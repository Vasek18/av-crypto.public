<?php

namespace Tests\Listeners;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\Macd;
use App\Jobs\UpdateCurrencyRates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CalculateCurrencyPairMetricsMacdTest extends TestCase
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
     * @test
     *
     * @return void
     */
    public function itCalculatesMacdForNeededAveragesPairs()
    {
        // чтобы не рассчитывать средние слишком долго, наполним редис ими сразу
        foreach ($this->hourIntervals as $c => $hourInterval) {
            Average::store($this->currencyPair->code, 'buy', $hourInterval, date('U'), $c + 1);
        }

        // запускаем расчёт метрик
        UpdateCurrencyRates::dispatchNow();

        // проходимся по всем парам средних и сами рассчитываем разницу для проверки macd. Таких пар 21 при 7 средних
        for ($i = 0; $i < count($this->hourIntervals) - 1; $i++) { // идём по всем диапазонам средних, кроме последнего
            for ($j = count($this->hourIntervals) - 1; $j > $i; $j--) { // по всем диапазонам выше, чем в первом цикле
                // получаем средние обоих диапазонов
                $hourIntervalFast = $this->hourIntervals[$i];
                $hourIntervalSlow = $this->hourIntervals[$j];
                $fastAverageValue = Average::getLast(
                    $this->currencyPair->code,
                    'buy',
                    $hourIntervalFast
                )['value'];
                $slowAverageValue = Average::getLast(
                    $this->currencyPair->code,
                    'buy',
                    $hourIntervalSlow
                )['value'];

                // получаем macd
                $macd = Macd::getLast(
                    $this->currencyPair->code,
                    $hourIntervalFast,
                    $hourIntervalSlow
                );

                // проверяем значение и само наличие метрики
                $this->assertNotEquals(0, $macd['value']); // самая важная проверка тут
                $this->assertEquals($fastAverageValue - $slowAverageValue, $macd['value']);
            }
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
            Macd::store(
                $this->currencyPair->code,
                1,
                2,
                $timestamp,
                $i // просто какое-то число, мы не это проверяем
            );

            Macd::store(
                $this->currencyPair->code,
                1,
                24,
                $timestamp,
                $i // просто какое-то число, мы не это проверяем
            );
        }

        $this->assertEquals(4, count(Macd::getForPeriod($this->currencyPair->code, 1, 2, 4)));
        $this->assertEquals(5, count(Macd::getForPeriod($this->currencyPair->code, 1, 24, 5)));
    }
}