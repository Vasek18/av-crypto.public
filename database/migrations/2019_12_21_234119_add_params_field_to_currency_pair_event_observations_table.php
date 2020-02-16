<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddParamsFieldToCurrencyPairEventObservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'currency_pair_event_observations',
            function (Blueprint $table) {
                $table->text('params')->nullable(); // если обновить MariaDB, можно будет использовать тип json
            }
        );

        // переводим старые события на новую структуру
        DB::table('currency_pair_event_observations')->orderBy('id')->where('event_code', 'like', '%_{%')->each(
            function ($observation) {
                list($type, $params) = explode('{', $observation->event_code);
                if (substr($type, -1, 1) === '_') {
                    $type = substr($type, 0, -1);
                }
                DB::table('currency_pair_event_observations')->where('id', $observation->id)->update(
                    [
                        'event_code' => $type,
                        'params'     => '{'.$params,
                    ]
                );
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
        Schema::table(
            'currency_pair_event_observations',
            function (Blueprint $table) {
                //
            }
        );
    }
}
