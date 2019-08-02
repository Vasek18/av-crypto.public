<?php

namespace App\Traders;

use App\CurrencyPairsMetrics\Average;
use App\Trading\CurrencyPairRate;

class AveragePriceTrader extends Trader
{
    public static $code = 'AveragePriceTrader';

    private function getMeasurementsStep()
    {
        return 6;
    }

    /**
     * @return int - всегда должно быть нечётным
     */
    private function getMeasurementsCount()
    {
        return 5;
    }

    public function getThresholdPercent()
    {
        return 0.01;
    }

    /**
     * Выглядит ли цена продажи так \/
     *
     * @param $averageValues array
     *
     * @return bool
     */
    public function isThereIsDownUpPeregib($averageValues)
    {
        if (!count($averageValues)) {
            return false;
        }

        // проверка, что чем ближе к центру, тем значение меньше
        $valuesCount = count($averageValues);
        for ($i = 0; $i < floor($valuesCount / 2); $i++) {
            $currentValueFromStart = $averageValues[$i];
            $nextValueFromStart = $averageValues[$i + 1];
            $currentValueFromEnd = $averageValues[($valuesCount - 1) - $i];
            $nextValueFromEnd = $averageValues[($valuesCount - 1) - $i - 1];
            if ($currentValueFromStart <= $nextValueFromStart || $nextValueFromEnd >= $currentValueFromEnd) {
                return false;
            }
        }

        // проверка, что значения достаточно отличаются
        $firstValue = $averageValues[0];
        $lastValue = $averageValues[$valuesCount - 1];
        $middleValue = $averageValues[floor($valuesCount / 2)];
        if (($firstValue - $middleValue) * 100 / $firstValue < $this->getThresholdPercent()) {
            return false;
        }
        if (($lastValue - $middleValue) * 100 / $lastValue < $this->getThresholdPercent()) {
            return false;
        }

        return true;
    }

    /**
     * Выглядит ли цена продажи так /\
     *
     * @param $averageValues array
     *
     * @return bool
     */
    public function isThereIsUpDownPeregib($averageValues)
    {
        if (!count($averageValues)) {
            return false;
        }

        $valuesCount = count($averageValues);
        for ($i = 0; $i < floor($valuesCount / 2); $i++) {
            $currentValueFromStart = $averageValues[$i];
            $nextValueFromStart = $averageValues[$i + 1];
            $currentValueFromEnd = $averageValues[($valuesCount - 1) - $i];
            $nextValueFromEnd = $averageValues[($valuesCount - 1) - $i - 1];
            if ($currentValueFromStart >= $nextValueFromStart || $nextValueFromEnd <= $currentValueFromEnd) {
                return false;
            }
        }

        // проверка, что значения достаточно отличаются
        $firstValue = $averageValues[0];
        $lastValue = $averageValues[$valuesCount - 1];
        $middleValue = $averageValues[floor($valuesCount / 2)];
        if (($middleValue - $firstValue) * 100 / $middleValue < $this->getThresholdPercent()) {
            return false;
        }
        if (($middleValue - $lastValue) * 100 / $middleValue < $this->getThresholdPercent()) {
            return false;
        }

        return true;
    }

    /**
     * Возвращаем только те точки, что будем сравнивать, то есть $count точек с конца через $step точек
     *
     * @param string $priceType
     * @param $rateTimestamp
     *
     * @return array|bool
     */
    private function getAverageValues($priceType)
    {
        $step = $this->getMeasurementsStep();
        $count = $this->getMeasurementsCount();

        // получаем нужные метрики из редиса
        $values = Average::getForPeriod(
            $this->currencyPairCode,
            $priceType,
            1,
            $step * ($count - 1) + 1 // такая сложная формула, потому что при выборе значений мы идём с нуля
        );

        $values = $this->filterAverageValuesByStep($values, $step);

        if (!$this->isThereIsEnoughValues($values, $count)) {
            return false;
        }

        return $values;
    }

    private function isThereIsEnoughValues($values, $count)
    {
        return count($values) >= $count;
    }

    private function filterAverageValuesByStep($values, $step)
    {
        $filteredValues = [];
        for ($i = 0; $i < count($values); $i = $i + $step) {
            if (!empty($values[$i])) {
                $filteredValues[] = $values[$i]['value'];
            }
        }

        return $filteredValues;
    }

    /**
     * @param CurrencyPairRate $rate
     *
     * @return TraderDecision|bool
     */
    public function getDecision(CurrencyPairRate $rate)
    {
        $averageValues = $this->getAverageValues('buy');
        if (is_array($averageValues)) {
            if ($this->isThereIsDownUpPeregib($averageValues)) {
                return new TraderDecision(
                    $rate->buy_price, $rate->timestamp, BUY_ACTION_CODE, static::$code
                );
            }
        }

        $averageValues = $this->getAverageValues('sell');
        if (is_array($averageValues)) {
            if ($this->isThereIsUpDownPeregib($averageValues)) {
                return new TraderDecision($rate->sell_price, $rate->timestamp, SELL_ACTION_CODE, static::$code);
            }
        }

        return false;
    }
}