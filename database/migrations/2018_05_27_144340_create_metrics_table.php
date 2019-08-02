<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMetricsTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('metrics', function(Blueprint $table){
            $table->increments('id');
            $table->integer('sort')->unsigned()->nullable()->default(500);
            $table->string('code');
            $table->string('name');
            $table->text('type');
            $table->boolean('public')->default(false);
            $table->integer('ideal_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('metrics');
    }
}
