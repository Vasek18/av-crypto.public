<?php

Route::get('/', 'IndexController@index')->name('index')->middleware('guest');
Route::get('/get-pair-info', 'IndexController@getPairInfo');

Auth::routes(['verify' => true]);

Route::group(
    [
        'middleware' => [
            'auth',
            'verified',
        ],
    ],
    function () {
        Route::get('/home', 'HomeController@index')->name('home');

        Route::resource('exchange', 'ExchangeMarketController');
        Route::post('exchange/{exchangeMarket}/connect', 'ExchangeMarketController@connect');
        Route::get('exchange/{exchangeMarket}/currency_pairs', 'ExchangeMarketController@getCurrencyPairs');

        Route::resource('user-accounts', 'ExchangeMarketUserAccountController');

        Route::resource('basket', 'BasketController');

        Route::group(
            [
                'middleware' => ['admin'],
                'prefix'     => 'oko',
            ],
            function () {
                Route::get('metrics', 'Admin\MetricsController@index');

                Route::get('logs', 'Admin\LogsController@index');

                Route::get('past-analysis', 'Admin\PastAnalysisController@index');
                Route::get('past-analysis/get-pair-info', 'Admin\PastAnalysisController@getPairInfo');
            }
        );
    }
);