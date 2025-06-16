<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpiMetricsTable extends Migration
{
    public function up()
    {
        Schema::create('kpi_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('run_id')->unique()->comment('Identifikasi unik run, misal run_Senin_20250612_01');
            $table->dateTime('run_timestamp')->comment('Waktu run/training dijalankan');
            // kolom metrik, bisa disesuaikan nama header dalam evaluation_metrics.csv
            $table->decimal('accuracy', 8, 4)->nullable();
            $table->decimal('precision', 8, 4)->nullable();
            $table->decimal('recall', 8, 4)->nullable();
            $table->decimal('f1_score', 8, 4)->nullable();
            // jika ada metrik lain, tambahkan di sini, misalnya macro/micro atau per-class?
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_metrics');
    }
}
