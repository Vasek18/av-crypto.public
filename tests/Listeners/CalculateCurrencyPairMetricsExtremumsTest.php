<?php

namespace Tests\Listeners;

use App\CurrencyPairsMetrics\Extremum;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateCurrencyPairMetricsExtremumsTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $extremumsBorders = 30;

    /**
     * @test
     *
     * @return void
     */
    public function itFindsMaximumIfThereIsUpDownGraphic()
    {
        $timestampI = 0;

        // подготавливаем котировки
        for ($i = 0; $i <= $this->extremumsBorders + 1; $i++, $timestampI++) { // вверх; +1, чтобы был явный пик и нам нужна 31 котировка
            CurrencyPairRate::save($this->testCurrencyPair->code, $i, $i, $this->timestampNow + $timestampI);
        }
        for ($i = $this->extremumsBorders; $i > 0; $i--, $timestampI++) { // вниз
            CurrencyPairRate::save($this->testCurrencyPair->code, $i, $i, $this->timestampNow + $timestampI);
        }

        // запускаем расчёт
        Extremum::calculate($this->testCurrencyPair->code);

        // проверяем определился ли пик
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'maximum');
        $this->assertEquals(31, $metricValue['value']);
        $this->assertEquals($this->timestampNow + 31, $metricValue['timestamp']);

        // проверяем, что минимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'minimum');
        $this->assertNull($metricValue);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntFindMaximumIfThereIsUpFlatGraphic()
    {
        $timestampI = 0;

        // подготавливаем котировки
        for ($i = 0; $i <= $this->extremumsBorders + 1; $i++, $timestampI++) { // вверх; +1, чтобы был явный пик и нам нужна 31 котировка
            CurrencyPairRate::save($this->testCurrencyPair->code, $i, $i, $this->timestampNow + $timestampI);
        }
        for ($i = $this->extremumsBorders; $i > 0; $i--, $timestampI++) { // вбок
            CurrencyPairRate::save(
                $this->testCurrencyPair->code,
                $this->extremumsBorders + 1,
                $this->extremumsBorders + 1,
                $this->timestampNow + $timestampI
            );
        }

        // запускаем расчёт
        Extremum::calculate($this->testCurrencyPair->code);

        // проверяем, что максимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'maximum');
        $this->assertNull($metricValue);

        // проверяем, что минимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'minimum');
        $this->assertNull($metricValue);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntFindMaximumIfThereIsFlatDownGraphic()
    {
        $timestampI = 0;

        // подготавливаем котировки
        for ($i = 0; $i <= $this->extremumsBorders + 1; $i++, $timestampI++) { // вбок; +1, чтобы был явный пик и нам нужна 31 котировка
            CurrencyPairRate::save(
                $this->testCurrencyPair->code,
                $this->extremumsBorders + 1,
                $this->extremumsBorders + 1,
                $this->timestampNow + $timestampI
            );
        }
        for ($i = $this->extremumsBorders; $i > 0; $i--, $timestampI++) { // вниз
            CurrencyPairRate::save($this->testCurrencyPair->code, $i, $i, $this->timestampNow + $timestampI);
        }

        // запускаем расчёт
        Extremum::calculate($this->testCurrencyPair->code);

        // проверяем, что максимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'maximum');
        $this->assertNull($metricValue);

        // проверяем, что минимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'minimum');
        $this->assertNull($metricValue);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itFindsMinimumIfThereIsDownUpGraphic()
    {
        $timestampI = 0;

        // подготавливаем котировки
        for ($i = $this->extremumsBorders + 2; $i > 0; $i--, $timestampI++) { // вниз; +2, чтобы был явный пик + нам нужна 31 котировка + нулевая цена считается за ошибку, так как таких цен не бывает
            CurrencyPairRate::save($this->testCurrencyPair->code, $i, $i, $this->timestampNow + $timestampI);
        }
        for ($i = 2; $i <= $this->extremumsBorders + 1; $i++, $timestampI++) { // вверх; минимум у нас 1, поэтому начинаем с 2
            CurrencyPairRate::save($this->testCurrencyPair->code, $i, $i, $this->timestampNow + $timestampI);
        }

        // запускаем расчёт
        Extremum::calculate($this->testCurrencyPair->code);

        // проверяем определился ли минимум
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'minimum');
        $this->assertEquals(1, $metricValue['value']);
        $this->assertEquals($this->timestampNow + 31, $metricValue['timestamp']);

        // проверяем, что максимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'maximum');
        $this->assertNull($metricValue);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntFindMinimumIfThereIsDownFlatGraphic()
    {
        $timestampI = 0;

        // подготавливаем котировки
        for ($i = $this->extremumsBorders + 2; $i > 0; $i--, $timestampI++) { // вниз; +2, чтобы был явный пик + нам нужна 31 котировка + нулевая цена считается за ошибку, так как таких цен не бывает
            CurrencyPairRate::save($this->testCurrencyPair->code, $i, $i, $this->timestampNow + $timestampI);
        }
        for ($i = 1; $i <= $this->extremumsBorders + 1; $i++, $timestampI++) { // вбок; минимум у нас 1, поэтому начинаем с 2
            CurrencyPairRate::save($this->testCurrencyPair->code, 2, 2, $this->timestampNow + $timestampI);
        }

        // запускаем расчёт
        Extremum::calculate($this->testCurrencyPair->code);

        // проверяем, что максимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'maximum');
        $this->assertNull($metricValue);

        // проверяем, что минимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'minimum');
        $this->assertNull($metricValue);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntFindMinimumIfThereIsFlatUpGraphic()
    {
        $timestampI = 0;

        // подготавливаем котировки
        for ($i = $this->extremumsBorders + 2; $i > 0; $i--, $timestampI++) { // вбок
            CurrencyPairRate::save($this->testCurrencyPair->code, 2, 2, $this->timestampNow + $timestampI);
        }
        for ($i = 2; $i <= $this->extremumsBorders + 1; $i++, $timestampI++) { // вверх
            CurrencyPairRate::save($this->testCurrencyPair->code, $i, $i, $this->timestampNow + $timestampI);
        }

        // запускаем расчёт
        Extremum::calculate($this->testCurrencyPair->code);

        // проверяем, что максимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'maximum');
        $this->assertNull($metricValue);

        // проверяем, что минимума нет
        $metricValue = Extremum::getLast($this->testCurrencyPair->code, 'minimum');
        $this->assertNull($metricValue);
    }

    /**
     * @test
     *
     */
    public function periodGetterTest()
    {
        $pauseInMinutes = 10;
        $testValuesCount = 10;
        $oldestMetricTimestamp = date('U') - ($testValuesCount - 1) * SECONDS_IN_MINUTE * $pauseInMinutes;

        // заполняем
        for ($i = 0; $i < $testValuesCount; $i++) {
            $timestamp = $oldestMetricTimestamp + $i * SECONDS_IN_MINUTE * $pauseInMinutes; // каждые 10 минут
            Extremum::store(
                $this->testCurrencyPair->code,
                'maximum',
                $timestamp,
                $i // просто какое-то число, мы не это проверяем
            );
            Extremum::store(
                $this->testCurrencyPair->code,
                'minimum',
                $timestamp,
                $i // просто какое-то число, мы не это проверяем
            );
        }

        $this->assertEquals(4, count(Extremum::getMinimumsForPeriod($this->testCurrencyPair->code, 30)));
        $this->assertEquals(4, count(Extremum::getMinimumsForPeriod($this->testCurrencyPair->code, 32)));
        $this->assertEquals(4, count(Extremum::getMaximumsForPeriod($this->testCurrencyPair->code, 39)));
        $this->assertEquals(5, count(Extremum::getMaximumsForPeriod($this->testCurrencyPair->code, 41)));
    }
}