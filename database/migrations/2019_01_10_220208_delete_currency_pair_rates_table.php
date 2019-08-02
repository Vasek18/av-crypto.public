<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Удаляем старую денормализованную таблицу. Выигрыша от денормализации мы не получили, но получили проблемы с размером таблицы на диске и скоростью вставки в бд
class DeleteCurrencyPairRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('currency_pair_rates');
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
