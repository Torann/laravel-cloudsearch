<?php

namespace LaravelCloudSearch\Console;

use Illuminate\Database\Eloquent\Model;

class IndexCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:index
                                {model : Name or comma separated names of the model(s) to index}
                                {--l|locales= : Single or comma separated locales to index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all the entries in an Eloquent model.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->processModels('index');
    }

    /**
     * Index all model entries to ElasticSearch.
     *
     * @param Model  $instance
     * @param string $name
     *
     * @return bool
     */
    protected function index(Model $instance, $name)
    {
        $this->getOutput()->write("Indexing [{$name}]");

        $instance->chunk($this->batching_size, function ($models) use (&$total) {
            $this->cloudSearcher->update($models);
            $this->getOutput()->write(str_repeat('.', $models->count()));
        });

        $this->getOutput()->writeln('<info>done</info>');
    }
}
