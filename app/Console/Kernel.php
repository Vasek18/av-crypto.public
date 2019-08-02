<?php

namespace App\Console;

use App\Jobs\CheckOrders;
use App\Jobs\ClearDB;
use App\Jobs\UpdateCurrenciesSettings;
use App\Jobs\UpdateCurrencyRates;
use App\Jobs\UpdateOrderBooksInfo;
use App\Models\Metrics\Metrics;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Schema;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // условие из-за того, что на этом моменте постоянно падают команды артисана
        if (Schema::hasTable('exchange_markets_currency_pairs')) {
            // обновляем цены валют
            $schedule->job(new UpdateCurrencyRates)->everyMinute();

            // обновляем стаканы валют
            $schedule->job(new UpdateOrderBooksInfo())->everyMinute();

            // проверяем ордера
            $schedule->job(new CheckOrders)->everyMinute();

            // обновляем настройки валют
            $schedule->job(new UpdateCurrenciesSettings)->daily();

            // очистка бд от устаревших данных
            $schedule->job(new ClearDB)->daily();

            // считаем запросные метрики сайта
            $schedule->call(
                function () {
                    Metrics::calculateMetrics();
                }
            )->dailyAt('23:50'); // считаем прямо перед полуночью, а не после, иначе получаем нелогичные данные
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
