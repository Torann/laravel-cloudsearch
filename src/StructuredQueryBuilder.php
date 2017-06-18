<?php

namespace LaravelCloudSearch;

class StructuredQueryBuilder
{
    /**
     * Cursor value
     *
     * @var string
     */
    public $cursor;

    /**
     * Array of key-value pairs for custom expressions
     *
     * @var array
     */
    public $expressions = [];

    /**
     * Array of facet specifications
     *
     * @var array
     */
    public $facets = [];

    /**
     * Content type of response
     *
     * @var string
     */
    public $format = 'json';

    /**
     * Specifies a structured query that filters the results of a search without
     * affecting how the results are scored and sorted. You use fq in
     * conjunction with the q parameter to filter the documents that match the
     * constraints specified in the q parameter. Specifying a filter just
     * controls which matching documents are included in the results, it has no
     * effect on how they are scored and sorted.
     *
     * @var StructuredSearch
     */
    public $fq;

    /**
     * The search criteria for the request
     *
     * @var StructuredSearch
     */
    public $q;

    /**
     * Query parser options
     *
     * @var array
     */
    public $options;

    /**
     * Fields to return in results
     *
     * @var string
     */
    public $returnFields;

    /**
     * Number results to return per page
     *
     * @var integer
     */
    public $size = 10; // default per page

    /**
     * Fields/expressions to sort by
     *
     * @var string
     */
    public $sort;

    /**
     * Search results offset
     *
     * @var integer
     */
    public $start = 0; // default offset

    /**
     * Array of fields to get statistics
     *
     * @var array
     */
    public $stats;

    /**
     * Create new instance
     */
    public function __construct()
    {
        $this->q = new StructuredSearch;
        $this->fq = new StructuredSearch;
    }

    /**
     * Alias to get structured search query
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->q->getQuery();
    }

    /**
     * Alias to get structured filter query
     *
     * @return array
     */
    public function getFilterQuery()
    {
        return $this->fq->getQuery();
    }

    /**
     * CURSOR
     * Retrieves a cursor value you can use to page through large result sets.
     * Use the size parameter to control the number of hits you want to include
     * in each response. You can specify either the cursor or start parameter in
     * a request, they are mutually exclusive.
     *
     * To get the first cursor, specify cursor=initial in your initial request.
     * In subsequent requests, specify the cursor value returned in the hits
     * section of the response.
     *
     * @param string $cursor
     *
     * @return StructuredQueryBuilder
     */
    public function cursor($cursor = 'initial')
    {
        $this->cursor = $cursor == 0 ? 'initial' : $cursor;

        return $this;
    }

    /**
     * EXPRESSION
     * Defines an expression that can be used to sort results. You can also
     * specify an expression as a return field.
     *
     * @param string $accessor
     * @param string $expression
     *
     * @return StructuredQueryBuilder
     */
    public function expr($accessor, $expression)
    {
        $this->expressions[$accessor] = $expression;
    }

    /**
     * FACET (sorted)
     * Specifies a field that you want to get facet information for—FIELD is the
     * name of the field. The specified field must be facet enabled in the
     * domain configuration. Facet options are specified as a JSON object. If
     * the JSON object is empty, facet.FIELD={}, facet counts are computed for
     * all field values, the facets are sorted by facet count, and the top 10
     * facets are returned in the results.
     *
     * sort specifies how you want to sort the facets in the results: bucket or
     * count. Specify bucket to sort alphabetically or numerically by facet
     * value (in ascending order). Specify count to sort by the facet counts
     * computed for each facet value (in descending order).
     *
     * size specifies the maximum number of facets to include in the results.
     * By default, Amazon CloudSearch returns counts for the top 10.
     *
     * @param string  $field
     * @param string  $sort
     * @param integer $size
     *
     * @return StructuredQueryBuilder
     */
    public function facet($field, $sort = "bucket", $size = 10)
    {
        $this->facets[$field] = [
            'sort' => $sort,
            'size' => $size,
        ];
    }

    /**
     * FACET (BUCKETS)
     * specifies an array of the facet values or ranges you want to count.
     * Buckets are returned in the order they are specified in the request. To
     * specify a range of values, use a comma (,) to separate the upper and
     * lower bounds and enclose the range using brackets or braces. A square
     * bracket, [ or ], indicates that the bound is included in the range, a
     * curly brace, { or }, excludes the bound. You can omit the upper or lower
     * bound to specify an open-ended range. When omitting a bound, you must
     * use a curly brace.
     *
     * @param string $field
     * @@param array $buckets
     * @param string $method
     *
     * @return StructuredQueryBuilder
     */
    public function facetBuckets($field, $buckets, $method = "filter")
    {
        $this->facets[$field] = [
            'buckets' => $buckets,
            'method' => $method,
        ];
    }

    /**
     * QUERY PARSER OPTIONS
     * Configure options for the query parser specified in the q.parser
     * parameter.    The options are specified as a JSON object, for example:
     * q.options={defaultOperator: 'or', fields: ['title^5','description']}
     *
     * defaultOperator-The default operator used to combine individual terms
     * in the search string. (and|or)
     * defaultOperator: 'or'
     *
     * fields—An array of the fields to search when no fields are specified in
     * a search.  You can specify a weight for each field to control the relative
     * importance of each field when Amazon CloudSearch calculates relevance scores.
     * fields: ['title^5','description']
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return StructuredQueryBuilder
     */
    public function options($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * The field and expression values to include in the response, specified as
     * a comma-separated list. By default, a search response includes all return
     * enabled fields (return=_all_fields). To return only the document IDs for
     * the matching documents, specify return=_no_fields. To retrieve the
     * relevance score calculated for each document, specify return=_score. You
     * specify multiple return fields as a comma separated list. For example,
     * return=title,_score returns just the title and relevance score of each
     * matching document.
     *
     * @param string $returnFields
     *
     * @return StructuredQueryBuilder
     */
    public function returnFields($returnFields)
    {
        $this->returnFields = $returnFields;

        return $this;
    }

    /**
     * The maximum number of search hits to return.
     *
     * @param integer $size
     *
     * @return StructuredQueryBuilder
     */
    public function size($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * A comma-separated list of fields or custom expressions to use to sort the
     * search results. You must specify the sort direction (asc or desc) for
     * each field. For example, sort=year desc,title asc. You can specify a
     * maximum of 10 fields and expressions. To use a field to sort results, it
     * must be sort enabled in the domain configuration. Array type fields
     * cannot be used for sorting. If no sort parameter is specified, results
     * are sorted by their default relevance scores in descending order:
     * sort=_score desc. You can also sort by document ID (sort=_id) and
     * version (sort=_version).
     *
     * @param string $field
     * @param string $direction
     *
     * @return StructuredQueryBuilder
     */
    public function sort($field, $direction = 'asc')
    {
        $this->sort = "{$field} {$direction}";

        return $this;
    }

    /**
     * The offset of the first search hit you want to return. You can specify
     * either the start or cursor parameter in a request, they are mutually
     * exclusive.
     *
     * @param integer $start
     *
     * @return StructuredQueryBuilder
     */
    public function start($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * To get statistics for a field you use the stats.FIELD parameter. FIELD
     * is the name of a facet-enabled numeric field. You specify an empty JSON
     * object, stats.FIELD={}, to get all of the available statistics for the
     * specified field. (The stats.FIELD parameter does not support any options;
     * you must pass an empty JSON object.) You can request statistics for
     * multiple fields in the same request.
     *
     * You can get statistics only for facet-enabled numeric fields: date,
     * date-array, double, double-array, int, or int-array. Note that only the
     * count, max, min, and missing statistics are returned for date and
     * date-array fields.
     *
     * @param string $field
     *
     * @return StructuredQueryBuilder
     */
    public function stats($field)
    {
        $this->stats[] = $field;
    }


    /**
     * Special function to filter by distance (lat/lon)
     *
     * @param string  $field
     * @param float   $lat
     * @param float   $lon
     * @param integer $radius
     * @param boolean $addExpr
     *
     * @return StructuredQueryBuilder
     */
    public function latlon($field, $lat, $lon, $radius = 50, $addExpr = false)
    {
        // upper left bound
        $lat1 = $lat + ($radius / 69);
        $lon1 = $lon - $radius / abs(cos(deg2rad($lat)) * 69);

        // lower right bound
        $lat2 = $lat - ($radius / 69);
        $lon2 = $lon + $radius / abs(cos(deg2rad($lat)) * 69);

        $min = "'{$lat1},{$lon1}'";
        $max = "'{$lat2},{$lon2}'";
        $this->fq->range($field, $min, $max);

        if ($addExpr) {
            $this->addDistanceExpr($field, $lat, $lon);
        }

        return $this;
    }

    /**
     * Special function to add 'distance' expression
     *
     * @param string $field
     * @param string $lat
     * @param string $lon
     *
     * @return StructuredQueryBuilder
     */
    public function addDistanceExpr($field, $lat, $lon)
    {
        $expression = "haversin(" .
            "{$lat}," .
            "{$lon}," .
            "{$field}.latitude," .
            "{$field}.longitude)";
        $this->expr("distance", $expression);

        return $this;
    }

    /**
     * Build the structured query array to send to AWS search
     *
     * @return array
     */
    public function buildStructuredQuery()
    {
        $structuredQuery = [];

        // cursor
        if ($this->cursor) {
            $structuredQuery['cursor'] = $this->cursor;
        }

        // expressions
        if ($this->expressions) {
            $structuredQuery['expr'] = json_encode($this->expressions);
        }

        // facets
        if ($this->facets) {
            $structuredQuery['facet'] = json_encode($this->facets);
        }

        // filter query
        if ($this->fq->query) {
            $structuredQuery['filterQuery'] = (string)$this->fq;
        }

        // query
        if ($this->q->query) {
            $structuredQuery['query'] = (string)$this->q;
        }

        // options
        if ($this->options) {
            $structuredQuery['queryOptions'] = json_encode($this->options);
        }

        // highlights
        // partial
        // parser
        $structuredQuery['queryParser'] = 'structured';

        // return
        if ($this->returnFields) {
            $structuredQuery['return'] = $this->returnFields;
        }

        // size
        $structuredQuery['size'] = $this->size;

        // sort
        if ($this->sort) {
            $structuredQuery['sort'] = $this->sort;
        }

        if (!$this->cursor) {
            $structuredQuery['start'] = $this->start;
        }

        // stats
        if ($this->stats) {

            // Parse fields
            $stats = array_map(function($field) {
                return "\"{$field}\":{}";
            }, $this->stats);

            $structuredQuery['stats'] = "{" . implode(',', $stats) . "}";
        }

        return $structuredQuery;
    }
}