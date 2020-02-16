<?php

namespace App\CurrencyPairsMetrics;

class MacdAverage extends AbstractCurrencyPairsMetric implements Calculatable
{
    public static $hourIntervals = [0.5, 1, 2, 4, 12];

    private static function getCode($fastAvgInterval, $slowAvgInterval, $calculationInterval)
    {
        return 'macd_avg_'.$fastAvgInterval.'_'.$slowAvgInterval.'_'.$calculationInterval;
    }

    public static function calculate($currencyPairCode, $currentTimestamp = '')
    {
        // идти по каждой паре
        foreach (Macd::getAllAveragePeriodsPairs() as list($hourIntervalFastAvg, $hourIntervalSlowAvg)) {
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
        int $calculationInterval,
        int $timestamp,
        $value
    ) {
        static::save(
            $currencyPairCode,
            static::getCode($fastAvgInterval, $slowAvgInterval, $calculationInterval),
            $timestamp,
            $value
        );
    }

    public static function getForPeriod(
        $currencyPairCode,
        int $fastAvgInterval,
        int $slowAvgInterval,
        int $calculationInterval,
        $minutes,
        $countFromTimestamp = null
    ) {
        return parent::getFromDB(
            $currencyPairCode,
            static::getCode($fastAvgInterval, $slowAvgInterval, $calculationInterval),
            $minutes,
            $countFromTimestamp
        );
    }

    public static function getLast($currencyPairCode, int $fastAvgInterval, int $slowAvgInterval, int $macdAvgInterval)
    {
        $values = static::getForPeriod($currencyPairCode, $fastAvgInterval, $slowAvgInterval, $macdAvgInterval, 1);

        return !empty($values) ? $values[0] : null;
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        foreach (Macd::getAllAveragePeriodsPairs() as list($hourIntervalFast, $hourIntervalSlow)) { // для каждого macd
            foreach (static::$hourIntervals as $hourIntervalAvg) { // для каждого периода, по которому рассчитываем среднее
                static::clearValuesOlderThan(
                    $currencyPairCode,
                    static::getCode($hourIntervalFast, $hourIntervalSlow, $hourIntervalAvg),
                    $timestamp
                );
            }
        }
    }
}