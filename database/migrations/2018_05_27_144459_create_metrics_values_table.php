<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetricsValuesTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('metrics_values', function(Blueprint $table){
            $table->increments('id');
            $table->integer('metrics_id')->unsigned();
            $table->foreign('metrics_id')->references('id')->on('metrics')->onDelete('cascade');
            $table->integer('timestamp')->unsigned();
            $table->double('value')->unsigned();
            $table->integer('counter')->unsigned()->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('metrics_values');
    }
}
