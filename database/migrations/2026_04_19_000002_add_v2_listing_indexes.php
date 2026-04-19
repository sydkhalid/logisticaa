<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddV2ListingIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('vehicles')) {
            if (Schema::hasColumn('vehicles', 'vehicleStatus') && Schema::hasColumn('vehicles', 'id')) {
                $this->addIndex('vehicles', 'vehicles_vehicle_status_id_index', ['vehicleStatus', 'id']);
            }

            if (Schema::hasColumn('vehicles', 'vehicleNo')) {
                $this->addIndex('vehicles', 'vehicles_vehicle_no_index', ['vehicleNo']);
            }
        }

        if (Schema::hasTable('trackings')) {
            if (Schema::hasColumn('trackings', 'status') && Schema::hasColumn('trackings', 'id')) {
                $this->addIndex('trackings', 'trackings_status_id_index', ['status', 'id']);
            }

            if (Schema::hasColumn('trackings', 'vehicleNo')) {
                $this->addIndex('trackings', 'trackings_vehicle_no_index', ['vehicleNo']);
            }

            if (Schema::hasColumn('trackings', 'lspId')) {
                $this->addIndex('trackings', 'trackings_lsp_id_index', ['lspId']);
            }

            if (Schema::hasColumn('trackings', 'lrNumber')) {
                $this->addIndex('trackings', 'trackings_lr_number_index', ['lrNumber']);
            }
        }

        if (Schema::hasTable('epods')) {
            if (Schema::hasColumn('epods', 'status') && Schema::hasColumn('epods', 'id')) {
                $this->addIndex('epods', 'epods_status_id_index', ['status', 'id']);
            }

            if (Schema::hasColumn('epods', 'lspId')) {
                $this->addIndex('epods', 'epods_lsp_id_index', ['lspId']);
            }

            if (Schema::hasColumn('epods', 'lrNumber')) {
                $this->addIndex('epods', 'epods_lr_number_index', ['lrNumber']);
            }
        }

        if (Schema::hasTable('weights')) {
            if (Schema::hasColumn('weights', 'lspId')) {
                $this->addIndex('weights', 'weights_lsp_id_index', ['lspId']);
            }

            if (Schema::hasColumn('weights', 'lrNumber')) {
                $this->addIndex('weights', 'weights_lr_number_index', ['lrNumber']);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropIndex('vehicles', 'vehicles_vehicle_status_id_index');
        $this->dropIndex('vehicles', 'vehicles_vehicle_no_index');
        $this->dropIndex('trackings', 'trackings_status_id_index');
        $this->dropIndex('trackings', 'trackings_vehicle_no_index');
        $this->dropIndex('trackings', 'trackings_lsp_id_index');
        $this->dropIndex('trackings', 'trackings_lr_number_index');
        $this->dropIndex('epods', 'epods_status_id_index');
        $this->dropIndex('epods', 'epods_lsp_id_index');
        $this->dropIndex('epods', 'epods_lr_number_index');
        $this->dropIndex('weights', 'weights_lsp_id_index');
        $this->dropIndex('weights', 'weights_lr_number_index');
    }

    /**
     * @param  string  $table
     * @param  string  $name
     * @param  array  $columns
     * @return void
     */
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

    /**
     * @param  string  $table
     * @param  string  $name
     * @return void
     */
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
