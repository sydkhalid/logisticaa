<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trackings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('lspId',255)->nullable();
            $table->string('lrNumber',255)->nullable();
            $table->string('lrStatus')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('location')->nullable();
            $table->string('pickUpDate')->nullable();
            $table->string('lrDate')->nullable();
            $table->string('actualDeliveredDate')->nullable();
            $table->string('edd')->nullable();
            $table->string('receiverName')->nullable();
            $table->string('deliveredToPerson')->nullable();
            $table->string('actualWeight')->nullable();
            $table->string('numberOfPackages')->nullable();
            $table->string('length')->nullable();
            $table->string('breadth')->nullable();
            $table->string('height')->nullable();
            $table->string('truckType')->nullable();
            $table->string('truckTonnage')->nullable();
            $table->string('vehicleNo')->nullable();
            $table->string('deliveryNotes')->nullable();
            $table->boolean('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trackings');
    }
}
