<?php

use Faker\Generator as Faker;

$factory->define(\App\Models\ExchangeMarketUserAccount::class, function(Faker $faker){
    $testExchangeMarket = \App\Models\ExchangeMarket::where('code', 'test')->first();
    if ($testExchangeMarket){
        $exchangeMarketID = $testExchangeMarket->id;
    } else{
        $exchangeMarketID = \App\Models\ExchangeMarket::first()->id;
    }

    return [
        'exchange_market_id' => $exchangeMarketID,
        'user_id'            => factory(\App\Models\User::class),
        'active'             => true,
        'public_key'         => 'test',
        'secret_key'         => 'test',
    ];
});
