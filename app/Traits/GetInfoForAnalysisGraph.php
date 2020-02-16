<?php

namespace App\Traits;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\BuyAmount;
use App\CurrencyPairsMetrics\Extremum;
use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\CurrencyPairsMetrics\SellQuantity;
use App\CurrencyPairsMetrics\Spread;

trait GetInfoForAnalysisGraph
{
    public function getExtremumsForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
    {
        $metrics = [];
        $values = Extremum::getMinimumsForPeriod($currencyPairCode, $minutes, $timestampTo);
        if (!empty($values)) {
            $metrics[] = [
                'code'       => 'minimum',
                'name'       => __('metrics.minimum'),
                'values'     => $values,
                'type'       => 'extremum',
                'group_name' => __('metrics.extremums'),
            ];
        }
        $values = Extremum::getMaximumsForPeriod($currencyPairCode, $minutes, $timestampTo);
        if (!empty($values)) {
            $metrics[] = [
                'code'       => 'maximum',
                'name'       => __('metrics.maximum'),
                'values'     => $values,
                'type'       => 'extremum',
                'group_name' => __('metrics.extremums'),
            ];
        }

        return $metrics;
    }

    public function getAveragesForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
    {
        $metrics = [];

        foreach (Average::$hourIntervals as $hourInterval) {
            foreach (['sell', 'buy'] as $type) {
                $values = Average::getForPeriod(
                    $currencyPairCode,
                    $type,
                    $hourInterval,
                    $minutes,
                    $timestampTo
                );

                if (!empty($values)) {
                    $metrics[] = [
                        'code'       => 'average_'.$type.'_'.$hourInterval,
                        'name'       => ucfirst(__('metrics.for_period'))
                            .' '.$hourInterval
                            .' '.trans_choice('metrics.hours', $hourInterval)
                            .'. '.__('metrics.'.$type),
                        'values'     => $values,
                        'type'       => 'average',
                        'group_name' => __('metrics.averages'),
                    ];
                }
            }
        }

        return $metrics;
    }

    public function getMacdsForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
    {
        $metrics = [];

        foreach (Macd::getAllAveragePeriodsPairs() as list($hourIntervalFastAvg, $hourIntervalSlowAvg)) {
            $values = Macd::getForPeriod(
                $currencyPairCode,
                $hourIntervalFastAvg,
                $hourIntervalSlowAvg,
                $minutes,
                $timestampTo
            );

            if (!empty($values)) {
                $metrics[] = [
                    'code'       => 'macd_'.$hourIntervalFastAvg.'_'.$hourIntervalSlowAvg,
                    'name'       => ucfirst(__('metrics.for_period'))
                        .' '.$hourIntervalFastAvg
                        .'/'.$hourIntervalSlowAvg
                        .' '.trans_choice('metrics.hours', $hourIntervalSlowAvg),
                    'values'     => $values,
                    'type'       => 'macd',
                    'group_name' => __('metrics.macds'),
                ];
            }
        }

        return $metrics;
    }

    public function getMacdAveragesForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
    {
        $metrics = [];

        foreach (Macd::getAllAveragePeriodsPairs() as list($hourIntervalFastAvg, $hourIntervalSlowAvg)) {
            foreach (MacdAverage::$hourIntervals as $hourInterval) {

                $values = MacdAverage::getForPeriod(
                    $currencyPairCode,
                    $hourIntervalFastAvg,
                    $hourIntervalSlowAvg,
                    $hourInterval,
                    $minutes,
                    $timestampTo
                );

                if (!empty($values)) {
                    $metrics[] = [
                        'code'       => 'macd_average_'.$hourIntervalFastAvg.'_'.$hourIntervalSlowAvg.'_'.$hourInterval,
                        'name'       => ucfirst(__('metrics.for_period'))
                            .' '.$hourIntervalFastAvg
                            .'/'.$hourIntervalSlowAvg
                            .'/'.$hourInterval
                            .' '.trans_choice('metrics.hours', $hourIntervalSlowAvg),
                        'values'     => $values,
                        'type'       => 'macd',
                        'group_name' => __('metrics.macd_averages'),
                    ];
                }
            }
        }

        return $metrics;
    }

    public function getOrderBookMetricsForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
    {
        $metrics = [];

        $values = SellQuantity::getForPeriod($currencyPairCode, $minutes, $timestampTo);
        if (!empty($values)) {
            $metrics[] = [
                'code'       => 'sell_quantity',
                'type'       => 'sell_quantity',
                'name'       => __('metrics.sell_quantity'),
                'values'     => $values,
                'group_name' => __('metrics.order_book'),
            ];
        }

        $values = BuyAmount::getForPeriod($currencyPairCode, $minutes, $timestampTo);
        if (!empty($values)) {
            $metrics[] = [
                'code'       => 'buy_amount',
                'type'       => 'buy_amount',
                'name'       => __('metrics.buy_amount'),
                'values'     => $values,
                'group_name' => __('metrics.order_book'),
            ];
        }

        $values = Spread::getForPeriod($currencyPairCode, $minutes, $timestampTo);
        if (!empty($values)) {
            $metrics[] = [
                'code'       => 'spread',
                'type'       => 'spread',
                'name'       => __('metrics.spread'),
                'values'     => $values,
                'group_name' => __('metrics.order_book'),
            ];
        }

        return $metrics;
    }
}