<?php

namespace LaravelCloudSearch\Eloquent;

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
        static::observe(Observer::class);
    }

    /**
     * Dispatch the job to make the model searchable.
     *
     * @return array
     */
    public function addToCloudSearch()
    {
        return $this->getCloudSearch()->update($this);
    }

    /**
     * Dispatch the job to make the model unsearchable.
     *
     * @return array
     */
    public function removeFromCloudSearch()
    {
        return $this->getCloudSearch()->remove($this);
    }

    /**
     * Get the CloudSearcher index name for the model.
     *
     * @return string
     */
    public function getCloudSearchIndex()
    {
        return $this->getTable();
    }

    /**
     * Perform a search against the model's indexed data.
     *
     * @return \LaravelCloudSearch\Builder
     */
    public static function search()
    {
        return new Builder(new static);
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
