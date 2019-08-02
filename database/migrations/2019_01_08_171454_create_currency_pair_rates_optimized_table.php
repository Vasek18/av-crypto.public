<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// денормолизация таблицы не принесла никаких плюсов, но минусов очень много
class CreateCurrencyPairRatesOptimizedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'currency_pair_rates_optimized',
            function (Blueprint $table) {
                $table->increments('id');
                $table->integer('currency_pair_id')->unsigned();
                $table->double('buy_price')->unsigned();
                $table->double('sell_price')->unsigned();
                $table->integer('timestamp')->unsigned();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currency_pair_rates_optimized');
    }
}
