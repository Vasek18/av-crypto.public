<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserExchangeMarketAccountsTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('exchange_market_user_accounts', function(Blueprint $table){
            $table->increments('id');
            $table->integer('exchange_market_id')->unsigned();
//            $table->foreign('exchange_market_id')->references('id')->on('exchange_markets')->onDelete('cascade');
            $table->integer('user_id')->unsigned();
//            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('active')->default(false);
            $table->string('public_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('exchange_market_user_accounts');
    }
}
