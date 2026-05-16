<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIntegrationMonitorsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('integration_monitors')) {
            return;
        }

        Schema::create('integration_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->unique();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('token_refreshed_at')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('integration_monitors');
    }
}
