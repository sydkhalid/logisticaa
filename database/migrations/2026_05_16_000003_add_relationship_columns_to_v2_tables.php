<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRelationshipColumnsToV2Tables extends Migration
{
    public function up()
    {
        Schema::table('trackings', function (Blueprint $table) {
            if (!Schema::hasColumn('trackings', 'vehicle_id')) {
                $table->unsignedBigInteger('vehicle_id')->nullable()->after('vehicleNo');
                $table->index('vehicle_id', 'trackings_vehicle_id_index');
                $table->foreign('vehicle_id', 'trackings_vehicle_id_foreign')
                    ->references('id')
                    ->on('vehicles')
                    ->nullOnDelete();
            }
        });

        Schema::table('epods', function (Blueprint $table) {
            if (!Schema::hasColumn('epods', 'tracking_id')) {
                $table->unsignedInteger('tracking_id')->nullable()->after('id');
                $table->index('tracking_id', 'epods_tracking_id_index');
                $table->foreign('tracking_id', 'epods_tracking_id_foreign')
                    ->references('id')
                    ->on('trackings')
                    ->nullOnDelete();
            }
        });

        Schema::table('weights', function (Blueprint $table) {
            if (!Schema::hasColumn('weights', 'tracking_id')) {
                $table->unsignedInteger('tracking_id')->nullable()->after('id');
                $table->index('tracking_id', 'weights_tracking_id_index');
                $table->foreign('tracking_id', 'weights_tracking_id_foreign')
                    ->references('id')
                    ->on('trackings')
                    ->nullOnDelete();
            }
        });

        $this->backfillTrackingVehicles();
        $this->backfillEpodTrackings();
        $this->backfillWeightTrackings();
    }

    public function down()
    {
        Schema::table('weights', function (Blueprint $table) {
            if (Schema::hasColumn('weights', 'tracking_id')) {
                $table->dropForeign('weights_tracking_id_foreign');
                $table->dropIndex('weights_tracking_id_index');
                $table->dropColumn('tracking_id');
            }
        });

        Schema::table('epods', function (Blueprint $table) {
            if (Schema::hasColumn('epods', 'tracking_id')) {
                $table->dropForeign('epods_tracking_id_foreign');
                $table->dropIndex('epods_tracking_id_index');
                $table->dropColumn('tracking_id');
            }
        });

        Schema::table('trackings', function (Blueprint $table) {
            if (Schema::hasColumn('trackings', 'vehicle_id')) {
                $table->dropForeign('trackings_vehicle_id_foreign');
                $table->dropIndex('trackings_vehicle_id_index');
                $table->dropColumn('vehicle_id');
            }
        });
    }

    private function backfillTrackingVehicles(): void
    {
        DB::table('trackings')
            ->whereNull('vehicle_id')
            ->whereNotNull('vehicleNo')
            ->orderBy('id')
            ->chunkById(500, function ($trackings) {
                foreach ($trackings as $tracking) {
                    $vehicleId = DB::table('vehicles')
                        ->where('vehicleNo', $tracking->vehicleNo)
                        ->value('id');

                    if ($vehicleId) {
                        DB::table('trackings')
                            ->where('id', $tracking->id)
                            ->update(['vehicle_id' => $vehicleId]);
                    }
                }
            });
    }

    private function backfillEpodTrackings(): void
    {
        DB::table('epods')
            ->whereNull('tracking_id')
            ->whereNotNull('lrNumber')
            ->orderBy('id')
            ->chunkById(500, function ($epods) {
                foreach ($epods as $epod) {
                    $trackingId = DB::table('trackings')
                        ->where('lspId', $epod->lspId)
                        ->where('lrNumber', $epod->lrNumber)
                        ->orderByDesc('id')
                        ->value('id');

                    if ($trackingId) {
                        DB::table('epods')
                            ->where('id', $epod->id)
                            ->update(['tracking_id' => $trackingId]);
                    }
                }
            });
    }

    private function backfillWeightTrackings(): void
    {
        DB::table('weights')
            ->whereNull('tracking_id')
            ->whereNotNull('lrNumber')
            ->orderBy('id')
            ->chunkById(500, function ($weights) {
                foreach ($weights as $weight) {
                    $trackingId = DB::table('trackings')
                        ->where('lspId', $weight->lspId)
                        ->where('lrNumber', $weight->lrNumber)
                        ->orderByDesc('id')
                        ->value('id');

                    if ($trackingId) {
                        DB::table('weights')
                            ->where('id', $weight->id)
                            ->update(['tracking_id' => $trackingId]);
                    }
                }
            });
    }
}
