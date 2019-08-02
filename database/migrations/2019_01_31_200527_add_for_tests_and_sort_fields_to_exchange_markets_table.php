<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddForTestsAndSortFieldsToExchangeMarketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'exchange_markets',
            function (Blueprint $table) {
                $table->boolean('for_tests')->nullable()->default(false);
                $table->integer('sort')->nullable()->unsigned()->default(100);
            }
        );

        // проставляем всем уже созданным биржам сортировку 100
        DB::table('exchange_markets')->where('sort', null)->update(['sort' => 100]);

        // тестовой бирже ставим for_tests true
        DB::table('exchange_markets')->where('code', 'test')->update(['for_tests' => true]);

        // exmo ставим for_tests false
        DB::table('exchange_markets')->where('code', 'exmo')->update(['for_tests' => false]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'exchange_markets',
            function (Blueprint $table) {
                //
            }
        );
    }
}
