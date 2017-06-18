<?php

namespace LaravelCloudSearch;

use Closure;
use Exception;

class StructuredSearch
{
    /**
     * Array of structured search statements
     *
     * @var array
     */
    public $query;

    /**
     * Get the query property
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Includes a document only if it matches all of the specified expressions.
     * (Boolean AND operator.) The expressions can contain any of the structured
     * query operators, or a simple search string.
     *
     * @param Closure|string $block
     *
     * @return StructuredSearch
     */
    public function qAnd($block)
    {
        if ($block instanceof Closure) {

            $block($builder = new self);

            $this->query[] = "(and " . implode('', $builder->getQuery()) . ")";
        }
        else if (gettype($block) == "string") {
            $this->query[] = "(and '{$block}')";
        }

        return $this;
    }

    /**
     * Matches every document in the domain.
     *
     * @return StructuredSearch
     */
    public function matchall()
    {
        $this->query[] = "(matchall)";
    }

    /**
     * Searches a text or text-array field for the specified multi-term string and matches documents that contain the
     * terms within the specified distance of one another. (This is sometimes called a sloppy phrase search.) If you
     * omit the field option, Amazon CloudSearch searches all statically configured text and text-array fields by
     * default. Dynamic fields and literal fields are not searched by default. You can specify which fields you want to
     * search by default by specifying the q.options fields option.
     *
     * @param  string  $value
     * @param  string  $field
     * @param  integer $distance
     * @param  integer $boost
     *
     * @return StructuredSearch
     */
    private function near($value, $field = null, $distance = 3, $boost = null)
    {
        $near = "(near ";

        if ($field) {
            $near .= "field='{$field}' ";
        }

        if ($distance) {
            $near .= "distance='{$distance}' ";
        }

        if ($boost) {
            $near .= "boost='{$boost}' ";
        }

        $near .= "'{$value}')";

        $this->query[] = $near;

        return $this;
    }

    /**
     * Excludes a document if it matches the specified expression. (Boolean NOT
     * operator.) The expression can contain any of the structured query
     * operators, or a simple search string.
     *
     * @param Closure|string $block
     *
     * @return StructuredSearch
     */
    public function qNot($block)
    {
        if (gettype($block) == "object") {

            $block($builder = new self);

            $this->query[] = "(not " . implode('', $builder->getQuery()) . ")";
        }
        else if (gettype($block) == "string") {
            $this->query[] = "(not '{$block}')";
        }

        return $this;
    }

    /**
     * Includes a document if it matches any of the specified expressions.
     * (Boolean OR operator.) The expressions can contain any of the structured
     * query operators, or a simple search string.
     *
     * @param Closure|string $block
     *
     * @return StructuredSearch
     */
    public function qOr($block)
    {
        if ($block instanceof Closure) {

            $block($builder = new self);

            $this->query[] = "(or " . implode('', $builder->getQuery()) . ")";
        }
        else if (gettype($block) == "string") {
            $this->query[] = "(or '{$block}')";
        }

        return $this;
    }

    /**
     * Searches a text or text-array field for the specified phrase. If you
     * omit the field option, Amazon CloudSearch searches all statically
     * configured text and text-array fields by default. Dynamic fields and
     * literal fields are not searched by default. You can specify which fields
     * you want to search by default by specifying the q.options fields option.
     *
     * @param  string  $value
     * @param  string  $field
     * @param  integer $boost
     *
     * @return StructuredSearch
     */
    private function phrase($value, $field = null, $boost = null)
    {
        $phrase = "(phrase ";

        if ($field) {
            $phrase .= "field='{$field}' ";
        }

        if ($boost) {
            $phrase .= "boost='{$boost}' ";
        }

        $phrase .= "'{$value}')";

        $this->query[] = $phrase;

        return $this;
    }

    /**
     * Searches a text, text-array, literal, or literal-array field for the
     * specified prefix followed by zero or more characters. If you omit the
     * field option, Amazon CloudSearch searches all statically configured text
     * and text-array fields by default. Dynamic fields and literal fields are
     * not searched by default. You can specify which fields you want to search
     * by default by specifying the q.options fields option.
     *
     * @param  string  $value
     * @param  string  $field
     * @param  integer $boost
     *
     * @return StructuredSearch
     */
    private function prefix($value, $field = null, $boost = null)
    {
        $prefix = "(prefix ";

        if ($field) {
            $prefix .= "field='{$field}' ";
        }

        if ($boost) {
            $prefix .= "boost='{$boost}' ";
        }

        $prefix .= "'{$value}')";

        $this->query[] = $prefix;

        return $this;
    }

    /**
     * Searches a numeric field (double, double-array, int, int-array) or date
     * field (date, date-array) for values in the specified range. Matches
     * documents that have at least one value in the field within the specified
     * range. The field option must be specified.
     *
     * To specify a range of values, use a comma (,) to separate the upper and
     * lower bounds and enclose the range using brackets or braces. A square
     * bracket, [ or ], indicates that the bound is included in the range, a
     * curly brace, { or }, excludes the bound. You can omit the upper or lower
     * bound to specify an open-ended range. When omitting a bound, you must
     * use a curly brace.
     *
     * Dates and times are specified in UTC (Coordinated Universal Time)
     * according to IETF RFC3339: yyyy-mm-ddTHH:mm:ss.SSSZ. In UTC, for example,
     * 5:00 PM August 23, 1970 is: 1970-08-23T17:00:00Z. Note that you can also
     * specify fractional seconds when specifying times in UTC. For example,
     * 1967-01-31T23:20:50.650Z.
     *
     * @param  string         $field
     * @param  integer|string $min
     * @param  integer|string $max
     *
     * @return StructuredSearch
     */
    public function range($field, $min, $max)
    {
        $range = "(range field={$field} ";

        if ($min and !$max) {
            $value = "[{$min},}";
        }
        else if (!$min and $max) {
            $value = "{,{$max}]";
        }
        else if ($min and $max) {
            $value = "[{$min},{$max}]";
        }
        else {
            return $this;
        }

        $range .= "{$value})";

        $this->query[] = $range;

        return $this;
    }

    /**
     * Searches the specified field for a string, numeric value, or date. The
     * field option must be specified when searching for a value. If you omit
     * the field option, Amazon CloudSearch searches all statically configured
     * text and text-array fields by default. Dynamic fields and literal fields
     * are not searched by default. You can specify which fields you want to
     * search by default by specifying the q.options fields option.
     *
     * Dates and times are specified in UTC (Coordinated Universal Time)
     * according to IETF RFC3339: yyyy-mm-ddTHH:mm:ss.SSSZ. In UTC, for example,
     * 5:00 PM August 23, 1970 is: 1970-08-23T17:00:00Z. Note that you can also
     * specify fractional seconds when specifying times in UTC. For example,
     * 1967-01-31T23:20:50.650Z.
     *
     * @param  string  $value
     * @param  string  $field
     * @param  integer $boost
     *
     * @return StructuredSearch
     */
    private function term($value, $field = null, $boost = null)
    {
        $term = "(term ";

        if ($field) {
            $term .= "field='{$field}' ";
        }

        if ($boost) {
            $term .= "boost='{$boost}' ";
        }

        $term .= "'{$value}')";

        $this->query[] = $term;

        return $this;
    }

    /**
     * Query and filterQuery methods are made inaccessible in order to trigger
     * the __call magic method.  Here we can escape argument strings prior to
     * calling the intended function
     *
     * @param string $method
     * @param array  $args
     *
     * @return StructuredQueryBuilder
     * @throws Exception
     */
    public function __call($method, $args)
    {
        // Raise exception if no method
        if (!method_exists($this, $method)) {
            throw new Exception("Method doesn't exist");
        }

        // Escape string arguments
        foreach ($args as $key => $value) {
            if (gettype($value) == 'string') {
                $value = preg_replace('#\\\#i', "\\\\\\", $value);
                $value = preg_replace("#'#", "\\'", $value);
                $args[$key] = $value;
            }
        }

        // call the desired the method
        call_user_func_array([$this, $method], $args);

        return $this;
    }

    /**
     * Magic method when casting as string
     * Concatenate all query statements into wrapped 'and' statement
     *
     * @return string
     */
    public function __toString()
    {
        return "(and " . implode('', $this->query) . ")";
    }
}