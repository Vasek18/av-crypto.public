<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddActivePropToCurrencyPairsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'exchange_markets_currency_pairs',
            function (Blueprint $table) {
                $table->boolean('active')->nullable()->default(false);
            }
        );

        // выбираем валюты для работы
        DB::table('exchange_markets_currency_pairs')
            ->where('currency_1_code', 'BTC')
            ->where('currency_2_code', 'USD')
            ->update(['active' => true]);
        DB::table('exchange_markets_currency_pairs')
            ->where('currency_1_code', 'ETH')
            ->where('currency_2_code', 'BTC')
            ->update(['active' => true]);
        DB::table('exchange_markets_currency_pairs')
            ->where('currency_1_code', 'ETH')
            ->where('currency_2_code', 'USD')
            ->update(['active' => true]);
        DB::table('exchange_markets_currency_pairs')
            ->where('currency_1_code', 'XRP')
            ->where('currency_2_code', 'BTC')
            ->update(['active' => true]);
        DB::table('exchange_markets_currency_pairs')
            ->where('currency_1_code', 'XRP')
            ->where('currency_2_code', 'USD')
            ->update(['active' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'exchange_markets_currency_pairs',
            function (Blueprint $table) {
                //
            }
        );
    }
}
