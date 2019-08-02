<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStrategyFieldToBasketsTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::table('baskets', function(Blueprint $table){
            $table->string('strategy');
        });

        // чтобы уже созданные корзинки тоже работали
        $baskets = DB::table('baskets')->where('strategy', '')->update(['strategy' => DEFAULT_TRADER]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::table('baskets', function(Blueprint $table){
            //
        });
    }
}
