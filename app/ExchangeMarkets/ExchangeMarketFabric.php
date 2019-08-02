<?php

namespace App\ExchangeMarkets;

class ExchangeMarketFabric
{
    protected static $classesMap = [
        'test'      => 'App\ExchangeMarkets\TestExchangeMarket',
        'exmo'      => 'App\ExchangeMarkets\ExmoExchangeMarket',
        'exmo_test' => 'App\ExchangeMarkets\ExmoTestExchangeMarket',
    ];

    /**
     * @param $exmCode
     *
     * @return ExchangeMarket|bool
     */
    public static function get($exmCode)
    {
        if (isset(static::$classesMap[$exmCode])) {
            return new static::$classesMap[$exmCode];
        }

        return false;
    }
}
