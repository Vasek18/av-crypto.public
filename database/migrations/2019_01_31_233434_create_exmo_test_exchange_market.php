<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateExmoTestExchangeMarket extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exmoTestExchangeMarketID = DB::table('exchange_markets')->insertGetId(
            array(
                'name'      => 'Exmo test',
                'url'       => '',
                'code'      => 'exmo_test',
                'for_tests' => true,
                'sort'      => 1000,
            )
        );

        // и сразу создаём одну пару
        DB::table('exchange_markets_currency_pairs')->insert(
            array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'USD',
                'exchange_market_id'    => $exmoTestExchangeMarketID,
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 1000,
                'min_price'             => 1,
                'max_price'             => 30000,
                'currency_2_min_amount' => 1,
                'currency_2_max_amount' => 500000,
                'commission_percents'   => 0.2,
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'exchange_markets',
            function (Blueprint $table) {
                //
            }
        );
    }
}
