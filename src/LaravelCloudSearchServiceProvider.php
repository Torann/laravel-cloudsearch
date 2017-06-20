<?php

namespace LaravelCloudSearch;

use Illuminate\Support\ServiceProvider;

class LaravelCloudSearchServiceProvider extends ServiceProvider
{
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

        $this->app->singleton(Queue::class, function ($app) {
            return new Queue($app['db']);
        });

        if ($this->app->runningInConsole()) {
            $this->registerResources();
            $this->registerCommands();
        }
    }

    /**
     * Register the resources.
     *
     * @return bool
     */
    protected function registerResources()
    {
        if ($this->isLumen() === false) {
            $this->publishes([
                __DIR__.'/../config/cloud-search.php' => config_path('cloud-search.php'),
            ], 'config');

            $this->mergeConfigFrom(
                __DIR__.'/../config/cloud-search.php', 'cloud-search'
            );
        }

        $this->publishes([
            __DIR__ . '/../database/migrations' => base_path('/database/migrations'),
        ], 'migrations');
    }

    /**
     * Register all commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->commands([
            Console\FieldsCommand::class,
            Console\FlushCommand::class,
            Console\IndexCommand::class,
            Console\QueueCommand::class,
        ]);
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
