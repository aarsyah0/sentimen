<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToKpiMetricsTable extends Migration
{
    public function up()
    {
        Schema::table('kpi_metrics', function (Blueprint $table) {
            $table->integer('data_size')->nullable()->comment('Jumlah baris data training');
            $table->integer('training_duration')->nullable()->comment('Durasi training dalam detik');
            $table->json('class_distribution')->nullable()->comment('Distribusi label training, JSON');
            $table->json('per_class_metrics')->nullable()->comment('Metrik per kelas, JSON');
            $table->decimal('auc', 8, 4)->nullable()->comment('AUC jika tersedia');
            $table->decimal('loss', 10, 4)->nullable()->comment('Loss akhir jika tersedia');
            // Jika ingin inference latency later, bisa ditambah di tabel lain atau di sini:
            // $table->decimal('inference_latency', 8,4)->nullable()->comment('Rata-rata latency inference (detik)');
        });
    }

    public function down()
    {
        Schema::table('kpi_metrics', function (Blueprint $table) {
            $table->dropColumn(['data_size','training_duration','class_distribution','per_class_metrics','auc','loss']);
        });
    }
}
