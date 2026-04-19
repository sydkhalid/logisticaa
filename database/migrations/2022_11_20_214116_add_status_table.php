<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'mobileNo')) {
                $table->string('mobileNo')->nullable()->after('vehicleNo');
            }

            if (!Schema::hasColumn('vehicles', 'expireDate')) {
                $table->timestamp('expireDate')->nullable()->after('mobileNo');
            }

            if (!Schema::hasColumn('vehicles', 'simProvider')) {
                $table->string('simProvider')->nullable()->after('expireDate');
            }

            if (!Schema::hasColumn('vehicles', 'vehicleStatus')) {
                $table->integer('vehicleStatus')->nullable()->after('simProvider');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            //
        });
    }
}
