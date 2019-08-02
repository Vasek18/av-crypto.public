<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMetrics extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        DB::table('metrics')->insert(
            array(
                'sort'        => 100,
                'code'        => 'time_from_rates_check_to_order_creation',
                'name'        => 'Время от тика до создания ордера',
                'type'        => 'average',
                'public'      => true,
                'ideal_value' => 0,
            )
        );

        DB::table('metrics')->insert(
            array(
                'sort'        => 200,
                'code'        => 'time_order_being_open',
                'name'        => 'Время от создания ордера до его выкупа',
                'type'        => 'average',
                'public'      => true,
                'ideal_value' => 0,
            )
        );

        DB::table('metrics')->insert(
            array(
                'sort'        => 300,
                'code'        => 'successfully_created_orders_count',
                'name'        => 'Сколько создалось ордеров',
                'type'        => 'counter',
                'public'      => true,
                'ideal_value' => null,
            )
        );

        DB::table('metrics')->insert(
            array(
                'sort'        => 300,
                'code'        => 'unsuccessfully_created_orders_count',
                'name'        => 'Сколько ордеров не смогло создастся',
                'type'        => 'counter',
                'public'      => true,
                'ideal_value' => 0,
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        //
    }
}
