<?php

namespace LaravelCloudSearch\Eloquent;

use LaravelCloudSearch\Query\Builder;
use LaravelCloudSearch\CloudSearcher;

trait Searchable
{
    /**
     * Document score hit score.
     *
     * @var null|int
     */
    public $documentScore = null;

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootSearchable()
    {
        if (config('cloud-search.enabled', true)) {
            static::observe(Observer::class);
        }
    }

    /**
     * Dispatch the job to make the model searchable.
     *
     * @return bool
     */
    public function addToCloudSearch()
    {
        return $this->getCloudSearch()->queue('update', $this);

    }

    /**
     * Dispatch the job to make the model unsearchable.
     *
     * @return bool
     */
    public function deleteFromCloudSearch()
    {
        return $this->getCloudSearch()->queue('delete', $this);
    }

    /**
     * Perform a search against the model's indexed data.
     *
     * @param string $query
     *
     * @return \LaravelCloudSearch\Query\Builder
     */
    public static function search($query)
    {
        return self::searchBuilder()->term($query);
    }

    /**
     * Get the search builder instance.
     *
     * @return \LaravelCloudSearch\Query\Builder
     */
    public static function searchBuilder()
    {
        $instance = new static();

        $builder = new Builder($instance->getCloudSearch());

        return $builder->searchableType($instance);
    }

    /**
     * Get search document ID for the model.
     *
     * @return string|int
     */
    public function getSearchableId()
    {
        if (method_exists($this, 'getLocalizedSearchableId')) {
            return $this->getLocalizedSearchableId();
        }

        return $this->getKey();
    }

    /**
     * Get search document data for the model.
     *
     * @return array
     */
    abstract public function getSearchDocument();

    /**
     * Get a CloudSearch for the model.
     *
     * @return CloudSearcher
     */
    public function getCloudSearch()
    {
        return app(CloudSearcher::class);
    }
}
