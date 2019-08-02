<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ExchangeMarketCurrencyPair extends Model
{
    protected $table      = 'exchange_markets_currency_pairs';
    protected $fillable   = [
        'currency_1_code',
        'currency_2_code',
        'exchange_market_id',
        'currency_1_min_amount',
        'currency_1_max_amount',
        'min_price',
        'max_price',
        'currency_2_max_amount',
        'currency_2_min_amount',
        'commission_percents',
        'active',
    ];
    public    $timestamps = false;

    // виртуальные аттрибуты
    public function getCodeAttribute()
    {
        // todo нужно ли тут кеширование? Этот метод много где по сайту вызывается
        return $this->exchange_market->code.'.'.$this->currency_1_code.'.'.$this->currency_2_code;
    }

    public static function getCodeByID($id)
    {
        $code = Cache::remember(
            'currency_'.$id.'_code',
            now()->addDay(),
            function () use ($id) {
                $pair = ExchangeMarketCurrencyPair::where('id', $id)->first();

                return $pair->code;
            }
        );

        return $code;
    }

    // связи с другими моделями
    public function exchange_market()
    {
        return $this->belongsTo('App\Models\ExchangeMarket', 'exchange_market_id');
    }

    // области видимости
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeInActive($query)
    {
        return $query->where('active', false);
    }
}
