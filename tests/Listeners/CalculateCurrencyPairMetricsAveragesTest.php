<?php

namespace Tests\Listeners;

use App\CurrencyPairsMetrics\Average;
use App\Events\CurrencyPairRateChanged;
use App\Listeners\CalculateCurrencyPairMetrics;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CalculateCurrencyPairMetricsAveragesTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $hourIntervals = [1, 2, 4, 6, 8, 12, 24];
    public $currencyPair;

    public function setUp()
    {
        parent::setUp();

        $this->currencyPair = $this->getCurrencyPair();

        // чистим редис перед каждым тестом
        Redis::flushall();
    }

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

        // вспомогательная информация
        $timestampNow = date('U');
        $rate = CurrencyPairRate::save($this->currencyPair->code, 1, 1, $timestampNow);

        // запускаем расчёт метрик
        $listener = new CalculateCurrencyPairMetrics();
        $event = new CurrencyPairRateChanged(
            $this->currencyPair->id,
            $this->currencyPair->code,
            $rate,
            $timestampNow
        );
        $listener->handle($event);

        foreach ($this->hourIntervals as $hourInterval) {
            $ratesCollection = collect(
                CurrencyPairRate::getForPeriod($this->currencyPair->code, $hourInterval * MINUTES_IN_HOUR)
            );  // тут нельзя делать фильтрацию по диапозону timestamp, так как seeder отталкивается от суток, а не от текущего времени

            $buyMetricValue = Average::getLast(
                $this->currencyPair->code,
                'buy',
                $hourInterval
            );
            // проверяем правильность расчётов
            $this->assertEquals($ratesCollection->avg('buy_price'), $buyMetricValue['value']);
            // проверяем, что время метрик совпадает со временем котировок
            $this->assertEquals($timestampNow, $buyMetricValue['timestamp']);

            $sellMetricValue = Average::getLast(
                $this->currencyPair->code,
                'sell',
                $hourInterval
            );
            // проверяем правильность расчтов
            $this->assertEquals($ratesCollection->avg('sell_price'), $sellMetricValue['value']);
            // проверяем, что время метрик совпадает со временем котировок
            $this->assertEquals($timestampNow, $sellMetricValue['timestamp']);
        }
    }

    /**
     * @test
     *
     */
    public function periodGetterTest()
    {
        $testValuesCount = 10;
        $oldestMetricTimestamp = date('U') - ($testValuesCount - 1) * SECONDS_IN_MINUTE;

        // заполняем
        for ($i = 0; $i < $testValuesCount; $i++) {
            $timestamp = $oldestMetricTimestamp + $i * SECONDS_IN_MINUTE;
            Average::store($this->currencyPair->code, 'buy', 1, $timestamp, $i);
            Average::store($this->currencyPair->code, 'sell', 1, $timestamp, $i);
            Average::store($this->currencyPair->code, 'buy', 2, $timestamp, $i);
            Average::store($this->currencyPair->code, 'sell', 2, $timestamp, $i);
        }

        $this->assertEquals(4, count(Average::getForPeriod($this->currencyPair->code, 'buy', 1, 4)));
        $this->assertEquals(3, count(Average::getForPeriod($this->currencyPair->code, 'sell', 1, 3)));
        $this->assertEquals(5, count(Average::getForPeriod($this->currencyPair->code, 'buy', 2, 5)));
        $this->assertEquals(7, count(Average::getForPeriod($this->currencyPair->code, 'sell', 2, 7)));
    }
}