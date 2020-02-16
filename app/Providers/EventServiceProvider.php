<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'App\Events\CurrencyPairRateChanged' => [
            'App\Listeners\CalculateCurrencyPairMetrics',
            'App\Listeners\FireCurrencyPairEvents',
            // вызывается здесь, потому что события отталкиваются от цены и времени котировки
            'App\Listeners\Trade',
        ],
        'App\Events\CurrencyPairEventFired'  => [
            'App\Listeners\OnCurrencyPairEventFired',
        ],
        Registered::class                    => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        //
    }
}
