<?php

namespace App\CurrencyPairsMetrics;

use App\Trading\CurrencyPairRate;

class Extremum extends AbstractCurrencyPairsMetric implements Calculatable
{
    public static $countOneSidePointsForCompare = 30;

    // проверяем одну точку за раз
    public static function calculate($currencyPairID, $currencyPairCode, $currentTimestamp = '')
    {
        $ratesCount = static::$countOneSidePointsForCompare * 2 + 1; // +1 потому что кроме левых и правых групп нам нужна ещё и котировка, которую проверяем
        $rates = CurrencyPairRate::getForPeriod($currencyPairCode, $ratesCount);
        if (count($rates) < $ratesCount) {
            return false;
        }

        // проверяем, что котировка $countOneSidePointsForCompare минут назад является максимумом или минимумом
        $testedRate = $rates[static::$countOneSidePointsForCompare];
        $maximum = $testedRate['sell_price'];
        $minimum = $testedRate['buy_price'];
        for ($i = 0; $i < static::$countOneSidePointsForCompare; $i++) {
            $rateFromLeft = $rates[$i];
            $rateFromRight = $rates[static::$countOneSidePointsForCompare * 2 - $i];
            if ($rateFromLeft['sell_price'] >= $testedRate['sell_price'] || $testedRate['sell_price'] <= $rateFromRight['sell_price']) {
                $maximum = false;
            }
            if ($rateFromLeft['buy_price'] <= $testedRate['buy_price'] || $testedRate['buy_price'] >= $rateFromRight['buy_price']) {
                $minimum = false;
            }
        }

        // сохраняем
        if ($maximum) {
            static::store($currencyPairCode, 'maximum', $testedRate['timestamp'], $maximum);
        }
        if ($minimum) {
            static::store($currencyPairCode, 'minimum', $testedRate['timestamp'], $minimum);
        }
    }

    public static function store($currencyPairCode, $type, $timestamp, $value)
    {
        static::save(
            $currencyPairCode,
            $type.'_'.static::$countOneSidePointsForCompare,
            $timestamp,
            $value
        );
    }

    public static function getMaximumsForPeriod($currencyPairCode, $minutes, $countFromTimestamp = null)
    {
        return static::getForPeriod(
            $currencyPairCode,
            'maximum',
            $minutes,
            $countFromTimestamp
        );
    }

    public static function getMinimumsForPeriod($currencyPairCode, $minutes, $countFromTimestamp = null)
    {
        return static::getForPeriod(
            $currencyPairCode,
            'minimum',
            $minutes,
            $countFromTimestamp
        );
    }

    protected static function getForPeriod($currencyPairCode, $type, $minutes, $countFromTimestamp = null)
    {
        $code = $type.'_'.static::$countOneSidePointsForCompare;

        if ($minutes === 1) { // только последний
            return static::getFromDB($currencyPairCode, $code, 1);
        }

        $values = static::getFromDB(
            $currencyPairCode,
            $code,
            0
        );

        // обрезаем лишние значения, которые были до начала периода
        $currentTimestamp = date('U');
        foreach ($values as $c => $value) {
            if ($value['timestamp'] + $minutes * SECONDS_IN_MINUTE < $currentTimestamp) {
                unset($values[$c]);
            } else {
                // список полностью входит в диапазон
                break;
            }
        }
        $values = array_values($values); // сбрасываем порядок ключей
        // обрезаем лишние значения, которые были после конца периода
        if ($countFromTimestamp) {
            foreach (array_reverse($values) as $c => $value) {
                if ($value['timestamp'] > $countFromTimestamp) {
                    unset($values[$c]);
                } else {
                    // список полностью входит в диапазон
                    break;
                }
            }
            $values = array_values($values); // сбрасываем порядок ключей
        }

        return $values;
    }

    public static function getLast($currencyPairCode, $type)
    {
        $values = static::getForPeriod(
            $currencyPairCode,
            $type,
            1
        );

        return count($values) ? $values[0] : null;
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        // за день должно набегать не очень много экстремумов и их разделение по дням довольно сложное, поэтому мы тут просто убираем по одному элементу слева
        foreach (['minimum', 'maximum'] as $type) {
            $metricCode = $type.'_'.static::$countOneSidePointsForCompare;
            $metrics = static::getFromDB($currencyPairCode, $metricCode, 0);
            foreach ($metrics as $metric) {
                $metricTimestamp = $metric['timestamp'];

                if ($metricTimestamp <= $timestamp) {
                    static::trimByIndexes($currencyPairCode, $metricCode, 1, -1);
                } else { // дошли до нужной границы - сохраняем обратно и выходим
                    break;
                }
            }
        }
    }
}