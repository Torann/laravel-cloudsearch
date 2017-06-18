<?php

namespace LaravelCloudSearch;

use Aws\Result;
use ReflectionMethod;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Aws\CloudSearch\CloudSearchClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Aws\CloudSearchDomain\CloudSearchDomainClient;
use Illuminate\Database\Eloquent\Relations\Relation;
use Aws\CloudSearchDomain\Exception\CloudSearchDomainException;

class CloudSearcher
{
    /**
     * Laravel CloudSearch configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * CloudSearch domain client instance.
     *
     * @var CloudSearchClient
     */
    private $searchClient;

    /**
     * CloudSearch domain client instance.
     *
     * @var CloudSearchDomainClient
     */
    private $domainClient;

    /**
     * Create a CloudSearcher instance.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Add/Update the given models in the index.
     *
     * @param mixed $models
     *
     * @return array
     */
    public function update($models)
    {
        // Ensure it's a collection
        $models = $models instanceof Model
            ? new Collection([$models])
            : $models;

        $payload = new Collection();

        $models->each(function ($model) use ($payload) {

            // Get document and skip empties
            if (empty($fields = $this->getSearchDocument($model))) {
                return null;
            }

            // Add to the payload
            $payload->push([
                'type' => 'add',
                'id' => $model->getSearchableId(),
                'fields' => array_map(function($value) {
                    return is_null($value) ? '' : $value;
                }, $fields),
            ]);
        });

        return $this->domainClient()->uploadDocuments([
            'documents' => json_encode($payload->all()),
            'contentType' => 'application/json',
        ]);
    }

    /**
     * Remove from search index
     *
     * @param mixed $models
     *
     * @return array
     */
    public function remove($models)
    {
        // Ensure it's a collection
        $models = $models instanceof Model
            ? new Collection([$models])
            : $models;

        $payload = new Collection();

        $models->each(function ($model) use ($payload) {

            // Get document and skip empties
            if (method_exists($this, 'getSearchDocument') === false) {
                return null;
            }

            // Add to the payload
            $payload->push([
                'type' => 'delete',
                'version' => 1,
                'id' => $model->getSearchableId(),
            ]);
        });

        return $this->domainClient()->uploadDocuments([
            'documents' => json_encode($payload->all()),
            'contentType' => 'application/json',
        ]);
    }

    /**
     * Quick and simple search used for autocompletion.
     *
     * @param string $term
     * @param int    $perPage
     * @param array  $options
     *
     * @return LengthAwarePaginator|array
     */
    public function quickSearch($term, $perPage = 10, array $options = [])
    {
        $results = $this->performSearch($term, array_merge([
            'size' => $perPage,
        ], $options));

        return Arr::get($options, 'group', false)
            ? $this->groupResults($results)
            : $results;
    }

    /**
     * Get search results.
     *
     * @param StructuredQueryBuilder $builder
     *
     * @return Collection
     */
    public function get(StructuredQueryBuilder $builder)
    {
        return $this->hydrateResults(Arr::get($this->execute($builder), 'hits.hit', []));
    }

    /**
     * Paginate the given search results.
     *
     * @param StructuredQueryBuilder $builder
     * @param int                    $perPage
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(StructuredQueryBuilder $builder, $perPage = 15)
    {
        // Get current page
        $page = LengthAwarePaginator::resolveCurrentPage();

        // Set pagination params
        $builder->size($perPage)
            ->start((($page * $perPage) - $perPage));

        // Make request
        return $this->paginateResults($this->execute($builder), $page, $perPage);
    }

    /**
     * Perform the given search.
     *
     * @param StructuredQueryBuilder $builder
     *
     * @return mixed
     */
    public function execute(StructuredQueryBuilder $builder)
    {
        try {
            return $this->domainClient()->search($builder->buildStructuredQuery());
        }
        catch (CloudSearchDomainException $e) {
            dd($e->getAwsErrorMessage() ?: $e->getMessage());
            return $e->getAwsErrorMessage() ?: $e->getMessage();
        }
    }

    /**
     * Create collection from results.
     *
     * @param array $items
     *
     * @return Collection
     */
    protected function hydrateResults(array $items)
    {
        $items = array_map(function ($item) {
            return $this->newFromHitBuilder($item);
        }, $items);

        return Collection::make($items);
    }

    /**
     * Group Elasticsearch results by table name.
     *
     * @param Collection $results
     *
     * @return array
     */
    protected function groupResults(Collection $results)
    {
        $groups = [];

        $results->each(function ($item) use (&$groups) {
            $groups[$item->getTable()][] = $item;
        });

        return $groups;
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param Result $result
     * @param int   $page
     * @param int   $perPage
     * @param array $append
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function paginateResults(Result $result, $page, $perPage, array $append = [])
    {
        // Get total number of pages
        $total = Arr::get($result, 'hits.found', 0);

        // Create pagination instance
        $paginator = (new LengthAwarePaginator($this->hydrateResults(Arr::get($result, 'hits.hit', [])), $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]));

        return $paginator->appends($append);
    }

    /**
     * New from hit builder.
     *
     * @param array $hit
     *
     * @return Model
     */
    protected function newFromHitBuilder($hit = [])
    {
        // Reconstitute the attributes from the field values
        $attributes = array_map(function ($field) {
            return $field[0];
        }, Arr::get($hit, 'fields', []));

        // Get model name from source
        if (!($model = Arr::pull($attributes, 'searchable_type'))) return null;

        // Set type
        $attributes['result_type'] = basename(str_replace('\\', '/', strtolower($model)));

        // Create model instance from type
        return $this->newFromBuilderRecursive(new $model, $attributes);
    }

    /**
     * Create a new model instance that is existing recursive.
     *
     * @param Model    $model
     * @param array    $attributes
     * @param Relation $parentRelation
     *
     * @return Model
     */
    protected function newFromBuilderRecursive(Model $model, array $attributes = [], Relation $parentRelation = null)
    {
        // Create a new instance of the given model
        $instance = $model->newInstance([], $exists = true);

        // Set the array of model attributes
        $instance->setRawAttributes((array)$attributes, $sync = true);

        // Load relations recursive
        $this->loadRelationsAttributesRecursive($instance);

        // Load pivot
        $this->loadPivotAttribute($instance, $parentRelation);

        return $instance;
    }

    /**
     * Get the relations attributes from a model.
     *
     * @param Model $model
     */
    protected function loadRelationsAttributesRecursive(Model $model)
    {
        $attributes = $model->getAttributes();

        foreach ($attributes as $key => $value) {
            if (method_exists($model, $key)) {
                $reflection_method = new ReflectionMethod($model, $key);

                if ($reflection_method->class != 'Illuminate\Database\Eloquent\Model') {
                    $relation = $model->$key();

                    if ($relation instanceof Relation) {
                        // Check if the relation field is single model or collections
                        if (is_null($value) === true || !$this->isMultiLevelArray($value)) {
                            $value = [$value];
                        }

                        $models = $this->hydrateRecursive($relation->getModel(), $value, $relation);

                        // Unset attribute before match relation
                        unset($model[$key]);
                        $relation->match([$model], $models, $key);
                    }
                }
            }
        }
    }

    /**
     * Get the pivot attribute from a model.
     *
     * @param Model    $model
     * @param Relation $parentRelation
     */
    protected function loadPivotAttribute(Model $model, Relation $parentRelation = null)
    {
        foreach ($model->getAttributes() as $key => $value) {
            if ($key === 'pivot') {
                unset($model[$key]);

                $pivot = $parentRelation->newExistingPivot($value);
                $model->setRelation($key, $pivot);
            }
        }
    }

    /**
     * Check if an array is multi-level array like [[id], [id], [id]].
     *
     * For detect if a relation field is single model or collections.
     *
     * @param array $array
     *
     * @return boolean
     */
    protected function isMultiLevelArray(array $array)
    {
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a collection of models from plain arrays recursive.
     *
     * @param Model    $model
     * @param Relation $parentRelation
     * @param array    $items
     *
     * @return Collection
     */
    protected function hydrateRecursive(Model $model, array $items, Relation $parentRelation = null)
    {
        $items = array_map(function ($item) use ($model, $parentRelation) {
            return $this->newFromBuilderRecursive($model, ($item ?: []), $parentRelation);
        }, $items);

        return $model->newCollection($items);
    }

    /**
     * Get index document data for Laravel CloudSearch.
     *
     * @param Model $model
     *
     * @return array
     */
    protected function getSearchDocument(Model $model)
    {
        // Get indexable data from model
        $data = $model->getSearchDocument();

        // Append huntable type for polymorphic use
        $data['searchable_type'] = get_class($model);

        return $data;
    }

    /**
     * Get configuration value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Return the CloudSearch domain client instance.
     *
     * @return CloudSearchDomainClient
     */
    public function domainClient()
    {
        if (is_null($this->domainClient)) {
            $this->domainClient = new CloudSearchDomainClient($this->config('config'));
        }

        return $this->domainClient;
    }

    /**
     * Return the CloudSearch client instance.
     *
     * @return CloudSearchClient
     */
    public function searchClient()
    {
        if (is_null($this->searchClient)) {
            $this->searchClient = new CloudSearchClient([
                'region' => $this->config('config.region'),
                'credentials' => $this->config('config.credentials'),
                'version'  => $this->config('config.version'),
            ]);
        }

        return $this->searchClient;
    }

    /**
     * Create a new query builder instance.
     *
     * @return StructuredQueryBuilder
     */
    public function newQuery()
    {
        return new StructuredQueryBuilder;
    }
}
