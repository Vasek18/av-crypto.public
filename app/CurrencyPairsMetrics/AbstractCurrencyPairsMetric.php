<?php

namespace App\CurrencyPairsMetrics;

use Illuminate\Support\Facades\Redis;

// todo можно сделать getLast абстрактным динамическим и решить проблему разницы в параметрах через параметры конструктора
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

    /**
     * Получение метрик за период
     *
     * @param string $currencyPairCode
     * @param string $code
     * @param int $minutes
     * @param int $countFromTimestamp
     *
     * @return null|array
     */
    protected static function getFromDB($currencyPairCode, $code, $minutes, $countFromTimestamp = null)
    {
        // первый индекс
        $firstKey = -$minutes;

        // последний индекс
        $lastKey = -1;
        if ($countFromTimestamp) {
            $lastKey = ($countFromTimestamp - date('U')) / SECONDS_IN_MINUTE;
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

    // скорее всего нужен обратный метод для удаления вместо этого
    protected static function trimByIndexes($currencyPairCode, $metricCode, $start, $end = -1)
    {
        $length = static::countValues($currencyPairCode, $metricCode);
        // боремся с выходом за диапазон
        if ($length + $start < 0) {
            $start = 0;
        }

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
}