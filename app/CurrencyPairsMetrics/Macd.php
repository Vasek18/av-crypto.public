<?php

namespace App\CurrencyPairsMetrics;

class Macd extends AbstractCurrencyPairsMetric implements Calculatable
{
    private static function getCode($fastAvgInterval, $slowAvgInterval)
    {
        return 'macd_'.$fastAvgInterval.'_'.$slowAvgInterval;
    }

    // высчитываем только для цены покупки
    public static function calculate($currencyPairCode, $currentTimestamp = '')
    {
        foreach (static::getAllAveragePeriodsPairs() as list($hourIntervalFast, $hourIntervalSlow)) {
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
        static::save($currencyPairCode, static::getCode($fastAvgInterval, $slowAvgInterval), $timestamp, $value);
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
            static::getCode($fastAvgInterval, $slowAvgInterval),
            $minutes,
            $countFromTimestamp
        );
    }

    public static function getLast($currencyPairCode, int $fastAvgInterval, int $slowAvgInterval)
    {
        $values = static::getForPeriod($currencyPairCode, $fastAvgInterval, $slowAvgInterval, 1);

        return !empty($values) ? $values[0] : null;
    }

    public static function getAllAveragePeriodsPairs()
    {
        $periods = [];
        $hourIntervals = Average::$hourIntervals;

        for ($i = 0; $i < count($hourIntervals) - 1; $i++) { // идём по всем диапазонам средних, кроме последнего
            for ($j = $i + 1; $j <= count($hourIntervals) - 1; $j++) { // по всем диапазонам выше, чем в первом цикле
                $periods[] = [$hourIntervals[$i], $hourIntervals[$j]];
            }
        }

        return $periods;
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        foreach (static::getAllAveragePeriodsPairs() as list($hourIntervalFast, $hourIntervalSlow)) {
            static::clearValuesOlderThan(
                $currencyPairCode,
                static::getCode($hourIntervalFast, $hourIntervalSlow),
                $timestamp
            );
        }
    }
}