<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureLogsTableExists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('logs')) {
            Schema::create('logs', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id', false)->unsigned()->nullable();
                $table->enum('type', ['info', 'warning', 'success', 'danger', 'emergency'])->nullable();
                $table->string('title', 255)->nullable();
                $table->mediumText('description')->nullable();
                $table->text('uri')->nullable();
                $table->ipAddress('ip')->nullable();
                $table->boolean('is_api')->default(0);
                $table->longText('request_info')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->string('created_by', 55)->nullable();
            });
        }

        $this->addIndex('logs', 'logs_created_at_id_index', ['created_at', 'id']);
        $this->addIndex('logs', 'logs_type_created_at_index', ['type', 'created_at']);
        $this->addIndex('logs', 'logs_user_id_created_at_index', ['user_id', 'created_at']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropIndex('logs', 'logs_created_at_id_index');
        $this->dropIndex('logs', 'logs_type_created_at_index');
        $this->dropIndex('logs', 'logs_user_id_created_at_index');
    }

    protected function addIndex($table, $name, array $columns)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $wrappedColumns = implode(', ', array_map(function ($column) {
            return '`' . $column . '`';
        }, $columns));

        try {
            DB::statement('ALTER TABLE `' . $table . '` ADD INDEX `' . $name . '` (' . $wrappedColumns . ')');
        } catch (\Throwable $exception) {
            // Ignore duplicate-index errors on environments that already have these indexes.
        }
    }

    protected function dropIndex($table, $name)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `' . $table . '` DROP INDEX `' . $name . '`');
        } catch (\Throwable $exception) {
            // Ignore missing-index errors so rollback stays safe.
        }
    }
}
