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

    // todo метод возвращает последние, например, 100 значений, а не значения за последние 100 минут. Если котировок не было за последнее время, то будет выдавать устаревшие данные
    // todo совсем не нравится эта система с минутами. По идее нужно просто 2 границы в таймстемпах использовать
    // todo можно возвращать коллекцию этого класса, а не массив массивов
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
            $lastKey = ($countFromTimestamp - date('U')) / SECONDS_IN_MINUTE;
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
            $values[$c] = unserialize($value);
        }

        return $values;
    }

    // todo можно возвращать класс, а не массив
    public static function getLast($currencyPairCode)
    {
        $values = static::getForPeriod($currencyPairCode, 1);

        return !empty($values) ? $values[0] : null;
    }

    public static function clearOlderThan($currencyPairCode, $timestamp)
    {
        $redisKey = $currencyPairCode.'.rates';

        $timestampNow = date('U');
        $start = -(($timestampNow - $timestamp) / SECONDS_IN_MINUTE);

        // боремся с выходом за диапазон
        $length = Redis::llen($redisKey);
        if ($length + $start < 0) {
            $start = 0;
        }

        Redis::ltrim(
            $redisKey,
            $start,
            -1
        );
    }

    public static function count($currencyPairCode)
    {
        return Redis::llen($currencyPairCode.'.rates');
    }
}