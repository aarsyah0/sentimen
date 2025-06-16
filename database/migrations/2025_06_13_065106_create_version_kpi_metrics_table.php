<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVersionKpiMetricsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('version_kpi_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('run_id');
            $table->string('version'); // e.g., "iOS 15"
            $table->integer('count_positive')->default(0);
            $table->integer('count_negative')->default(0);
            $table->integer('count_neutral')->default(0);
            $table->integer('total')->default(0);
            $table->decimal('pct_positive', 5, 2)->nullable();
            $table->decimal('pct_negative', 5, 2)->nullable();
            $table->decimal('pct_neutral', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('version_kpi_metrics');
    }
}
