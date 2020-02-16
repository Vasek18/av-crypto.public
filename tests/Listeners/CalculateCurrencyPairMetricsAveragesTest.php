<?php

namespace Tests\Listeners;

use App\CurrencyPairsMetrics\Average;
use App\Events\CurrencyPairRateChanged;
use App\Listeners\CalculateCurrencyPairMetrics;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateCurrencyPairMetricsAveragesTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    /**
     * Проверка, что средние считаются правильно через полный запуск слушателя
     *
     * @test
     *
     * @return void
     */
    public function itCalculatesAveragesRight()
    {
        // набиваем бд тестовыми данными
        $this->seed('TestExchangeMarketsDayRatesSeeder');

        // запускаем расчёт метрик
        $listener = new CalculateCurrencyPairMetrics();
        $event = new CurrencyPairRateChanged(
            $this->testCurrencyPair->id,
            $this->testCurrencyPair->code,
            CurrencyPairRate::save($this->testCurrencyPair->code, 1, 1, $this->timestampNow)
        );
        $listener->handle($event);

        // проверяем правильность расчётов
        foreach (Average::$hourIntervals as $hourInterval) {
            $ratesCollection = collect(
                CurrencyPairRate::getForPeriod($this->testCurrencyPair->code, $hourInterval * MINUTES_IN_HOUR)
            );  // тут нельзя делать фильтрацию по диапозону timestamp, так как seeder отталкивается от суток, а не от текущего времени

            $buyMetricValue = Average::getLast(
                $this->testCurrencyPair->code,
                'buy',
                $hourInterval
            );
            // проверяем правильность расчётов
            $this->assertEquals($ratesCollection->avg('buy_price'), $buyMetricValue['value']);
            // проверяем, что время метрик совпадает со временем котировок
            $this->assertEquals($this->timestampNow, $buyMetricValue['timestamp']);

            $sellMetricValue = Average::getLast(
                $this->testCurrencyPair->code,
                'sell',
                $hourInterval
            );
            // проверяем правильность расчтов
            $this->assertEquals($ratesCollection->avg('sell_price'), $sellMetricValue['value']);
            // проверяем, что время метрик совпадает со временем котировок
            $this->assertEquals($this->timestampNow, $sellMetricValue['timestamp']);
        }
    }

    /**
     * @dataProvider periodGetterTestProvider
     * @test
     * @param $interval
     * @param $type
     */
    public function periodGetterTest($interval, $type)
    {
        $testValuesCount = 10;
        $oldestMetricTimestamp = $this->timestampNow - ($testValuesCount - 1) * SECONDS_IN_MINUTE;

        // заполняем
        for ($minutes = 0; $minutes < $testValuesCount; $minutes++) {
            $timestamp = $oldestMetricTimestamp + $minutes * SECONDS_IN_MINUTE;
            Average::store($this->testCurrencyPair->code, $type, $interval, $timestamp, $minutes);
        }

        for ($minutes = 3; $minutes < 7; $minutes++) {
            $this->assertEquals(
                $minutes,
                count(Average::getForPeriod($this->testCurrencyPair->code, $type, $interval, $minutes))
            );
        }
    }

    public function periodGetterTestProvider()
    {
        return [
            [1, 'buy'],
            [1, 'sell'],
            [2, 'buy'],
            [2, 'sell'],
        ];
    }

    /**
     * @test
     */
    public function ifTimestampIsNotMultipleTo60ClearingDoesntFail()
    {
        $bigRandomNumberOfMinutes = 100;
        $notMultipleTo60Number = 70; // в секундах всё равно должно быть больше минуты, иначе ни одно значение не удалится

        // сохраняем 2 значения метрики
        Average::store($this->testCurrencyPair->code, 'buy', 1, $this->timestampNow - SECONDS_IN_MINUTE, 100);
        Average::store($this->testCurrencyPair->code, 'buy', 1, $this->timestampNow, 100);

        // должно удалиться лишь одно значение
        Average::clearOlderThan($this->testCurrencyPair->code, $this->timestampNow - $notMultipleTo60Number);

        $this->assertEquals(
            1,
            count(Average::getForPeriod($this->testCurrencyPair->code, 'buy', 1, $bigRandomNumberOfMinutes))
        );
    }

    /**
     * @test
     */
    public function itCanDeleteLastElement()
    {
        Average::store($this->testCurrencyPair->code, 'buy', 1, $this->timestampNow, 100);

        Average::clearOlderThan($this->testCurrencyPair->code, $this->timestampNow);

        $this->assertEquals(
            0,
            count(Average::getForPeriod($this->testCurrencyPair->code, 'buy', 1, SECONDS_IN_MINUTE + SECONDS_IN_MINUTE))
        );
    }
}