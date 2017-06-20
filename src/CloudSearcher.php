<?php

namespace LaravelCloudSearch;

use Aws\Result;
use ReflectionMethod;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use LaravelCloudSearch\Query\Builder;
use Aws\CloudSearch\CloudSearchClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Aws\CloudSearchDomain\CloudSearchDomainClient;
use Illuminate\Database\Eloquent\Relations\Relation;
use LaravelCloudSearch\Query\StructuredQueryBuilder;
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
     * Queue the given model action.
     *
     * @param string $action
     * @param Model  $model
     *
     * @return bool
     */
    public function queue($action, Model $model)
    {
        switch($action) {
            case 'update':
                return $this->searchQueue()->push('update', $model->getKey(), get_class($model));
                break;
            case 'delete':
                return $this->searchQueue()->push('delete', $this->getSearchDocumentId($model), get_class($model));
                break;
        }

        return false;
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
            if ($fields = $this->getSearchDocument($model)) {
                $payload->push([
                    'type' => 'add',
                    'id' => $this->getSearchDocumentId($model),
                    'fields' => array_map(function ($value) {
                        return is_null($value) ? '' : $value;
                    }, $fields),
                ]);
            }
        });

        return $this->domainClient()->uploadDocuments([
            'documents' => json_encode($payload->all()),
            'contentType' => 'application/json',
        ]);
    }

    /**
     * Remove from search index
     *
     * @param mixed $ids
     *
     * @return array
     */
    public function delete($ids)
    {
        $payload = new Collection();

        // Add to the payload
        foreach((array) $ids as $search_document_id) {
            $payload->push([
                'type' => 'delete',
                'id' => $search_document_id,
            ]);
        }

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
     *
     * @return LengthAwarePaginator|array
     */
    public function searchAll($term, $perPage = 15)
    {
        return $this->newQuery()
            ->term($term)
            ->take($perPage)
            ->get();
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
     * Paginate the given query into a simple paginator.
     *
     * @param Result $result
     * @param int    $page
     * @param int    $perPage
     * @param array  $append
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
        $attributes['result_type'] = $this->getClassBasename($model);

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
     * @return array|null
     */
    protected function getSearchDocument(Model $model)
    {
        if ($data = $model->getSearchDocument()) {
            $data['searchable_type'] = get_class($model);
        }

        return $data;
    }

    /**
     * Create a document ID for Laravel CloudSearch using the searchable ID and
     * the class name of the model.
     *
     * @param Model $model
     *
     * @return string
     */
    protected function getSearchDocumentId(Model $model)
    {
        return $this->getClassBasename($model) . '-' . $model->getSearchableId();
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
                'version' => $this->config('config.version'),
            ]);
        }

        return $this->searchClient;
    }

    /**
     * Get the queue instance.
     *
     * @return Queue
     */
    public function searchQueue()
    {
        return app(Queue::class);
    }

    /**
     * Get the class "basename" of the given object / class.
     *
     * @param string|object $class
     *
     * @return string
     */
    protected function getClassBasename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', strtolower($class)));
    }

    /**
     * Create a new query builder instance.
     *
     * @return Builder
     */
    public function newQuery()
    {
        return new Builder($this);
    }
}
