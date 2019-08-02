<?php

namespace App\CurrencyPairsMetrics;

interface Calculatable
{
    // точка входа для расчёта метрик // todo $currencyPairID не нужен никому, кроме тренда
    public static function calculate($currencyPairID, $currencyPairCode, $currentTimestamp = '');
}