<?php

use LaravelCloudSearch\Queue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCloudsearchQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cloudsearch_queues', function (Blueprint $table) {
            $table->string('entry_id');
            $table->string('entry_type');
            $table->string('action', 10);
            $table->tinyInteger('status')->default(Queue::STATUS_WAITING);
            $table->unsignedInteger('created_at');

            $table->primary(['entry_id', 'entry_type', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cloudsearch_queues');
    }
}