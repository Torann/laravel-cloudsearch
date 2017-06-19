<?php

namespace LaravelCloudSearch\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use LaravelCloudSearch\CloudSearcher;
use Torann\Localization\LocaleManager;

abstract class AbstractCommand extends Command
{
    /**
     * @var \LaravelCloudSearch\CloudSearcher
     */
    protected $cloudSearcher;

    /**
     * Namespace for models.
     *
     * @var string
     */
    protected $models;

    /**
     * Create a new console command instance.
     *
     * @param CloudSearcher $cloudSearcher
     */
    public function __construct(CloudSearcher $cloudSearcher)
    {
        parent::__construct();

        $this->cloudSearcher = $cloudSearcher;
        $this->models = config('cloud-search.model_namespace', '\\App');
    }

    /**
     * Perform action model mapping.
     *
     * @param string $action
     */
    protected function processModels($action)
    {
        // Check for multilingual support
        $locales = $this->getLocales();

        // Process all provided models
        foreach ($this->getModelArgument() as $model) {
            if ($model = $this->validateModel("{$this->models}\\{$model}")) {

                // Get model instance
                $instance = new $model();

                // Perform action
                if (empty($locales) === false && method_exists($instance, 'getLocalizedSearchableId')) {

                    // Process each locale using the by locale macro
                    foreach ($locales as $locale) {

                        $this->line("\nIndexing locale: <info>{$locale}</info>");

                        $this->$action(
                            $instance->byLocale($locale),
                            $model
                        );
                    }
                }
                else {
                    $this->$action($instance);
                }
            }
        }
    }

    /**
     * Get action argument.
     *
     * @param  array $validActions
     *
     * @return array
     */
    protected function getActionArgument($validActions = [])
    {
        $action = strtolower($this->argument('action'));

        if (in_array($action, $validActions) === false) {
            throw new \RuntimeException("The [{$action}] option does not exist.");
        }

        return $action;
    }

    /**
     * Get model argument.
     *
     * @return array
     */
    protected function getModelArgument()
    {
        $models = explode(',', preg_replace('/\s+/', '', $this->argument('model')));

        return array_map(function ($model) {
            $model = array_map(function ($m) {
                return Str::studly($m);
            }, explode('\\', $model));

            return implode('\\', $model);
        }, $models);
    }

    /**
     * Get an array of supported locales.
     *
     * @return array|null
     */
    protected function getLocales()
    {
        // Get user specified locales
        if ($locales = $this->option('locales')) {
            return array_filter(explode(',', preg_replace('/\s+/', '', $locales)));
        }

        // Check for package
        if (class_exists('\\Torann\\Localization\\LocaleManager')) {
            return app(LocaleManager::class)->getSupportedLanguagesKeys();
        }

        return config('cloud-search.support_locales');
    }

    /**
     * Validate model.
     *
     * @param  string $model
     *
     * @return bool
     */
    protected function validateModel($model)
    {
        // Verify model existence
        if (class_exists($model) === false) {
            $this->error("Model [{$model}] not found");

            return false;
        }

        // Verify model is Elasticsearch ready
        if (method_exists($model, 'getSearchDocument') === false) {
            $this->error("Model [{$model}] does not support searching.");

            return false;
        }

        return $model;
    }
}
