<?php

namespace App\CurrencyPairsMetrics;

class Spread extends AbstractCurrencyPairsMetric
{
    public static $code = 'spread';

    public static function store($currencyPairCode, $value, $timestamp)
    {
        static::save($currencyPairCode, static::$code, $timestamp, $value);
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        // считаем, что эта метрика тоже ежеминутная
        static::clearValuesOlderThan($currencyPairCode, static::$code, $timestamp);
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