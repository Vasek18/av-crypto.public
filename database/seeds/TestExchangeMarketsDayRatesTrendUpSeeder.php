<?php

use App\Events\CurrencyPairRateChanged;
use App\Listeners\CalculateCurrencyPairMetrics;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use App\Trading\CurrencyPairRate;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Redis;

// наполняем бд тестовыми котировками, в которых есть тренд вверх, попутно рассчитывая индикаторы
class TestExchangeMarketsDayRatesTrendUpSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run()
    {
        // чистим
        Redis::flushall();

        // данные о валюте
        $currency1Code = 'BTC';
        $currency2Code = 'USD';
        $exchangeMarket = ExchangeMarket::where('code', 'test')->first();
        $currencyPair = ExchangeMarketCurrencyPair::where('exchange_market_id', $exchangeMarket->id)
            ->where('currency_1_code', $currency1Code)
            ->where('currency_2_code', $currency2Code)
            ->first();

        $extremumsSideWidth = 70; // чтобы можно было проверять часовые тренды
        $peregibsCount = 5; // 5*40 получается на 200 минут
        $price = 1000;
        $timestamp = Carbon::today()->startOfDay()->timestamp;
        $listener = new CalculateCurrencyPairMetrics();
        for ($i = 0; $i < $peregibsCount; $i++) { // с помощью перегибов получается такая структура //\//\
            if ($i % 2 == 0) { // цена вверх
                $addend = 10;
            } else { // цена вниз, но чуть медленнее
                $addend = -5;
            }
            for ($j = 0; $j < $extremumsSideWidth; $j++) { // todo тут наверное нужно <= поставить
                $price += $addend;
                $randomPrice = rand($price - 10, $price + 10);

                // набиваем тестовые котировки
                $rate = CurrencyPairRate::save(
                    $currencyPair->code,
                    $randomPrice,
                    $randomPrice,
                    $timestamp + ($i * $extremumsSideWidth + $j) * SECONDS_IN_MINUTE
                );

                // запускаем расчёт метрик
                $event = new CurrencyPairRateChanged(
                    $currencyPair->id,
                    $currencyPair->code,
                    $rate
                );
                $listener->handle($event);

                // переключаем минуту
                $timestamp++;
            }
        }
    }
}
