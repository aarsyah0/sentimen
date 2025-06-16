<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiMetric extends Model
{
    protected $table = 'kpi_metrics';
    protected $fillable = [
        'run_id','run_timestamp','accuracy','precision','recall','f1_score',
        'data_size','training_duration','class_distribution','per_class_metrics'
    ];

    protected $casts = [
        'run_timestamp'     => 'datetime',
        'class_distribution'=> 'array',
        'per_class_metrics' => 'array',
        'data_size'         => 'integer',
        'training_duration' => 'integer',
        'accuracy'          => 'decimal:4',
        'precision'         => 'decimal:4',
        'recall'            => 'decimal:4',
        'f1_score'          => 'decimal:4',

    ];
}
