<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeMarket extends Model
{
    protected $table      = 'exchange_markets';
    protected $fillable   = [
        'name',
        'code',
        'url',
        'img_src',
        'for_tests',
        'sort',
    ];
    public    $timestamps = false;

    // связи с другими моделями
    public function accounts()
    {
        return $this->hasMany('App\Models\ExchangeMarketUserAccount');
    }

    public function currency_pairs()
    {
        return $this->hasMany('App\Models\ExchangeMarketCurrencyPair', 'exchange_market_id');
    }

    public function scopeVisibleForUser($query)
    {
        // админы видят все биржи
        if (auth()->user()->isAdmin()) {
            return $query;
        }

        // в тестовом режиме видно все биржи
        if (env('APP_ENV') == 'testing') {
            return $query;
        }

        // в обычном режиме обычным пользователям видны только рабочие биржи
        return $query->where('for_tests', false);
    }
}
