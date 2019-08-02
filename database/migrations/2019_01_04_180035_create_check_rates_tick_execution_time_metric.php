<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckRatesTickExecutionTimeMetric extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('metrics')->insert(
            array(
                'sort'        => 500,
                'code'        => 'check_rates_tick_execution_time',
                'name'        => 'Длительность тика получения котировок, принятия решений, выставления ордеров, высчитывания метрик',
                'type'        => 'average',
                'public'      => false,
                'ideal_value' => 0,
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
        //
    }
}
