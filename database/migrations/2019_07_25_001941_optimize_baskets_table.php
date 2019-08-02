<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OptimizeBasketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'baskets',
            function (Blueprint $table) {
                $table->dropColumn(['currency_1_code', 'currency_2_code', 'exchange_market_id']);
                $table->integer('currency_pair_id')->unsigned();
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
        Schema::table(
            'baskets',
            function (Blueprint $table) {
                //
            }
        );
    }
}
