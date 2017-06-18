<?php

namespace LaravelCloudSearch\Eloquent;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LocalizedScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder  $builder
     * @param \Illuminate\Database\Eloquent\Model    $model
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getTable() . '.locale', app()->getLocale());
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param \Illuminate\Database\Eloquent\Builder  $builder
     */
    public function extend(Builder $builder)
    {
        $this->addWithoutLocalization($builder);
    }

    /**
     * Add the without-moderated extension to the builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder  $builder
     */
    protected function addWithoutLocalization(Builder $builder)
    {
        $builder->macro('withoutLocalization', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
