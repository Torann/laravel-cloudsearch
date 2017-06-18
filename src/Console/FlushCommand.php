<?php

namespace LaravelCloudSearch\Console;

use Illuminate\Database\Eloquent\Model;

class FlushCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:flush
                                {model : Name or comma separated names of the model(s) to index}
                                {--l|locales= : Single or comma separated locales to index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all of the model records from the search index.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->processModels('flush');
    }

    /**
     * Index all model entries to ElasticSearch.
     *
     * @param Model $model
     *
     * @return bool
     */
    protected function flush(Model $model)
    {
        $this->getOutput()->write('Flushing [' . get_class($model) .']');

        $model->chunk(100, function ($models) {
            $this->cloudSearcher->remove($models);
            $this->getOutput()->write('.');
        });

        $this->getOutput()->writeln('<info>done</info>');
    }
}
