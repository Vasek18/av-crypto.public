<?php

namespace App\Trading;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\Events\CurrencyPairEventFired;
use Illuminate\Support\Facades\Redis;

class Event
{
    public static $redisCode = 'events';

    public $type;
    public $params;
    public $buyPrice;
    public $sellPrice;
    public $timestamp;

    public function __construct($type, $params, $buyPrice, $sellPrice, $timestamp)
    {
        $this->type = $type;
        $this->params = $params;
        $this->buyPrice = $buyPrice;
        $this->sellPrice = $sellPrice;
        $this->timestamp = $timestamp;
    }

    public static function fire($currencyPairCode, $type, $buyPrice, $sellPrice, $params = [])
    {
        return static::save($currencyPairCode, $type, $buyPrice, $sellPrice, date('U'), $params);
    }

    public static function save($currencyPairCode, $type, $buyPrice, $sellPrice, $timestamp, $params = [])
    {
        $res = Redis::rpush(
            $currencyPairCode.'.'.static::$redisCode,
            serialize(
                [
                    'type'       => $type,
                    'params'     => $params,
                    'buy_price'  => $buyPrice,
                    'sell_price' => $sellPrice,
                    'timestamp'  => $timestamp,
                ]
            )
        );

        event(
            new CurrencyPairEventFired(
                $currencyPairCode,
                $type,
                $buyPrice,
                $sellPrice,
                $timestamp,
                $params
            )
        );

        return $res;
    }

    public static function get($currencyPairCode)
    {
        $values = Redis::lrange(
            $currencyPairCode.'.'.static::$redisCode,
            0,
            -1
        );

        $answer = [];
        foreach ($values as $value) {
            $answer[] = unserialize($value);
        }

        return $answer;
    }

    public static function delete($currencyPairCode, $event)
    {
        Redis::lrem($currencyPairCode.'.'.static::$redisCode, 0, serialize($event));
    }

    public static function getSimultaneousEventsTimePeriod()
    {
        return 5 * SECONDS_IN_MINUTE;
    }

    public static function fireComplexEvents(
        $currencyPairCode,
        $firedEventType,
        $firedEventParams,
        $buyPrice,
        $sellPrice
    ) {
        /** если макд пересёк свою среднюю и котировки пересекли свою среднюю в том же направлении и оба события произошли в пределах n минут */
        if ($firedEventType == 'macd_cross_its_average') {
            // смотрим, пересекли ли котировки свою среднюю в том же направлении
            foreach (Average::$hourIntervals as $hourInterval) {
                if (static::ifThereWasEvent(
                    $currencyPairCode,
                    'rates_cross_its_average',
                    [
                        'direction'      => $firedEventParams['direction'],
                        'average_period' => $hourInterval,
                    ],
                    static::getSimultaneousEventsTimePeriod()
                )) {
                    // если да - запускаем событие
                    Event::fire(
                        $currencyPairCode,
                        'macd_and_rates_cross_its_averages',
                        $buyPrice,
                        $sellPrice,
                        [
                            'direction'           => $firedEventParams['direction'],
                            'rates_period'        => $hourInterval,
                            'macd_fast_period'    => $firedEventParams['fast_period'],
                            'macd_slow_period'    => $firedEventParams['slow_period'],
                            'macd_average_period' => $firedEventParams['average_period'],
                        ]
                    );
                }
            }
        }

        // если котировки пересекли свою среднюю сверху вниз
        if ($firedEventType == 'rates_cross_its_average') {
            // смотрим, пересекли ли макди свою среднюю в том же направлении
            foreach (Macd::getAllAveragePeriodsPairs(
            ) as list($hourIntervalFast, $hourIntervalSlow)) { // для каждого macd
                foreach (MacdAverage::$hourIntervals as $hourIntervalAvg) { // для каждого периода, по которому рассчитываем среднее
                    if (static::ifThereWasEvent(
                        $currencyPairCode,
                        'macd_cross_its_average',
                        [
                            'direction'      => $firedEventParams['direction'],
                            'fast_period'    => $hourIntervalFast,
                            'slow_period'    => $hourIntervalSlow,
                            'average_period' => $hourIntervalAvg,
                        ],
                        static::getSimultaneousEventsTimePeriod()
                    )) {
                        // если да - запускаем событие
                        Event::fire(
                            $currencyPairCode,
                            'macd_and_rates_cross_its_averages',
                            $buyPrice,
                            $sellPrice,
                            [
                                'direction'           => $firedEventParams['direction'],
                                'rates_period'        => $firedEventParams['average_period'],
                                'macd_fast_period'    => $hourIntervalFast,
                                'macd_slow_period'    => $hourIntervalSlow,
                                'macd_average_period' => $hourIntervalAvg,
                            ]
                        );
                    }
                }
            }
        }
    }

    public static function ifThereWasEvent($currencyPairCode, $type, $params = [], $seconds = 0)
    {
        $currentTime = date('U');
        $minTimestamp = $currentTime - $seconds;

        $events = static::get($currencyPairCode);
        for ($i = count($events) - 1; $i >= 0; $i--) {
            $event = $events[$i];

            if ($seconds) {
                if ($event['timestamp'] < $minTimestamp) {
                    return false;
                }
            }

            if ($event['type'] == $type) {
                if ($event['params'] == $params) {
                    return true;
                }
            }
        }

        return false;
    }
}
