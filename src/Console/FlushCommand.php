<?php

namespace LaravelCloudSearch\Console;

use Illuminate\Database\Eloquent\Builder;

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
     * @param Builder $builder
     * @param string  $name
     *
     * @return bool
     */
    protected function flush(Builder $builder, $name)
    {
        $this->getOutput()->write("Flushing [{$name}]");

        $builder->chunk(100, function ($models) {
            $this->cloudSearcher->delete($models);
            $this->getOutput()->write(str_repeat('.', $models->count()));
        });

        $this->getOutput()->writeln('<info>done</info>');
    }
}
