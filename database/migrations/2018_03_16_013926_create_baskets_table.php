<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBasketsTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('baskets', function(Blueprint $table){
            $table->increments('id');
            $table->double('start_sum', 20, 8); // в начальной валюте
            $table->string('currency_1_code');
            $table->string('currency_2_code');
            $table->integer('account_id');
            //            $table->foreign('account_id')->references('id')->on('user_exchange_market_accounts')->onDelete('cascade');
            $table->integer('exchange_market_id');
            //            $table->foreign('exchange_market_id')->references('id')->on('exchange_markets')->onDelete('cascade');
            $table->double('currency_1_last_amount', 20, 8)->nullable();
            $table->double('currency_2_last_amount', 20, 8)->nullable();
            $table->string('next_action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('baskets');
    }
}
