<?php

namespace App\Listeners;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\Events\CurrencyPairRateChanged;
use App\Trading\CurrencyPairRate;
use App\Trading\Event;

class FireCurrencyPairEvents
{
    public function getDirectionConfirmPointsCount()
    {
        return 3;
    }

    public function handle(CurrencyPairRateChanged $event)
    {
        $this->fireMacdsCrossItsAverageEvents($event);
        $this->fireRatesCrossItsAverageEvents($event);
    }

    public function fireRatesCrossItsAverageEvents($event)
    {
        // получаем последние котировки
        $rates = CurrencyPairRate::getForPeriod($event->currencyPairCode, $this->getDirectionConfirmPointsCount());
        if (count($rates) < $this->getDirectionConfirmPointsCount()) {
            return false;
        }

        foreach (Average::$hourIntervals as $hourInterval) { // для каждого среднего
            // и последние средние
            $averages = Average::getForPeriod(
                $event->currencyPairCode,
                'buy', // todo какой тип нужен?
                $hourInterval,
                $this->getDirectionConfirmPointsCount()
            );
            if (count($averages) < $this->getDirectionConfirmPointsCount()) {
                continue;
            }

            // проверка на пересечение сверху вниз
            if ($this->ratesCrossItsAverageUpDown(
                $rates,
                $averages
            )) {
                Event::fire(
                    $event->currencyPairCode,
                    'rates_cross_its_average',
                    $event->rate->buy_price,
                    $event->rate->sell_price,
                    [
                        'direction'      => 'up_down',
                        'average_period' => $hourInterval,
                    ]
                );
            }

            // проверка на пересечение снизу вверх
            if ($this->ratesCrossItsAverageDownUp(
                $rates,
                $averages
            )) {
                Event::fire(
                    $event->currencyPairCode,
                    'rates_cross_its_average',
                    $event->rate->buy_price,
                    $event->rate->sell_price,
                    [
                        'direction'      => 'down_up',
                        'average_period' => $hourInterval,
                    ]
                );
            }
        }
    }

    protected function ratesCrossItsAverageUpDown(
        $rates,
        $averages
    ) {
        $lastNumber = count($rates) - 1;
        for ($i = 0; $i < $lastNumber; $i++) {
            if ($rates[$i]->buy_price <= $averages[$i]['value']) { // проверяем, что котировки до столкновения были строго больше среднего
                return false;
            }
        }
        if ($rates[$lastNumber]->buy_price <= $averages[$lastNumber]['value']) { // если последняя котировка равна или ниже средней
            return true;
        }

        return false;
    }

    protected function ratesCrossItsAverageDownUp(
        $rates,
        $averages
    ) {
        $lastNumber = count($rates) - 1;
        for ($i = 0; $i < $lastNumber; $i++) {
            if ($rates[$i]->buy_price >= $averages[$i]['value']) { // проверяем, что котировки до столкновения были строго меньше среднего
                return false;
            }
        }
        if ($rates[$lastNumber]->buy_price >= $averages[$lastNumber]['value']) { // если последняя котировка равна или больше средней
            return true;
        }

        return false;
    }

    public function fireMacdsCrossItsAverageEvents(CurrencyPairRateChanged $event)
    {
        foreach (Macd::getAllAveragePeriodsPairs() as list($hourIntervalFast, $hourIntervalSlow)) { // для каждого macd
            // получаем последние макд
            $macds = Macd::getForPeriod(
                $event->currencyPairCode,
                $hourIntervalFast,
                $hourIntervalSlow,
                $this->getDirectionConfirmPointsCount()
            );
            if (count($macds) < $this->getDirectionConfirmPointsCount()) {
                continue;
            }
            foreach (MacdAverage::$hourIntervals as $hourIntervalAvg) { // для каждого периода, по которому рассчитываем среднее
                // и последние средние макд
                $macdAverages = MacdAverage::getForPeriod(
                    $event->currencyPairCode,
                    $hourIntervalFast,
                    $hourIntervalSlow,
                    $hourIntervalAvg,
                    $this->getDirectionConfirmPointsCount()
                );
                if (count($macdAverages) < $this->getDirectionConfirmPointsCount()) {
                    continue;
                }

                // проверка на пересечение сверху вниз
                if ($this->macdsCrossItsAverageUpDown(
                    $macds,
                    $macdAverages
                )) {
                    Event::fire(
                        $event->currencyPairCode,
                        'macd_cross_its_average',
                        $event->rate->buy_price,
                        $event->rate->sell_price,
                        [
                            'direction'      => 'up_down',
                            'fast_period'    => $hourIntervalFast,
                            'slow_period'    => $hourIntervalSlow,
                            'average_period' => $hourIntervalAvg,
                        ]
                    );
                }

                // проверка на пересечение снизу вверх
                if ($this->macdsCrossItsAverageDownUp(
                    $macds,
                    $macdAverages
                )) {
                    Event::fire(
                        $event->currencyPairCode,
                        'macd_cross_its_average',
                        $event->rate->buy_price,
                        $event->rate->sell_price,
                        [
                            'direction'      => 'down_up',
                            'fast_period'    => $hourIntervalFast,
                            'slow_period'    => $hourIntervalSlow,
                            'average_period' => $hourIntervalAvg,
                        ]
                    );
                }
            }
        }
    }

    protected function macdsCrossItsAverageUpDown(
        $macds,
        $macdAverages
    ) {
        $lastNumber = count($macds) - 1;
        for ($i = 0; $i < $lastNumber; $i++) {
            if ($macds[$i]['value'] <= $macdAverages[$i]['value']) { // проверяем, что macd до столкновения были строго больше среднего
                return false;
            }
        }
        if ($macds[$lastNumber]['value'] <= $macdAverages[$lastNumber]['value']) { // если последняя macd равна или ниже средней
            return true;
        }

        return false;
    }

    protected function macdsCrossItsAverageDownUp(
        $macds,
        $macdAverages
    ) {
        $lastNumber = count($macds) - 1;
        for ($i = 0; $i < $lastNumber; $i++) {
            if ($macds[$i]['value'] >= $macdAverages[$i]['value']) { // проверяем, что macd до столкновения были строго меньше среднего
                return false;
            }
        }
        if ($macds[$lastNumber]['value'] >= $macdAverages[$lastNumber]['value']) { // если последняя macd равна или больше средней
            return true;
        }

        return false;
    }
}
