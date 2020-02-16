<?php

namespace App\CurrencyPairsMetrics;

use Illuminate\Support\Facades\Redis;

abstract class AbstractCurrencyPairsMetric implements Clearable
{
    abstract public static function clearOlderThan($currencyPairCode, $timestamp);

    // сохраняем значение метрик в редис лист, накапливая справа
    protected static function save($currencyPairCode, $code, $timestamp, $value)
    {
        Redis::rpush(
            $currencyPairCode.'.'.$code,
            serialize(
                [
                    'timestamp' => $timestamp,
                    'value'     => $value,
                ]
            )
        );
    }

    protected static function getAll($currencyPairCode, $code)
    {
        return static::getFromDB($currencyPairCode, $code, 0);
    }

    /**
     * Получение метрик за период
     *
     * @param string $currencyPairCode
     * @param string $code
     * @param int $countFromTail . 0 - Все
     * @param int $countFromTimestamp
     *
     * @return null|array
     */
    protected static function getFromDB($currencyPairCode, $code, $countFromTail, $countFromTimestamp = null)
    {
        // первый индекс
        $firstKey = -$countFromTail;

        // последний индекс
        $lastKey = -1;
        if ($countFromTimestamp) {
            $lastKey = floor(($countFromTimestamp - date('U')) / SECONDS_IN_MINUTE); // обязательно должен быть integer
            if ($lastKey > -1) {
                $lastKey = -1;
            }
        }

        $values = Redis::lrange(
            $currencyPairCode.'.'.$code,
            $firstKey,
            $lastKey
        );

        foreach ($values as $c => $value) {
            $values[$c] = unserialize($value);
        }

        return $values;
    }

    protected static function trimByIndexes($currencyPairCode, $metricCode, $start, $end = -1)
    {
        // если будут дроби, то попадём на "ERR value is not an integer or out of range"
        $start = ceil($start); // start - отрицательное число, поэтому ceil
        $end = floor($end);

        Redis::ltrim(
            $currencyPairCode.'.'.$metricCode,
            $start,
            $end
        );
    }

    protected static function countValues($currencyPairCode, $metricCode)
    {
        return Redis::llen($currencyPairCode.'.'.$metricCode);
    }

    protected static function deleteAllValues($currencyPairCode, $metricCode)
    {
        return Redis::del($currencyPairCode.'.'.$metricCode);

    }

    protected static function clearValuesOlderThan($currencyPairCode, $metricCode, $timestamp)
    {
        $timestampNow = date('U');
        $tooOldMetricsLifeInMinutes = ($timestampNow - $timestamp) / SECONDS_IN_MINUTE;

        if (!$tooOldMetricsLifeInMinutes) { // если получается 0, значит удаляем все значения
            return static::deleteAllValues($currencyPairCode, $metricCode);
        }

        $indexOtstup = -$tooOldMetricsLifeInMinutes; // минус, так как обрезаем, те что были до начала таймстемпа

        static::trimByIndexes(
            $currencyPairCode,
            $metricCode,
            $indexOtstup
        );
    }
}