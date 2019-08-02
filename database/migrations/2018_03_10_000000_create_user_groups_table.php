<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserGroupsTable extends Migration{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('user_groups', function(Blueprint $table){
            $table->increments('id');
            $table->string('code');
            $table->string('name');
        });

        DB::table('user_groups')->insert(
            array(
                'code' => 'admin',
                'name' => 'Администраторы',
            )
        );
        DB::table('user_groups')->insert(
            array(
                'code' => 'normal',
                'name' => 'Простые пользователи',
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::drop('user_groups');
    }
}
