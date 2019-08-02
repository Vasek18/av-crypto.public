<?php

namespace App\Models;

use App\ExchangeMarkets\ExchangeMarketFabric;
use Illuminate\Database\Eloquent\Model;

class ExchangeMarketUserAccount extends Model
{

    protected $table    = 'exchange_market_user_accounts';
    protected $fillable = [
        'exchange_market_id',
        'user_id',
        'active',
        'public_key',
        'secret_key',
        'test',
    ];

    public function getBalances()
    {
        /** @var \App\ExchangeMarkets\ExchangeMarket $exmClass */
        $exmClass = ExchangeMarketFabric::get($this->exchange()->first()->code);

        return $exmClass->getBalances($this->public_key, $this->secret_key);
    }

    public function haveEnoughCurrencyAmount($currencyCode, $amount)
    {
        // учитываем уже созданные корзинки
        $amount += $this->getAmountInBaskets($currencyCode);

        $balances = $this->getBalances();
        foreach ($balances as $balance) {
            if ($balance['currency']->code == $currencyCode) {
                if ($balance['amount'] >= $amount) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAmountInBaskets($currencyCode)
    {
        // смотрим среди первых валют
        $currencyPairsIDs = ExchangeMarketCurrencyPair::where('currency_1_code', $currencyCode)->pluck('id')->toArray();
        $amountInBaskets = $this->baskets()
            ->active()
            ->whereIn('currency_pair_id', $currencyPairsIDs)
            ->where('next_action', SELL_ACTION_CODE)
            ->sum('currency_1_last_amount');

        // смотрим среди вторых валют
        $currencyPairsIDs = ExchangeMarketCurrencyPair::where('currency_2_code', $currencyCode)->pluck('id')->toArray();
        $amountInBaskets += $this->baskets()
            ->active()
            ->whereIn('currency_pair_id', $currencyPairsIDs)
            ->where('next_action', BUY_ACTION_CODE)
            ->sum('currency_1_last_amount');

        return $amountInBaskets;
    }

    // связи с другими моделями
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function exchange()
    {
        return $this->belongsTo('App\Models\ExchangeMarket', 'exchange_market_id');
    }

    public function baskets()
    {
        return $this->hasMany('App\Models\Basket', 'account_id');
    }
}
