<?php

namespace App\CurrencyPairsMetrics;

use App\Trading\CurrencyPairRate;

class Extremum extends AbstractNotEveryMinuteCurrencyPairsMetric implements Calculatable
{
    public static  $countOneSidePointsForCompare = 30;
    private static $maximumType                  = 'maximum';
    private static $minimumType                  = 'minimum';

    private static function getCode($type)
    {
        return $type.'_'.static::$countOneSidePointsForCompare;
    }

    // проверяем одну точку за раз
    public static function calculate($currencyPairCode, $currentTimestamp = '')
    {
        $ratesCount = static::$countOneSidePointsForCompare * 2 + 1; // +1 потому что кроме левых и правых групп нам нужна ещё и котировка, которую проверяем
        $rates = CurrencyPairRate::getForPeriod($currencyPairCode, $ratesCount);
        if (count($rates) < $ratesCount) {
            return false;
        }

        // проверяем, что котировка $countOneSidePointsForCompare минут назад является максимумом или минимумом
        /** @var CurrencyPairRate $testedRate */
        $testedRate = $rates[static::$countOneSidePointsForCompare];
        $maximum = $testedRate->sell_price;
        $minimum = $testedRate->buy_price;
        for ($i = 0; $i < static::$countOneSidePointsForCompare && ($maximum || $minimum); $i++) {
            /** @var CurrencyPairRate $rateFromLeft */
            $rateFromLeft = $rates[$i];
            /** @var CurrencyPairRate $rateFromRight */
            $rateFromRight = $rates[static::$countOneSidePointsForCompare * 2 - $i];
            if ($rateFromLeft->sell_price >= $testedRate->sell_price || $testedRate->sell_price <= $rateFromRight->sell_price) {
                $maximum = false;
            }
            if ($rateFromLeft->buy_price <= $testedRate->buy_price || $testedRate->buy_price >= $rateFromRight->buy_price) {
                $minimum = false;
            }
        }

        // сохраняем
        if ($maximum) {
            static::store($currencyPairCode, static::$maximumType, $testedRate->timestamp, $maximum);
        }
        if ($minimum) {
            static::store($currencyPairCode, static::$minimumType, $testedRate->timestamp, $minimum);
        }
    }

    public static function store($currencyPairCode, $type, $timestamp, $value)
    {
        static::save(
            $currencyPairCode,
            static::getCode($type),
            $timestamp,
            $value
        );
    }

    public static function getMaximumsForPeriod($currencyPairCode, $minutes, $countFromTimestamp = null)
    {
        return static::getForPeriod(
            $currencyPairCode,
            static::$maximumType,
            $minutes,
            $countFromTimestamp
        );
    }

    public static function getMinimumsForPeriod($currencyPairCode, $minutes, $countFromTimestamp = null)
    {
        return static::getForPeriod(
            $currencyPairCode,
            static::$minimumType,
            $minutes,
            $countFromTimestamp
        );
    }

    protected static function getForPeriod($currencyPairCode, $type, $minutes, $countFromTimestamp = null)
    {
        $code = static::getCode($type);

        return static::getValuesForPeriod($currencyPairCode, $code, $minutes, $countFromTimestamp);
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
        foreach ([static::$maximumType, static::$minimumType] as $type) {
            $metricCode = static::getCode($type);
            static::clearValuesOlderThan($currencyPairCode, $metricCode, $timestamp);
        }
    }
}