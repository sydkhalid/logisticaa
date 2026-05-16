<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureCommonFilterIndexes extends Migration
{
    private $indexes = [
        ['logs', 'common_logs_created_at_index', ['created_at']],
        ['logs', 'common_logs_type_index', ['type']],
        ['vehicles', 'common_vehicles_vehicle_no_index', ['vehicleNo']],
        ['trackings', 'common_trackings_lr_number_index', ['lrNumber']],
        ['trackings', 'common_trackings_status_index', ['status']],
    ];

    public function up()
    {
        foreach ($this->indexes as [$table, $name, $columns]) {
            $this->addIndexIfMissing($table, $name, $columns);
        }
    }

    public function down()
    {
        foreach ($this->indexes as [$table, $name]) {
            $this->dropIndex($table, $name);
        }
    }

    private function addIndexIfMissing(string $table, string $name, array $columns): void
    {
        if (!Schema::hasTable($table) || !$this->tableHasColumns($table, $columns)) {
            return;
        }

        if ($this->hasIndexStartingWith($table, $columns)) {
            return;
        }

        $wrappedColumns = implode(', ', array_map(function ($column) {
            return '`' . $column . '`';
        }, $columns));

        try {
            DB::statement('ALTER TABLE `' . $table . '` ADD INDEX `' . $name . '` (' . $wrappedColumns . ')');
        } catch (\Throwable $exception) {
            // Keep the migration safe on servers where an equivalent index already exists.
        }
    }

    private function tableHasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasIndexStartingWith(string $table, array $columns): bool
    {
        $indexes = [];

        try {
            $rows = DB::select('SHOW INDEX FROM `' . $table . '`');
        } catch (\Throwable $exception) {
            return false;
        }

        foreach ($rows as $row) {
            $keyName = (string) $row->Key_name;
            $sequence = (int) $row->Seq_in_index;
            $indexes[$keyName][$sequence] = (string) $row->Column_name;
        }

        foreach ($indexes as $indexedColumns) {
            ksort($indexedColumns);
            $indexedColumns = array_values($indexedColumns);

            if (array_slice($indexedColumns, 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }

    private function dropIndex(string $table, string $name): void
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
