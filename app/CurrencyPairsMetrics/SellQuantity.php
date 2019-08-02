<?php

namespace App\CurrencyPairsMetrics;

class SellQuantity extends AbstractCurrencyPairsMetric
{
    public static $code = 'sell_quantity';

    public static function store($currencyPairCode, $value, $timestamp)
    {
        static::save($currencyPairCode, static::$code, $timestamp, $value);
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        // считаем, что эта метрика тоже ежеминутная
        $timestampNow = date('U');
        $indexOtstup = ($timestampNow - $timestamp) / SECONDS_IN_MINUTE;

        static::trimByIndexes($currencyPairCode, static::$code, -$indexOtstup);
    }

    public static function getForPeriod($currencyPairCode, $minutes, $countFromTimestamp = null)
    {
        return parent::getFromDB(
            $currencyPairCode,
            static::$code,
            $minutes,
            $countFromTimestamp
        );
    }

    public static function count($currencyPairCode)
    {
        return parent::countValues(
            $currencyPairCode,
            static::$code
        );
    }
}