<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddLogFilterIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('logs')) {
            return;
        }

        if (Schema::hasColumn('logs', 'created_at') && Schema::hasColumn('logs', 'id')) {
            $this->addIndex('logs', 'logs_created_at_id_index', ['created_at', 'id']);
        }

        if (Schema::hasColumn('logs', 'type') && Schema::hasColumn('logs', 'created_at')) {
            $this->addIndex('logs', 'logs_type_created_at_index', ['type', 'created_at']);
        }

        if (Schema::hasColumn('logs', 'user_id') && Schema::hasColumn('logs', 'created_at')) {
            $this->addIndex('logs', 'logs_user_id_created_at_index', ['user_id', 'created_at']);
        }
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
