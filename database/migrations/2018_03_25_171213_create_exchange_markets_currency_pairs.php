<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeMarketsCurrencyPairs extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'exchange_markets_currency_pairs',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('currency_1_code');
                $table->string('currency_2_code');
                $table->integer('exchange_market_id')->unsigned();
                $table->double('currency_1_min_amount', 20, 8)->unsigned();
                $table->double('currency_1_max_amount', 20, 8)->unsigned();
                $table->double('min_price', 20, 8)->unsigned();
                $table->double('max_price', 20, 8)->unsigned();
                $table->double('currency_2_max_amount', 20, 8)->unsigned();
                $table->double('currency_2_min_amount', 20, 8)->unsigned();
                $table->double('commission_percents', 20, 8)->unsigned();
            }
        );

        if (env('APP_ENV') != 'production') {
            $testExchangeMarket = DB::table('exchange_markets')
                ->where('code', 'test')
                ->first();
            if ($testExchangeMarket) {
                $testExchangeMarketID = $testExchangeMarket->id;

                DB::table('exchange_markets_currency_pairs')->insert(
                    array(
                        'currency_1_code'       => 'BTC',
                        'currency_2_code'       => 'USD',
                        'exchange_market_id'    => $testExchangeMarketID,
                        'currency_1_min_amount' => 0.001,
                        'currency_1_max_amount' => 100,
                        'min_price'             => 1,
                        'max_price'             => 30000,
                        'currency_2_min_amount' => 1,
                        'currency_2_max_amount' => 200000,
                        'commission_percents'   => 0.2,
                    )
                );

                DB::table('exchange_markets_currency_pairs')->insert(
                    array(
                        'currency_1_code'       => 'BTC',
                        'currency_2_code'       => 'RUB',
                        'exchange_market_id'    => $testExchangeMarketID,
                        'currency_1_min_amount' => 0.001,
                        'currency_1_max_amount' => 100,
                        'min_price'             => 1,
                        'max_price'             => 2000000,
                        'currency_2_min_amount' => 1,
                        'currency_2_max_amount' => 12000000,
                        'commission_percents'   => 0.2,
                    )
                );

                DB::table('exchange_markets_currency_pairs')->insert(
                    array(
                        'currency_1_code'       => 'ETH',
                        'currency_2_code'       => 'BTC',
                        'exchange_market_id'    => $testExchangeMarketID,
                        'currency_1_min_amount' => 0.001,
                        'currency_1_max_amount' => 100,
                        'min_price'             => 1,
                        'max_price'             => 2000000,
                        'currency_2_min_amount' => 1,
                        'currency_2_max_amount' => 12000000,
                        'commission_percents'   => 0.2,
                    )
                );

                DB::table('exchange_markets_currency_pairs')->insert(
                    array(
                        'currency_1_code'       => 'ETH',
                        'currency_2_code'       => 'USD',
                        'exchange_market_id'    => $testExchangeMarketID,
                        'currency_1_min_amount' => 0.001,
                        'currency_1_max_amount' => 100,
                        'min_price'             => 1,
                        'max_price'             => 2000000,
                        'currency_2_min_amount' => 1,
                        'currency_2_max_amount' => 12000000,
                        'commission_percents'   => 0.2,
                    )
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exchange_markets_currency_pairs');
    }
}
