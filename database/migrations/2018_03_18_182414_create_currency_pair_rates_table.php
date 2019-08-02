<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCurrencyPairRatesTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('currency_pair_rates', function(Blueprint $table){
            $table->increments('id');
            $table->string('currency_1_code');
            $table->string('currency_2_code');
            $table->integer('exchange_market_id')->unsigned();
            $table->double('buy_price')->unsigned();
            $table->double('sell_price')->unsigned();
            $table->integer('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('currency_pair_rates');
    }
}
