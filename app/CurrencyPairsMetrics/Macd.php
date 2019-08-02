<?php

namespace App\CurrencyPairsMetrics;

class Macd extends AbstractCurrencyPairsMetric implements Calculatable
{
    // высчитываем только для цены покупки
    public static function calculate($currencyPairID, $currencyPairCode, $currentTimestamp = '')
    {
        foreach (static::getAllAveragePairsPeriods() as list($hourIntervalFast, $hourIntervalSlow)) {
            $fastAverageValue = Average::getLast(
                $currencyPairCode,
                'buy',
                $hourIntervalFast
            )['value'];
            $slowAverageValue = Average::getLast(
                $currencyPairCode,
                'buy',
                $hourIntervalSlow
            )['value'];

            // высчитываем и сохраняем macd
            $metricValue = $fastAverageValue - $slowAverageValue;

            static::store($currencyPairCode, $hourIntervalFast, $hourIntervalSlow, $currentTimestamp, $metricValue);
        }
    }

    public static function store($currencyPairCode, int $fastAvgInterval, int $slowAvgInterval, int $timestamp, $value)
    {
        static::save($currencyPairCode, 'macd_'.$fastAvgInterval.'_'.$slowAvgInterval, $timestamp, $value);
    }

    public static function getForPeriod(
        $currencyPairCode,
        int $fastAvgInterval,
        int $slowAvgInterval,
        $minutes,
        $countFromTimestamp = null
    ) {
        return parent::getFromDB(
            $currencyPairCode,
            'macd_'.$fastAvgInterval.'_'.$slowAvgInterval,
            $minutes,
            $countFromTimestamp
        );
    }

    public static function getLast($currencyPairCode, int $fastAvgInterval, int $slowAvgInterval)
    {
        $values = static::getForPeriod($currencyPairCode, $fastAvgInterval, $slowAvgInterval, 1);

        return !empty($values) ? $values[0] : null;
    }

    public static function getAllAveragePairsPeriods()
    {
        $periods = [];
        $hourIntervals = Average::$hourIntervals;

        for ($i = 0; $i < count($hourIntervals) - 1; $i++) { // идём по всем диапазонам средних, кроме последнего
            for ($j = count($hourIntervals) - 1; $j > $i; $j--) { // по всем диапазонам выше, чем в первом цикле
                $periods[] = [$hourIntervals[$i], $hourIntervals[$j]];
            }
        }

        return $periods;
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        $timestampNow = date('U');
        $indexOtstup = ($timestampNow - $timestamp) / SECONDS_IN_MINUTE;

        foreach (static::getAllAveragePairsPeriods() as list($hourIntervalFast, $hourIntervalSlow)) {
            static::trimByIndexes(
                $currencyPairCode,
                'macd_'.$hourIntervalFast.'_'.$hourIntervalSlow,
                -$indexOtstup
            );
        }
    }
}