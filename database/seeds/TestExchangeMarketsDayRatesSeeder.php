<?php

use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

// наполняем бд тестовыми котировками на вчера и сегодня
class TestExchangeMarketsDayRatesSeeder extends Seeder
{

    /**
     * @return void
     */
    public function run()
    {
        $exchangeMarket = ExchangeMarket::where('code', 'test')->first();
        $currencyPair = ExchangeMarketCurrencyPair::where('exchange_market_id', $exchangeMarket->id)
            ->where('currency_1_code', 'BTC')
            ->where('currency_2_code', 'USD')
            ->first();

        $buyMin = 9000;
        $buyMax = 10000;
        $buySellOffset = 100;

        $buy = $buyMin;
        $sell = $buyMin + $buySellOffset;

        $step = 10;
        $up = true;
        $startOfDayTimestamp = Carbon::yesterday()->startOfDay()->timestamp;
        $minutesPerDay = 24 * 60;
        $days = 2;
        for ($minutesAfterMidnight = 0; $minutesAfterMidnight <= $minutesPerDay * $days; $minutesAfterMidnight++) {
            if ($buy >= $buyMax) {
                $up = false;
            }
            if ($buy <= $buyMin) {
                $up = true;
            }

            if ($up) {
                $buy = $buy + $step;
                $sell = $sell + $step;
            } else {
                $buy = $buy - $step;
                $sell = $sell - $step;
            }

            $timestamp = $startOfDayTimestamp + $minutesAfterMidnight * SECONDS_IN_MINUTE;
            \App\Trading\CurrencyPairRate::save($currencyPair->code, $buy, $sell, $timestamp);
        }
    }
}
