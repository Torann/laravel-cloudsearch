<?php

namespace LaravelCloudSearch\Eloquent;

trait Localized
{
    /**
     * Register eloquent event handlers.
     *
     * @return void
     */
    public static function bootLocalized()
    {
        static::addGlobalScope(new LocalizedScope);
    }

    /**
     * Get search document ID for the model.
     *
     * @return string|int
     */
    public function getLocalizedSearchableId()
    {
        return $this->locale . '-' . $this->getKey();
    }
}
