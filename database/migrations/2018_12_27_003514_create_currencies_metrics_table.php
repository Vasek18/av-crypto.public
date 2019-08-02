<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'currency_pairs_metrics',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('code')->unique();
            }
        );

        // создаём первые метрики
        DB::table('currency_pairs_metrics')->insert(
            array(
                'code' => 'avg24',
                'name' => 'Средняя цена сделки за 24 часа',
            )
        );
        DB::table('currency_pairs_metrics')->insert(
            array(
                'code' => 'high24',
                'name' => 'Максимальная цена сделки за 24 часа',
            )
        );
        DB::table('currency_pairs_metrics')->insert(
            array(
                'code' => 'low24',
                'name' => 'Минимальная цена сделки за 24 часа',
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
        Schema::dropIfExists('currency_pairs_metrics');
    }
}
