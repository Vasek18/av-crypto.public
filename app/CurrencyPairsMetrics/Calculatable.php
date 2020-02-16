<?php

namespace App\CurrencyPairsMetrics;

interface Calculatable
{
    // точка входа для расчёта метрик
    public static function calculate($currencyPairCode, $currentTimestamp = '');
}