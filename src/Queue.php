<?php

namespace LaravelCloudSearch;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\DatabaseManager;

class Queue
{
    /**
     * Status types.
     */
    const STATUS_WAITING = 0;
    const STATUS_RUNNING = 1;

    /**
     * The database manager instance.
     *
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * The database table that holds the jobs.
     *
     * @var string
     */
    protected $table = 'cloudsearch_queues';

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $expire = 60;

    /**
     * Create a new queue instance.
     *
     * @param DatabaseManager $database
     */
    public function __construct(DatabaseManager $database)
    {
        $this->database = $database;
    }

    /**
     * Push a new job into the queue.
     *
     * @param string $action
     * @param string $entry_id
     * @param string $entry_type
     *
     * @return mixed
     */
    public function push($action, $entry_id, $entry_type)
    {
        return $this->database->table($this->table)->updateOrInsert([
            'entry_id' => $entry_id,
            'entry_type' => $entry_type,
            'action' => $action,
        ], [
            'entry_id' => $entry_id,
            'entry_type' => $entry_type,
            'action' => $action,
            'status' => self::STATUS_WAITING,
            'created_at' => $this->currentTime(),
        ]);
    }

    /**
     * Get a queue batch.
     *
     * @return Collection
     */
    public function getBatch()
    {
        // Mark all as running
        $this->database->table($this->table)
            ->where('status', self::STATUS_WAITING)
            ->update([
                'status' => self::STATUS_RUNNING,
            ]);

        // Start processing
        return $this->database->table($this->table)
            ->select(['entry_id', 'entry_type', 'action'])
            ->where('status', self::STATUS_RUNNING)
            ->get()
            ->groupBy('action');
    }

    /**
     * Flush a batch.
     *
     * @return bool
     */
    public function flushBatch()
    {
        return $this->database->table($this->table)
            ->where('status', self::STATUS_RUNNING)
            ->delete();
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * @return int
     */
    protected function currentTime()
    {
        return Carbon::now()->getTimestamp();
    }
}