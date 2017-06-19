<?php

namespace LaravelCloudSearch\Query;

use Closure;
use LaravelCloudSearch\CloudSearcher;

class Builder
{
    /**
     * Cloud searcher instance.
     *
     * @var \LaravelCloudSearch\CloudSearcher
     */
    public $cloudSearcher;

    /**
     * Create a new search builder instance.
     *
     * @param \LaravelCloudSearch\CloudSearcher $cloudSearcher
     */
    public function __construct(CloudSearcher $cloudSearcher)
    {
        $this->cloudSearcher = $cloudSearcher;
        $this->builder = new StructuredQueryBuilder();
    }

    /**
     * Set the searchable type.
     *
     * @param mixed $type
     *
     * @return self
     */
    public function searchableType($type)
    {
        if (is_object($type)) {
            $type = get_class($type);
        }

        // Set the search type
        $this->phrase($type, 'searchable_type');

        // Set locale
        if (method_exists($type, 'getLocalizedSearchableId')) {
            $this->byLocale();
        }

        return $this;
    }

    /**
     * Set the locale to use for searching.
     *
     * @param string $locale
     *
     * @return self
     */
    public function byLocale($locale = null)
    {
        // Use the current system locale if one is not set
        $this->phrase($locale ?: app()->getLocale(), 'locale');

        return $this;
    }

    /**
     * Cursor method
     *
     * @param string $cursor
     *
     * @return self
     */
    public function cursor($cursor = 'initial')
    {
        $this->builder->cursor($cursor);

        return $this;
    }

    /**
     * Set builder expression
     *
     * @param array $filters
     *
     * @return self
     */
    public function filter(array $filters)
    {
        foreach($filters as $field=>$value) {
            if (is_array($value)) {
                foreach(array_flatten(array_filter($value)) as $v) {
                    $this->phrase($v, $field);
                }
            }
            else {
                $this->phrase($value, $field);
            }
        }

        return $this;
    }

    /**
     * Set builder expression
     *
     * @param string $accessor
     * @param string $expression
     *
     * @return self
     */
    public function expr($accessor, $expression)
    {
        $this->builder->expr($accessor, $expression);

        return $this;
    }

    /**
     * Build return facets array
     *
     * @param string  $field
     * @param string  $sort
     * @param integer $size
     *
     * @return self
     */
    public function facet($field, $sort = "bucket", $size = 10)
    {
        $this->builder->facet($field, $sort, $size);

        return $this;
    }

    /**
     * Build return facets with explicit buckets
     *
     * @param string $field
     * @param array  $buckets
     * @param string $method
     *
     * @return self
     */
    public function facetBuckets($field, $buckets, $method = "filter")
    {
        $this->builder->facetBuckets($field, $buckets, $method);

        return $this;
    }

    /**
     * Create an 'and' wrapped query block
     *
     * @param Closure|string $block
     *
     * @return self
     */
    public function qAnd($block)
    {
        $this->builder->q->qAnd($block);

        return $this;
    }

    /**
     * Create an 'and' wrapped filter query block
     *
     * @param Closure|string $block
     *
     * @return self
     */
    public function filterAnd($block)
    {
        $this->builder->fq->qAnd($block);

        return $this;
    }

    /**
     * Build match all query
     *
     * @return self
     */
    public function matchall()
    {
        $this->builder->q->matchall();

        return $this;
    }

    /**
     * Create a near (sloppy) query
     *
     * @param string $value
     * @param string $field
     * @param int    $distance
     * @param int    $boost
     *
     * @return self
     */
    public function near($value, $field = null, $distance = 3, $boost = null)
    {
        $this->builder->q->near($value, $field, $distance, $boost);

        return $this;
    }

    /**
     * Create a near (sloppy) query
     *
     * @param string $value
     * @param string $field
     * @param int    $distance
     * @param int    $boost
     *
     * @return self
     */
    public function filterNear($value, $field, $distance = 3, $boost = null)
    {
        $this->builder->fq->near($value, $field, $distance, $boost);

        return $this;
    }

    /**
     * Create a 'not' wrapped query block
     *
     * @param Closure|string $block
     *
     * @return self
     */
    public function qNot($block)
    {
        $this->builder->q->qNot($block);

        return $this;
    }

    /**
     * Create a 'not' wrapped query block
     *
     * @param Closure|string $block
     *
     * @return self
     */
    public function filterNot($block)
    {
        $this->builder->fq->qNot($block);

        return $this;
    }

    /**
     * Create an 'or' wrapped query block
     *
     * @param Closure|string $block
     *
     * @return self
     */
    public function qOr($block)
    {
        $this->builder->q->qOr($block);

        return $this;
    }

    /**
     * Create an 'or' wrapped query block
     *
     * @param Closure|string $block
     *
     * @return self
     */
    public function filterOr($block)
    {
        $this->builder->fq->qOr($block);

        return $this;
    }

    /**
     * Create a phrase query
     *
     * @param string $value
     * @param string $field
     * @param int    $boost
     *
     * @return self
     */
    public function phrase($value, $field = null, $boost = null)
    {
        $this->builder->q->phrase($value, $field, $boost);

        return $this;
    }

    /**
     * Create a phrase query
     *
     * @param string $value
     * @param string $field
     * @param int    $boost
     *
     * @return self
     */
    public function filterPhrase($value, $field, $boost = null)
    {
        $this->builder->fq->phrase($value, $field, $boost);

        return $this;
    }

    /**
     * Create a prefix query
     *
     * @param string $value
     * @param string $field
     * @param int    $boost
     *
     * @return self
     */
    public function prefix($value, $field = null, $boost = null)
    {
        $this->builder->q->prefix($value, $field, $boost);

        return $this;
    }

    /**
     * Create a prefix query
     *
     * @param string $value
     * @param string $field
     * @param int    $boost
     *
     * @return self
     */
    public function filterPrefix($value, $field, $boost = null)
    {
        $this->builder->fq->prefix($value, $field, $boost);

        return $this;
    }

    /**
     * Create a range query
     *
     * @param string     $field
     * @param string|int $min
     * @param string|int $max
     *
     * @return self
     */
    public function range($field, $min, $max = null)
    {
        $this->builder->q->range($field, $min, $max);

        return $this;
    }

    /**
     * Create a range query
     *
     * @param string     $field
     * @param string|int $min
     * @param string|int $max
     *
     * @return self
     */
    public function filterRange($field, $min, $max = null)
    {
        $this->builder->fq->range($field, $min, $max);

        return $this;
    }

    /**
     * Create a term query
     *
     * @param string $value
     * @param string $field
     * @param int    $boost
     *
     * @return self
     */
    public function term($value, $field = null, $boost = null)
    {
        $this->builder->q->term($value, $field, $boost);

        return $this;
    }

    /**
     * Create a term query
     *
     * @param string $value
     * @param string $field
     * @param int    $boost
     *
     * @return self
     */
    public function filterTerm($value, $field, $boost = null)
    {
        $this->builder->fq->term($value, $field, $boost);

        return $this;
    }

    /**
     * Set return fields property of query
     *
     * @param string $value
     *
     * @return self
     */
    public function returnFields($value)
    {
        $this->builder->returnFields($value);

        return $this;
    }

    /**
     * Set options property of query
     *
     * @param string $key
     * @param string $value
     *
     * @return self
     */
    public function options($key, $value)
    {
        $this->builder->options($key, $value);

        return $this;
    }

    /**
     * Set the "limit" for the query.
     *
     * @param int $value
     *
     * @return self
     */
    public function take($value)
    {
        $this->builder->size($value);

        return $this;
    }

    /**
     * Sort query
     *
     * @param string $field
     * @param string $direction
     *
     * @return self
     */
    public function sort($field, $direction = 'asc')
    {
        $this->builder->sort($field, $direction);

        return $this;
    }

    /**
     * Set start property of query
     *
     * @param int $value
     *
     * @return self
     */
    public function start($value)
    {
        $this->builder->start($value);

        return $this;
    }

    /**
     * Build field statistics
     *
     * @param string $field
     *
     * @return self
     */
    public function stats($field)
    {
        $this->builder->stats($field);

        return $this;
    }

    /**
     * Build a location range filter
     *
     * @param string  $field
     * @param string  $lat
     * @param string  $lon
     * @param integer $radius
     * @param bool    $addExpr
     *
     * @return self
     */
    public function latlon($field, $lat, $lon, $radius = 50, $addExpr = false)
    {
        $this->builder->latlon($field, $lat, $lon, $radius, $addExpr);

        return $this;
    }

    /**
     * Build distance expression
     *
     * @param string $field
     * @param string $lat
     * @param string $lon
     *
     * @return self
     */
    public function addDistanceExpr($field, $lat, $lon)
    {
        $this->builder->addDistanceExpr($field, $lat, $lon);

        return $this;
    }

    /**
     * Get the first result from the search.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function first()
    {
        $this->take(1);

        return $this->get()->first();
    }

    /**
     * Method to trigger request-response
     *
     * @return \Illuminate\Support\Collection
     */
    public function get()
    {
        return $this->cloudSearch()->get($this->builder);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int $perPage
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15)
    {
        return $this->cloudSearch()->paginate($this->builder, $perPage);
    }

    /**
     * Get the CloudSearch to handle the query.
     *
     * @return \LaravelCloudSearch\CloudSearcher
     */
    protected function cloudSearch()
    {
        return $this->cloudSearcher;
    }
}