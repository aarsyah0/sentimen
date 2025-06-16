<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VersionKpiMetric extends Model
{
    protected $fillable = [
        'run_id',
        'version',
        'count_positive',
        'count_negative',
        'count_neutral',
        'total',
        'pct_positive',
        'pct_negative',
        'pct_neutral',
    ];
}
