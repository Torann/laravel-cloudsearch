<?php

namespace LaravelCloudSearch\Eloquent;

class Observer
{
    /**
     * Handle the saved event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function saved($model)
    {
        $model->addToCloudSearch();
    }

    /**
     * Handle the deleted event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function deleted($model)
    {
        $model->removeFromCloudSearch();
    }

    /**
     * Handle the restored event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function restored($model)
    {
        $this->saved($model);
    }
}