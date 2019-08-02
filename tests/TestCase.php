<?php

namespace Tests;

use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    use CreatesApplication;

    protected function getCurrencyPair(
        $exchangeMarketCode = 'test',
        $currency1Code = 'BTC',
        $currency2Code = 'USD'
    ) {
        $exchangeMarket = ExchangeMarket::where('code', $exchangeMarketCode)->first();
        $currencyPair = ExchangeMarketCurrencyPair
            ::where('exchange_market_id', $exchangeMarket->id)
            ->where('currency_1_code', $currency1Code)
            ->where('currency_2_code', $currency2Code)
            ->first();

        return $currencyPair;
    }
}
