<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrencyPairEventObservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'currency_pair_event_observations',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('currency_pair_code');
                $table->string('event_code');
                $table->integer('top_hits')->unsigned()->default(0);
                $table->integer('bottom_hits')->unsigned()->default(0);
                $table->integer('missed')->unsigned()->default(0);
                $table->integer('percent')->unsigned();
                $table->integer('period')->unsigned();
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
        Schema::dropIfExists('currency_pair_event_observations');
    }
}
