<?php

namespace LaravelCloudSearch;

use Illuminate\Support\ServiceProvider;

class LaravelCloudSearchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/cloud-search.php', 'cloud-search'
        );
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CloudSearcher::class, function ($app) {
            return new CloudSearcher($app->config->get('cloud-search'));
        });

        if ($this->app->runningInConsole()) {
            if ($this->isLumen() === false) {
                $this->publishes([
                    __DIR__.'/../config/cloud-search.php' => config_path('cloud-search.php'),
                ], 'config');
            }

            $this->commands([
                Console\FieldsCommand::class,
                Console\FlushCommand::class,
                Console\IndexCommand::class,
            ]);
        }
    }

    /**
     * Check if package is running under a Lumen app.
     *
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen') === true;
    }
}
