<?php

namespace Tests\Listeners;

use App\CurrencyPairsMetrics\Average;
use App\Jobs\UpdateCurrencyRates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CalculateCurrencyPairMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public function setUp()
    {
        parent::setUp();

        // чистим редис перед каждым тестом
        Redis::flushall();
    }

    /**
     * Как минимум средние сохраняются в редис
     *
     * @test
     *
     * @return void
     */
    public function itSavesMetrics()
    {
        $this->assertEquals(0, count(Average::getForPeriod('test.BTC.USD', 'buy', 1, 10)));

        // набиваем бд тестовыми данными
        $this->seed('TestExchangeMarketsDayRatesSeeder');

        // запускаем все расчёты
        UpdateCurrencyRates::dispatchNow();

        // проверяем наличие
        $this->assertGreaterThan(0, count(Average::getForPeriod('test.BTC.USD', 'buy', 1, 10)));

    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntSaveMetricsIfThereAreNotEnoughRates()
    {
        $this->assertEquals(0, count(Average::getForPeriod('test.BTC.USD', 'buy', 1, 10)));

        // запускаем все расчёты
        UpdateCurrencyRates::dispatchNow();

        // проверяем наличие в кеше
        $this->assertEquals(0, count(Average::getForPeriod('test.BTC.USD', 'buy', 1, 10)));

    }
}