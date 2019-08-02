<?php

namespace Tests\Listeners;

use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\Events\CurrencyPairRateChanged;
use App\Listeners\CalculateCurrencyPairMetrics;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CalculateCurrencyPairMetricsMacdAveragesTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $currencyPair;

    public function setUp()
    {
        parent::setUp();

        $this->currencyPair = $this->getCurrencyPair();

        // чистим редис перед каждым тестом
        Redis::flushall();
    }

    /**
     * Проверка, что средние macd считаются правильно через полный запуск слушателя
     *
     * @test
     *
     * @return void
     */
    public function itCalculatesMacdAverage()
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
        $listener->handle($event); // дважды, чтобы было больше одного значения у макд

        // посчитаем явно среднее макд только для пары 1-2
        $values = Macd::getForPeriod(
            $this->currencyPair->code,
            1,
            2,
            MINUTES_IN_HOUR
        );

        $valuesSum = array_sum(array_column($values, 'value'));
        $average = $valuesSum / count($values);

        $macdAverageValue = MacdAverage::getLast(
            $this->currencyPair->code,
            1,
            2,
            1
        )['value'];

        $this->assertEquals($average, $macdAverageValue);
        $this->assertGreaterThan(0, $macdAverageValue);
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
            MacdAverage::store(
                $this->currencyPair->code,
                1,
                2,
                1,
                $timestamp,
                $i // просто какое-то число, мы не это проверяем
            );
        }

        $this->assertEquals(4, count(MacdAverage::getForPeriod($this->currencyPair->code, 1, 2, 1, 4)));
    }
}