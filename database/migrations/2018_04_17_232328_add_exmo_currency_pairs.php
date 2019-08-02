<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExmoCurrencyPairs extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        $exmo = DB::table('exchange_markets')
            ->where('code', 'exmo')
            ->first();
        if ($exmo){
            $exmoID = $exmo->id;

            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 100,
                'min_price'             => 1,
                'max_price'             => 30000,
                'currency_2_max_amount' => 200000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'EUR',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 100,
                'min_price'             => 1,
                'max_price'             => 30000,
                'currency_2_max_amount' => 200000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 100,
                'min_price'             => 1,
                'max_price'             => 2000000,
                'currency_2_max_amount' => 12000000,
                'currency_2_min_amount' => 10,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'UAH',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 100,
                'min_price'             => 1,
                'max_price'             => 1500000,
                'currency_2_max_amount' => 800000,
                'currency_2_min_amount' => 10,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'PLN',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.001,
                'max_price'             => 90000,
                'currency_2_max_amount' => 900000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BCH',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 0.003,
                'currency_1_max_amount' => 10000,
                'min_price'             => 0.00000001,
                'max_price'             => 5,
                'currency_2_max_amount' => 10,
                'currency_2_min_amount' => 0.0001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BCH',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.003,
                'currency_1_max_amount' => 10000,
                'min_price'             => 0.00000001,
                'max_price'             => 30000,
                'currency_2_max_amount' => 20000,
                'currency_2_min_amount' => 0.0001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BCH',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 0.003,
                'currency_1_max_amount' => 10000,
                'min_price'             => 0.00000001,
                'max_price'             => 2000000,
                'currency_2_max_amount' => 600000,
                'currency_2_min_amount' => 0.0001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BCH',
                'currency_2_code'       => 'ETH',
                'currency_1_min_amount' => 0.003,
                'currency_1_max_amount' => 10000,
                'min_price'             => 0.0000001,
                'max_price'             => 200,
                'currency_2_max_amount' => 10000,
                'currency_2_min_amount' => 0.0001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'DASH',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 500,
                'min_price'             => 0.001,
                'max_price'             => 10,
                'currency_2_max_amount' => 10,
                'currency_2_min_amount' => 0.001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'DASH',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.001,
                'max_price'             => 5000,
                'currency_2_max_amount' => 100000,
                'currency_2_min_amount' => 0.1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'DASH',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.001,
                'max_price'             => 200000,
                'currency_2_max_amount' => 1000000,
                'currency_2_min_amount' => 0.1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETH',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.0001,
                'max_price'             => 0.5,
                'currency_2_max_amount' => 10,
                'currency_2_min_amount' => 0.01,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETH',
                'currency_2_code'       => 'LTC',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.001,
                'max_price'             => 1000,
                'currency_2_max_amount' => 10000,
                'currency_2_min_amount' => 0.1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETH',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.001,
                'max_price'             => 10000,
                'currency_2_max_amount' => 50000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETH',
                'currency_2_code'       => 'EUR',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.001,
                'max_price'             => 10000,
                'currency_2_max_amount' => 50000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETH',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.001,
                'max_price'             => 100000,
                'currency_2_max_amount' => 1000000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETH',
                'currency_2_code'       => 'UAH',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.01,
                'max_price'             => 100000,
                'currency_2_max_amount' => 200000,
                'currency_2_min_amount' => 0.1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETH',
                'currency_2_code'       => 'PLN',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.001,
                'max_price'             => 8000,
                'currency_2_max_amount' => 900000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETC',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 0.2,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.0001,
                'max_price'             => 0.5,
                'currency_2_max_amount' => 10,
                'currency_2_min_amount' => 0.001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETC',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.2,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.01,
                'max_price'             => 10000,
                'currency_2_max_amount' => 100000,
                'currency_2_min_amount' => 0.01,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETC',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 0.2,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.01,
                'max_price'             => 10000,
                'currency_2_max_amount' => 500000,
                'currency_2_min_amount' => 0.01,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'LTC',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 0.05,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.0001,
                'max_price'             => 1,
                'currency_2_max_amount' => 10,
                'currency_2_min_amount' => 0.001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'LTC',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.05,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.01,
                'max_price'             => 10000,
                'currency_2_max_amount' => 100000,
                'currency_2_min_amount' => 0.001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'LTC',
                'currency_2_code'       => 'EUR',
                'currency_1_min_amount' => 0.05,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.01,
                'max_price'             => 10000,
                'currency_2_max_amount' => 100000,
                'currency_2_min_amount' => 0.001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'LTC',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 0.05,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.01,
                'max_price'             => 100000,
                'currency_2_max_amount' => 1000000,
                'currency_2_min_amount' => 0.01,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ZEC',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 500,
                'min_price'             => 0.001,
                'max_price'             => 10,
                'currency_2_max_amount' => 10,
                'currency_2_min_amount' => 0.001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ZEC',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 500,
                'min_price'             => 0.001,
                'max_price'             => 5000,
                'currency_2_max_amount' => 20000,
                'currency_2_min_amount' => 0.1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ZEC',
                'currency_2_code'       => 'EUR',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 500,
                'min_price'             => 0.001,
                'max_price'             => 5000,
                'currency_2_max_amount' => 20000,
                'currency_2_min_amount' => 0.1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ZEC',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 500,
                'min_price'             => 0.001,
                'max_price'             => 100000,
                'currency_2_max_amount' => 1000000,
                'currency_2_min_amount' => 0.1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'XRP',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 0.1,
                'currency_1_max_amount' => 50000,
                'min_price'             => 0.0000001,
                'max_price'             => 0.1,
                'currency_2_max_amount' => 5000,
                'currency_2_min_amount' => 0.00001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'XRP',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 15,
                'currency_1_max_amount' => 20000,
                'min_price'             => 0.001,
                'max_price'             => 10,
                'currency_2_max_amount' => 20000,
                'currency_2_min_amount' => 0.001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'XRP',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 15,
                'currency_1_max_amount' => 20000,
                'min_price'             => 0.000001,
                'max_price'             => 600,
                'currency_2_max_amount' => 500000,
                'currency_2_min_amount' => 0.01,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'XMR',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 0.03,
                'currency_1_max_amount' => 500,
                'min_price'             => 0.001,
                'max_price'             => 1,
                'currency_2_max_amount' => 10,
                'currency_2_min_amount' => 0.001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'XMR',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.03,
                'currency_1_max_amount' => 500,
                'min_price'             => 0.001,
                'max_price'             => 1000,
                'currency_2_max_amount' => 100000,
                'currency_2_min_amount' => 0.1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'XMR',
                'currency_2_code'       => 'EUR',
                'currency_1_min_amount' => 0.03,
                'currency_1_max_amount' => 500,
                'min_price'             => 0.001,
                'max_price'             => 1000,
                'currency_2_max_amount' => 100000,
                'currency_2_min_amount' => 0.1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'USDT',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 100,
                'min_price'             => 1,
                'max_price'             => 30000,
                'currency_2_max_amount' => 100000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'ETH',
                'currency_2_code'       => 'USDT',
                'currency_1_min_amount' => 0.01,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.001,
                'max_price'             => 10000,
                'currency_2_max_amount' => 10000,
                'currency_2_min_amount' => 0.5,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'USDT',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 3,
                'currency_1_max_amount' => 100000,
                'min_price'             => 0.5,
                'max_price'             => 10,
                'currency_2_max_amount' => 100000,
                'currency_2_min_amount' => 0.01,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'USDT',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 3,
                'currency_1_max_amount' => 25000,
                'min_price'             => 0.01,
                'max_price'             => 1000,
                'currency_2_max_amount' => 1500000,
                'currency_2_min_amount' => 10,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'USD',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 3,
                'currency_1_max_amount' => 25000,
                'min_price'             => 0.01,
                'max_price'             => 1000,
                'currency_2_max_amount' => 1500000,
                'currency_2_min_amount' => 10,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'DOGE',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 100,
                'currency_1_max_amount' => 100000000,
                'min_price'             => 0.0000001,
                'max_price'             => 1,
                'currency_2_max_amount' => 10,
                'currency_2_min_amount' => 0.0001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'WAVES',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 0.5,
                'currency_1_max_amount' => 1000,
                'min_price'             => 0.0001,
                'max_price'             => 1,
                'currency_2_max_amount' => 10,
                'currency_2_min_amount' => 0.0001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'WAVES',
                'currency_2_code'       => 'RUB',
                'currency_1_min_amount' => 0.5,
                'currency_1_max_amount' => 5000,
                'min_price'             => 1,
                'max_price'             => 10000,
                'currency_2_max_amount' => 500000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'KICK',
                'currency_2_code'       => 'BTC',
                'currency_1_min_amount' => 100,
                'currency_1_max_amount' => 200000,
                'min_price'             => 0.0000001,
                'max_price'             => 0.1,
                'currency_2_max_amount' => 6,
                'currency_2_min_amount' => 0.00001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
            DB::table('exchange_markets_currency_pairs')->insert(array(
                'currency_1_code'       => 'KICK',
                'currency_2_code'       => 'ETH',
                'currency_1_min_amount' => 100,
                'currency_1_max_amount' => 200000,
                'min_price'             => 0.000001,
                'max_price'             => 1,
                'currency_2_max_amount' => 100,
                'currency_2_min_amount' => 0.0001,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoID,
            ));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        //
    }
}