<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(){
        // todo хак для старых mysql для миграций, не забыть удалить
        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(){
        //
    }
}
