<?php

namespace App\CurrencyPairsMetrics;

interface Clearable
{
    public static function clearOlderThan($currencyPairCode, $timestamp);
}