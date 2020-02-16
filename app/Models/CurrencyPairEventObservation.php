<?php

namespace App\Models;

use App\Trading\CurrencyPairRate;
use App\Trading\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CurrencyPairEventObservation extends Model
{
    protected $table      = 'currency_pair_event_observations';
    protected $fillable   = [
        'currency_pair_code',
        'event_code',
        'params',
        'top_hits',
        'bottom_hits',
        'missed',
        'percent',
        'period',
    ];
    protected $casts      = [
        'params' => 'array',
    ];
    public    $timestamps = false;

    const EVENT_HIT_TOP    = 1;
    const EVENT_HIT_BOTTOM = 2;
    const EVENT_MISSED     = 3;

    public static function getThresholdPercent()
    {
        return 2;
    }

    public static function getPeriodInSeconds()
    {
        return 86400; // 60 * 60 * 24
    }

    /**
     * Проверка предсказаний
     */
    public static function checkEvents()
    {
        $currencyPairs = Cache::remember(
            'active_currency_pairs',
            now()->addDay(),
            function () {
                return ExchangeMarketCurrencyPair
                    ::active()
                    ->orderBy('exchange_market_id')
                    ->orderBy('currency_1_code')
                    ->orderBy('currency_2_code')
                    ->get();
            }
        );

        foreach ($currencyPairs as $currencyPair) {
            $events = Event::get($currencyPair->code);
            foreach ($events as $eventIndex => $event) {
                $checkResult = self::checkEvent($currencyPair->code, $event);
                switch ($checkResult) {
                    case static::EVENT_HIT_TOP:
                    case static::EVENT_HIT_BOTTOM:
                    case static::EVENT_MISSED:
                        static::commitEvent($currencyPair->code, $event, $checkResult);
                        Event::delete($currencyPair->code, $event);
                        break;
                }
            }
        }
    }

    public static function checkEvent($currencyPairCode, $event)
    {
        $lastRate = CurrencyPairRate::getLast($currencyPairCode);
        if (!$lastRate) {
            return false;
        }

        // тут sell, потому что нам поднятие цены интересно возможностью продажи
        if ($lastRate->sell_price >= ($event['sell_price'] * (1 + static::getThresholdPercent() / 100))) {
            return static::EVENT_HIT_TOP;
        }
        // а тут наоборот хочется купить подешевле
        if ($lastRate->buy_price <= ($event['buy_price'] * (1 - static::getThresholdPercent() / 100))) {
            return static::EVENT_HIT_BOTTOM;
        }
        if ($event['timestamp'] + static::getPeriodInSeconds() <= date('U')) {
            return static::EVENT_MISSED;
        }

        return false;
    }

    public static function commitEvent($currencyPairCode, $event, $increment)
    {
        $observation = static::getObservationForEvent($currencyPairCode, $event);

        if ($increment == static::EVENT_HIT_TOP) {
            $observation->increment('top_hits');
        }
        if ($increment == static::EVENT_HIT_BOTTOM) {
            $observation->increment('bottom_hits');
        }
        if ($increment == static::EVENT_MISSED) {
            $observation->increment('missed');
        }
    }

    // todo попытаться упростить
    public static function getObservationForEvent($currencyPairCode, $event)
    {
        $params = empty($event['params']) ? null : $event['params']; // предупреждаем ошибку отсутствия ключа

        $observation = static
            ::where('currency_pair_code', $currencyPairCode)
            ->where('event_code', $event['type'])
            ->whereRaw(
                empty($params) ? "params IS NULL" : "params='".json_encode($params)."'"
            ) // пришлось уйти от firstOrCreate с массивом к такой сложной структуре, потому что where некоректно работает с json полями
            ->where('percent', static::getThresholdPercent())
            ->where('period', static::getPeriodInSeconds())
            ->first();
        if (!$observation) {
            $observation = static::create(
                [
                    'currency_pair_code' => $currencyPairCode,
                    'event_code'         => $event['type'],
                    'params'             => $params,
                    'percent'            => static::getThresholdPercent(),
                    'period'             => static::getPeriodInSeconds(),
                ]
            );
        }

        return $observation;
    }
}
