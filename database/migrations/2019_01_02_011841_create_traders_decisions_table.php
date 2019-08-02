<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradersDecisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'traders_decisions',
            function (Blueprint $table) {
                $table->increments('id');
                $table->integer('currency_pair_id')->unsigned();
                $table->string('trader_code');
                $table->string('decision', 1);
                $table->integer('timestamp')->unsigned();
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
        Schema::dropIfExists('traders_decisions');
    }
}
