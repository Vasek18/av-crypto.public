<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesMetricsValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'currency_pairs_metrics_values',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('metric_code');
                $table->integer('currency_id')->unsigned();
                $table->string('value');
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
        Schema::dropIfExists('currency_pairs_metrics_values');
    }
}
