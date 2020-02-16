<?php

namespace App\CurrencyPairsMetrics;

// функции сделаны статичными, а не динамичными с получением параметров для генерации кода в конструкторе, так как методы на подобие getForPeriod должны быть статичными и со своими параметрами у разных сущностей
abstract class AbstractNotEveryMinuteCurrencyPairsMetric extends AbstractCurrencyPairsMetric
{
    protected static function getThisMinuteValue($currencyPairCode, $metricCode)
    {
        $value = static::getFromDB($currencyPairCode, $metricCode, 1);

        if (empty($value) || empty($value[0])) {
            return null;
        }

        if ((date('U') - $value[0]['timestamp']) <= SECONDS_IN_MINUTE) {
            return $value[0];
        }

        return null;
    }

    protected static function getValuesForPeriod(
        $currencyPairCode,
        $metricCode,
        $minutes = 0,
        $countFromTimestamp = null
    ) {
        if ($minutes === 1) { // только последний
            return static::getFromDB($currencyPairCode, $metricCode, 1);
        }

        $values = static::getFromDB(
            $currencyPairCode,
            $metricCode,
            $minutes // за одну минуту может быть максимум 1 значение. Следовательно мы сразу можем ограничить количество минимум до количества минут
        );

        if (!$minutes) {
            return $values;
        }

        if ($minutes) {
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
        }

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
        }

        $values = array_values($values); // сбрасываем порядок ключей

        return $values;
    }

    protected static function clearValuesOlderThan($currencyPairCode, $metricCode, $timestamp)
    {
        $index = 0;
        $metrics = static::getAll($currencyPairCode, $metricCode);
        foreach ($metrics as $metric) {
            if ($metric['timestamp'] <= $timestamp) {
                $index++;
            } else { // дошли до нужной границы - выходим
                break;
            }
        }

        if ($index) { // даже если хотим удалить все, $index будет минимум 1
            return static::trimByIndexes($currencyPairCode, $metricCode, $index, -1);
        }
    }
}