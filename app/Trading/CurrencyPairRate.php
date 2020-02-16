<?php

namespace App\Trading;

use App\CurrencyPairsMetrics\Clearable;
use Illuminate\Support\Facades\Redis;

class CurrencyPairRate implements Clearable
{
    public $currencyPairCode;
    public $buy_price;
    public $sell_price;
    public $timestamp;

    public function __construct(
        $currencyPairCode,
        $buy_price,
        $sell_price,
        $timestamp
    ) {
        $this->currencyPairCode = $currencyPairCode;
        $this->buy_price = $buy_price;
        $this->sell_price = $sell_price;
        $this->timestamp = $timestamp;
    }

    public static function save(
        $currencyPairCode,
        $buy_price,
        $sell_price,
        $timestamp
    ) {
        Redis::rpush(
            $currencyPairCode.'.rates',
            serialize(
                [
                    'buy_price'  => $buy_price,
                    'sell_price' => $sell_price,
                    'timestamp'  => $timestamp,
                ]
            )
        );

        return new static(
            $currencyPairCode,
            $buy_price,
            $sell_price,
            $timestamp
        );
    }

    // Такая система с минутами удобнее двух таймстемпов при расчётах метрик
    // Метод возвращает последние, например, 100 значений, а не значения за последние 100 минут. Если котировок не было за последнее время, то будет выдавать устаревшие данные. Но это же значит, что и биржа падала, а значит данные остаются актуальными для торгов
    public static function getForPeriod($currencyPairCode, $minutes = null, $countFromTimestamp = null)
    {
        // первый индекс
        if ($minutes) {
            $firstKey = -$minutes;
        } else {
            $firstKey = 0; // когда нам нужны все котировки
        }

        // последний индекс
        $lastKey = -1;
        if ($countFromTimestamp) {
            $lastKey = floor(($countFromTimestamp - date('U')) / SECONDS_IN_MINUTE); // обязательно должен быть integer
            if ($lastKey > -1) {
                $lastKey = -1;
            }
        }

        $values = Redis::lrange(
            $currencyPairCode.'.rates',
            $firstKey,
            $lastKey
        );

        foreach ($values as $c => $value) {
            $unserialized = unserialize($value);
            $values[$c] = new static(
                $currencyPairCode,
                $unserialized['buy_price'],
                $unserialized['sell_price'],
                $unserialized['timestamp']
            );
        }

        return $values;
    }

    public static function getLast($currencyPairCode): ?CurrencyPairRate
    {
        $values = static::getForPeriod($currencyPairCode, 1);
        if (empty($values) || !$values[0]) {
            return null;
        }

        return new static(
            $currencyPairCode,
            $values[0]->buy_price,
            $values[0]->sell_price,
            $values[0]->timestamp
        );
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        $redisKey = $currencyPairCode.'.rates';

        $timestampNow = date('U');
        $tooOldMetricsLifeInMinutes = ($timestampNow - $timestamp) / SECONDS_IN_MINUTE;

        $indexOtstup = ceil(-$tooOldMetricsLifeInMinutes); // минус, так как обрезаем, те что были до начала таймстемпа

        Redis::ltrim(
            $redisKey,
            $indexOtstup,
            -1
        );
    }

    public static function count($currencyPairCode)
    {
        return Redis::llen($currencyPairCode.'.rates');
    }
}