<?php

namespace Tests\Listeners;

use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\Events\CurrencyPairRateChanged;
use App\Listeners\CalculateCurrencyPairMetrics;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateCurrencyPairMetricsMacdAveragesTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

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
        $rate = CurrencyPairRate::save($this->testCurrencyPair->code, 1, 1, $this->timestampNow);

        // запускаем расчёт метрик
        $listener = new CalculateCurrencyPairMetrics();
        $event = new CurrencyPairRateChanged(
            $this->testCurrencyPair->id,
            $this->testCurrencyPair->code,
            $rate
        );
        $listener->handle($event);
        $listener->handle($event); // дважды, чтобы было больше одного значения у макд

        // посчитаем явно среднее макд только для пары 1-2
        $values = Macd::getForPeriod(
            $this->testCurrencyPair->code,
            1,
            2,
            MINUTES_IN_HOUR
        );

        // расчётный результат
        $valuesSum = array_sum(array_column($values, 'value'));
        $average = $valuesSum / count($values);

        $macdAverageValue = MacdAverage::getLast(
            $this->testCurrencyPair->code,
            1,
            2,
            1
        )['value'];

        $this->assertEquals($average, $macdAverageValue);
        $this->assertGreaterThan(
            0,
            $macdAverageValue
        ); // проверка, что сидер до сих пор позволяет создавать ненулевые средние
    }

    /**
     * @test
     *
     */
    public function periodGetterTest()
    {
        $testValuesCount = 10;
        $oldestMetricTimestamp = $this->timestampNow - ($testValuesCount - 1) * SECONDS_IN_MINUTE;

        // заполняем
        for ($i = 0; $i < $testValuesCount; $i++) {
            $timestamp = $oldestMetricTimestamp + $i * SECONDS_IN_MINUTE;
            MacdAverage::store(
                $this->testCurrencyPair->code,
                1,
                2,
                1,
                $timestamp,
                $i // просто какое-то число, мы не это проверяем
            );
        }

        $this->assertEquals(4, count(MacdAverage::getForPeriod($this->testCurrencyPair->code, 1, 2, 1, 4)));
    }
}