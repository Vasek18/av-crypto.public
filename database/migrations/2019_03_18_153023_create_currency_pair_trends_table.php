<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrencyPairTrendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'currency_pair_trends',
            function (Blueprint $table) {
                $table->increments('id');
                $table->integer('currency_pair_id')->unsigned();
                $table->string('type')->nullable();
                $table->integer('lt_x')->nullable()->unsigned();
                $table->integer('lt_y')->nullable()->unsigned();
                $table->integer('lb_x')->nullable()->unsigned();
                $table->integer('lb_y')->nullable()->unsigned();
                $table->integer('rt_x')->nullable()->unsigned();
                $table->integer('rt_y')->nullable()->unsigned();
                $table->integer('rb_x')->nullable()->unsigned();
                $table->integer('rb_y')->nullable()->unsigned();
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
        Schema::dropIfExists('currency_pair_trends');
    }
}
