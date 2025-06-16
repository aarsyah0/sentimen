<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('kpi_metrics', function (Blueprint $table) {
        $table->dropColumn(['auc', 'loss']);
    });
}

public function down()
{
    Schema::table('kpi_metrics', function (Blueprint $table) {
        $table->float('auc')->nullable();
        $table->float('loss')->nullable();
    });
}

};
