<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false)->unsigned()->nullable();
            $table->enum('type',['info','warning','success','danger','emergency'])->nullable();
            $table->string('title',255)->nullable();
            $table->mediumText('description')->nullable();
            $table->text('uri')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->boolean('is_api')->default(0);
            $table->longText('request_info')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->string('created_by',55)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
}
