<?php

namespace LaravelCloudSearch\Console;

use LaravelCloudSearch\Queue;
use Illuminate\Console\Command;
use LaravelCloudSearch\CloudSearcher;

class QueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the CloudSearch queue.';

    /**
     * @var \LaravelCloudSearch\CloudSearcher
     */
    protected $cloudSearcher;

    /**
     * @var \LaravelCloudSearch\Queue
     */
    protected $queue;

    /**
     * @var int
     */
    protected $batching_size = 100;

    /**
     * Create a new console command instance.
     *
     * @param CloudSearcher $cloudSearcher
     * @param Queue         $queue
     */
    public function __construct(CloudSearcher $cloudSearcher, Queue $queue)
    {
        parent::__construct();

        $this->cloudSearcher = $cloudSearcher;
        $this->batching_size = $cloudSearcher->config('batching_size', 100);
        $this->queue = $queue;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('Processing search queue');

        $this->queue->getBatch()->each(function ($collection, $action) {
            $collection->groupBy('entry_type')->each(function ($items, $model) use ($action) {
                $this->{$action}($items, $model);
            });
        });

        $this->queue->flushBatch();
    }

    /**
     * Add or update given models in the search index.
     *
     * @param \Illuminate\Support\Collection $items
     * @param string                         $model
     */
    protected function update($items, $model)
    {
        // Get the model's primary key
        $instance = new $model;

        // Create a full column name
        $key = $instance->getTable() . '.' . $instance->getKeyName();

        // Process all models
        $model::whereIn($key, $items->pluck('entry_id'))->chunk($this->batching_size, function($models) {
            $this->cloudSearcher->update($models);
        });
    }

    /**
     * Delete given models from search index.
     *
     * @param \Illuminate\Support\Collection $items
     * @param string                         $model
     */
    protected function delete($items, $model)
    {
        foreach($items->chunk($this->batching_size) as $models) {
            $this->cloudSearcher->delete($models->pluck('entry_id'));
        }
    }
}
