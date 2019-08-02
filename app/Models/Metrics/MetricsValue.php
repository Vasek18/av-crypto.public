<?php

namespace App\Models\Metrics;

use Illuminate\Database\Eloquent\Model;

class MetricsValue extends Model{

    protected $table = 'metrics_values';
    public $timestamps = false;
    protected $fillable = [
        'metrics_id',
        'timestamp',
        'value',
        'counter',
    ];

    // свойства вне бд
    function getDayAttribute(){
        return date('d.m.Y', $this->timestamp);
    }
    // .свойства вне бд

    // связи с другими моделями
    public function metrics(){
        return $this->belongsTo('App\Models\Metrics\Metrics');
    }
}
