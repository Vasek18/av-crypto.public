<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('orders', function(Blueprint $table){
            $table->increments('id');
            $table->string('currency_1_code');
            $table->string('currency_2_code');
            $table->integer('basket_id');
            $table->integer('exchange_market_id');
            $table->double('amount', 20, 8)->unsigned();
            $table->double('gained_amount', 20, 8)->unsigned();
            $table->double('price', 20, 8)->unsigned();
            $table->string('action');
            $table->string('id_at_exm');
            $table->boolean('done')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('orders');
    }
}
