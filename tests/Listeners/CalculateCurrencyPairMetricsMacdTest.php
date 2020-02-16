<?php

namespace Tests\Listeners;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\Macd;
use App\Jobs\UpdateCurrencyRates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateCurrencyPairMetricsMacdTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $hourIntervals = [1, 2, 4, 6, 8, 12, 24];

    /**
     * @test
     *
     * @return void
     */
    public function itCalculatesMacdForNeededAveragesPairs()
    {
        // чтобы не рассчитывать средние слишком долго, наполним редис ими сразу
        foreach (Average::$hourIntervals as $c => $hourInterval) {
            Average::store($this->testCurrencyPair->code, 'buy', $hourInterval, $this->timestampNow, $c + 1);
        }

        // запускаем расчёт метрик
        UpdateCurrencyRates::dispatchNow();

        // проходимся по всем парам средних и сами рассчитываем разницу для проверки macd
        foreach (Macd::getAllAveragePeriodsPairs() as list($hourIntervalFast, $hourIntervalSlow)) {
            // получаем средние обоих диапазонов
            $fastAverageValue = Average::getLast(
                $this->testCurrencyPair->code,
                'buy',
                $hourIntervalFast
            )['value'];
            $slowAverageValue = Average::getLast(
                $this->testCurrencyPair->code,
                'buy',
                $hourIntervalSlow
            )['value'];

            // получаем macd
            $macd = Macd::getLast(
                $this->testCurrencyPair->code,
                $hourIntervalFast,
                $hourIntervalSlow
            );

            // проверяем значение и само наличие метрики
            $this->assertNotEquals(0, $macd['value']); // наличие
            $this->assertEquals($fastAverageValue - $slowAverageValue, $macd['value']); // значение
        }
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
        for ($minutes = 0; $minutes < $testValuesCount; $minutes++) {
            $timestamp = $oldestMetricTimestamp + $minutes * SECONDS_IN_MINUTE;
            Macd::store(
                $this->testCurrencyPair->code,
                1,
                2,
                $timestamp,
                $minutes // просто какое-то число, мы не это проверяем
            );

            Macd::store(
                $this->testCurrencyPair->code,
                1,
                24,
                $timestamp,
                $minutes // просто какое-то число, мы не это проверяем
            );
        }

        $this->assertEquals(4, count(Macd::getForPeriod($this->testCurrencyPair->code, 1, 2, 4)));
        $this->assertEquals(5, count(Macd::getForPeriod($this->testCurrencyPair->code, 1, 24, 5)));
    }

    /**
     * @test
     */
    public function getAllAveragePairsPeriodsTest()
    {
        $avgHourIntervals = Average::$hourIntervals;

        $avgPeriodsPairs = Macd::getAllAveragePeriodsPairs();

        $this->assertEquals([$avgHourIntervals[0], $avgHourIntervals[1]], $avgPeriodsPairs[0]);
        $this->assertEquals([$avgHourIntervals[0], $avgHourIntervals[2]], $avgPeriodsPairs[1]);
        $this->assertEquals(
            $this->countPossibleCombinations(count($avgHourIntervals), 2),
            count($avgPeriodsPairs)
        );
    }

    public static function factorial($n)
    {
        if ($n == 0) {
            return 1;
        }

        return $n * self::factorial($n - 1);
    }

    public function countPossibleCombinations($elementsCount, $oneCombinationElementsCount)
    {
        return $this->factorial($elementsCount)
            /
            (
                $this->factorial($elementsCount - $oneCombinationElementsCount)
                *
                $oneCombinationElementsCount
            );
    }
}