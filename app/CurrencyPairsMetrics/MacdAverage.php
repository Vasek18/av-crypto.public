<?php

namespace App\CurrencyPairsMetrics;

class MacdAverage extends AbstractCurrencyPairsMetric implements Calculatable
{
    public static $hourIntervals = [1];

    public static function calculate($currencyPairID, $currencyPairCode, $currentTimestamp = '')
    {
        // идти по каждой паре
        foreach (Macd::getAllAveragePairsPeriods() as list($hourIntervalFastAvg, $hourIntervalSlowAvg)) {
            foreach (static::$hourIntervals as $hourInterval) { // для каждого периода, по которому рассчитываем среднее
                $values = Macd::getForPeriod(
                    $currencyPairCode,
                    $hourIntervalFastAvg,
                    $hourIntervalSlowAvg,
                    $hourInterval * MINUTES_IN_HOUR
                );
                if (!empty($values)) {
                    $valuesSum = array_sum(array_column($values, 'value'));
                    $average = $valuesSum / count($values);

                    static::store(
                        $currencyPairCode,
                        $hourIntervalFastAvg,
                        $hourIntervalSlowAvg,
                        $hourInterval,
                        $currentTimestamp,
                        $average
                    );
                }
            }
        }
    }

    public static function store(
        $currencyPairCode,
        int $fastAvgInterval,
        int $slowAvgInterval,
        int $hourInterval,
        int $timestamp,
        $value
    ) {
        static::save(
            $currencyPairCode,
            'macd_avg_'.$fastAvgInterval.'_'.$slowAvgInterval.'_'.$hourInterval,
            $timestamp,
            $value
        );
    }

    public static function getForPeriod(
        $currencyPairCode,
        int $fastAvgInterval,
        int $slowAvgInterval,
        int $macdAvgInterval,
        $minutes,
        $countFromTimestamp = null
    ) {
        return parent::getFromDB(
            $currencyPairCode,
            'macd_avg_'.$fastAvgInterval.'_'.$slowAvgInterval.'_'.$macdAvgInterval,
            $minutes,
            $countFromTimestamp
        );
    }

    public static function getLast($currencyPairCode, int $fastAvgInterval, int $slowAvgInterval, int $macdAvgInterval)
    {
        $values = static::getForPeriod($currencyPairCode, $fastAvgInterval, $slowAvgInterval, $macdAvgInterval, 1);

        return !empty($values) ? $values[0] : null;
    }

    public static function getFirst($currencyPairCode, int $fastAvgInterval, int $slowAvgInterval, int $macdAvgInterval)
    {
        $values = static::getForPeriod($currencyPairCode, $fastAvgInterval, $slowAvgInterval, $macdAvgInterval, 1);

        return !empty($values) ? $values[0] : null;
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        $timestampNow = date('U');
        $indexOtstup = ($timestampNow - $timestamp) / SECONDS_IN_MINUTE;

        foreach (Macd::getAllAveragePairsPeriods(
        ) as list($hourIntervalFastAvg, $hourIntervalSlowAvg)) { // для каждого macd
            foreach (static::$hourIntervals as $hourInterval) { // для каждого периода, по которому рассчитываем среднее
                static::trimByIndexes(
                    $currencyPairCode,
                    'macd_avg_'.$hourIntervalFastAvg.'_'.$hourIntervalSlowAvg.'_'.$hourInterval,
                    -$indexOtstup
                );
            }
        }
    }
}