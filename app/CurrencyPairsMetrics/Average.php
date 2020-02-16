<?php

namespace App\CurrencyPairsMetrics;

use App\Trading\CurrencyPairRate;

class Average extends AbstractCurrencyPairsMetric implements Calculatable
{
    public static $hourIntervals = [0.5, 1, 2, 4, 6, 8, 12, 24];

    // имхо нет выгоды от объявления этого метода абстрактным из-за необходимости работы с инстансами класса
    private static function getCode($type, $interval)
    {
        return 'avg_'.$type.'_'.$interval;
    }

    public static function calculate($currencyPairCode, $currentTimestamp = '')
    {
        foreach (static::$hourIntervals as $hourInterval) {
            list($buyAverage, $sellAverage) = static::getAveragesForPeriod($currencyPairCode, $hourInterval);

            if ($buyAverage) {
                static::store($currencyPairCode, 'buy', $hourInterval, $currentTimestamp, $buyAverage);
            }
            if ($sellAverage) {
                static::store($currencyPairCode, 'sell', $hourInterval, $currentTimestamp, $sellAverage);
            }
        }
    }

    public static function store($currencyPairCode, $type, int $interval, $timestamp, $value)
    {
        static::save($currencyPairCode, static::getCode($type, $interval), $timestamp, $value);
    }

    public static function getForPeriod($currencyPairCode, $type, int $interval, $minutes, $countFromTimestamp = null)
    {
        if ($type !== 'buy' && $type !== 'sell') {
            return false; // хотя тут можно и эксепшион кидать
        }

        return parent::getFromDB(
            $currencyPairCode,
            static::getCode($type, $interval),
            $minutes,
            $countFromTimestamp
        );
    }

    public static function getLast($currencyPairCode, $type, int $interval)
    {
        $values = static::getForPeriod($currencyPairCode, $type, $interval, 1);

        return !empty($values) ? $values[0] : null;
    }

    protected static function getAveragesForPeriod($currencyPairCode, $hourInterval)
    {
        $ratesCount = $hourInterval * MINUTES_IN_HOUR;
        $rates = CurrencyPairRate::getForPeriod($currencyPairCode, $ratesCount);

        // если произошёл сбой кеширования и нужное количество котировок не набирается, то не считаем средние
        if (count($rates) < $ratesCount) {
            return [false, false];
        }

        $buySum = 0;
        $sellSum = 0;
        /** @var CurrencyPairRate $rate */
        foreach ($rates as $rate) {
            $buySum += $rate->buy_price;
            $sellSum += $rate->sell_price;
        }

        return [$buySum / $ratesCount, $sellSum / $ratesCount];
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        foreach (static::getAllCodes() as $code) {
            static::clearValuesOlderThan(
                $currencyPairCode,
                $code,
                $timestamp
            );
        }
    }

    protected static function getAllCodes()
    {
        $codes = [];
        foreach (static::$hourIntervals as $hourInterval) {
            foreach (['sell', 'buy'] as $type) {
                $codes[] = static::getCode($type, $hourInterval);
            }
        }

        return $codes;
    }
}