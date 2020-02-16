<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtstupTraderTempTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('otstup_trader_temp', function(Blueprint $table){
            $table->increments('id');
            $table->string('currency_1_code');
            $table->string('currency_2_code');
            $table->string('exchange_market_code');
            $table->double('sell_last_min', 20, 8)->nullable();
            $table->double('sell_last_max', 20, 8)->nullable();
            $table->double('buy_last_min', 20, 8)->nullable();
            $table->double('buy_last_max', 20, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('otstup_trader_temp');
    }
}
