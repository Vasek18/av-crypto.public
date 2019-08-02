<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyPairTrend extends Model
{
    protected $table    = 'currency_pair_trends';
    protected $fillable = [
        'currency_pair_id',
        'type',
        'lt_x',
        'lt_y',
        'lb_x',
        'lb_y',
        'rt_x',
        'rt_y',
        'rb_x',
        'rb_y',
    ];

    public $timestamps = false;

    // тренды, которые хоть одним концом входят в диапазон // todo кеширование
    public static function getForPeriod($currencyPairID, $timestampFrom, $timestampTo)
    {
        $trends = static::where('currency_pair_id', $currencyPairID)
            ->where(
                function ($q) use ($timestampTo) {
                    $q->where('lt_x', '<=', $timestampTo)
                        ->orWhere('lb_x', '<=', $timestampTo);
                }
            )
            ->where(
                function ($q) use ($timestampFrom) {
                    $q->where('rt_x', '>=', $timestampFrom)
                        ->orWhere('rb_x', '>=', $timestampFrom);
                }
            )
            ->get();

        return $trends;
    }
}
