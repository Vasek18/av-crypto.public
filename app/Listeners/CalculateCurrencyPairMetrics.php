<?php

namespace App\Listeners;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\Extremum;
use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\CurrencyPairsMetrics\Trend;
use App\Events\CurrencyPairRateChanged;

class CalculateCurrencyPairMetrics
{
    public function handle(CurrencyPairRateChanged $event)
    {
        Average::calculate($event->currencyPairID, $event->currencyPairCode, $event->rate->timestamp);
        Extremum::calculate(
            $event->currencyPairID,
            $event->currencyPairCode
        ); // этот расчёт должен идти перед расчётом трендов
        Trend::calculate($event->currencyPairID, $event->currencyPairCode);
        Macd::calculate($event->currencyPairID, $event->currencyPairCode, $event->rate->timestamp);
        MacdAverage::calculate($event->currencyPairID, $event->currencyPairCode, $event->rate->timestamp);
    }
}