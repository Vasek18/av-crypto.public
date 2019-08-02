<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExchangeMarketsTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('exchange_markets', function(Blueprint $table){
            $table->increments('id');
            $table->string('name');
            $table->string('code');
            $table->string('url')->nullable();
            $table->string('img_src')->nullable();
        });

        if (env('APP_ENV') != 'production'){
            DB::table('exchange_markets')->insert(
                array(
                    'name' => 'Test',
                    'url'  => 'test.ru',
                    'code' => 'test',
                )
            );
        }

        DB::table('exchange_markets')->insert(
            array(
                'name' => 'Exmo',
                'url'  => 'http://exmo.me/',
                'code' => 'exmo',
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('exchange_markets');
    }
}
