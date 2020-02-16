<?php

namespace App\Listeners;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\Extremum;
use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\Events\CurrencyPairRateChanged;

class CalculateCurrencyPairMetrics
{
    public function handle(CurrencyPairRateChanged $event)
    {
        Average::calculate($event->currencyPairCode, $event->rate->timestamp);
        Extremum::calculate($event->currencyPairCode);
        Macd::calculate($event->currencyPairCode, $event->rate->timestamp);
        MacdAverage::calculate($event->currencyPairCode, $event->rate->timestamp);
    }
}