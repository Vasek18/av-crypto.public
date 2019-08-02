<?php

namespace Tests\Listeners;

use App\CurrencyPairsMetrics\Extremum;
use App\CurrencyPairsMetrics\Trend;
use App\Events\CurrencyPairRateChanged;
use App\Listeners\CalculateCurrencyPairMetrics;
use App\Models\CurrencyPairTrend;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CalculateCurrencyPairMetricsTrendsTest extends TestCase
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

    /** @test */
    public function getAllInterruptedLinesBetweenExtremumsTestTop()
    {
        // максимально длинный, не считая последнего
        $points = [
            [
                'value'     => 5,
                'timestamp' => 1,
            ],
            [
                'value'     => 1,
                'timestamp' => 2,
            ],
            [
                'value'     => 1,
                'timestamp' => 3,
            ],
            [
                'value'     => 3.5,
                'timestamp' => 4,
            ],
            [
                'value'     => 4,
                'timestamp' => 5,
            ],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points);
        $this->assertCount(2, $lines);
        $this->assertEquals(1, $lines[0]['x1']);
        $this->assertEquals(5, $lines[0]['y1']);
        $this->assertEquals(5, $lines[0]['x2']);
        $this->assertEquals(4, $lines[0]['y2']);
        $this->assertEquals(4, $lines[1]['x1']);
        $this->assertEquals(3.5, $lines[1]['y1']);
        $this->assertEquals(5, $lines[1]['x2']);
        $this->assertEquals(4, $lines[1]['y2']);

        // чуть менее длинный, не считая последнего
        $points = [
            ['value' => 1, 'timestamp' => 1,],
            ['value' => 5, 'timestamp' => 2,],
            ['value' => 1, 'timestamp' => 3,],
            ['value' => 2.5, 'timestamp' => 4,],
            ['value' => 3, 'timestamp' => 5,],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points);
        $this->assertCount(2, $lines);
        $this->assertEquals(2, $lines[0]['x1']);
        $this->assertEquals(5, $lines[0]['y1']);
        $this->assertEquals(5, $lines[0]['x2']);
        $this->assertEquals(3, $lines[0]['y2']);
        $this->assertEquals(4, $lines[1]['x1']);
        $this->assertEquals(2.5, $lines[1]['y1']);
        $this->assertEquals(5, $lines[1]['x2']);
        $this->assertEquals(3, $lines[1]['y2']);

        // два тренда, не считая последнего
        $points = [
            [
                'value'     => 7,
                'timestamp' => 1,
            ],
            [
                'value'     => 1,
                'timestamp' => 2,
            ],
            [
                'value'     => 4,
                'timestamp' => 3,
            ],
            [
                'value'     => 2,
                'timestamp' => 4,
            ],
            [
                'value'     => 5,
                'timestamp' => 5,
            ],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points);
        $this->assertCount(3, $lines);
        $this->assertEquals(1, $lines[0]['x1']);
        $this->assertEquals(7, $lines[0]['y1']);
        $this->assertEquals(5, $lines[0]['x2']);
        $this->assertEquals(5, $lines[0]['y2']);
        $this->assertEquals(3, $lines[1]['x1']);
        $this->assertEquals(4, $lines[1]['y1']);
        $this->assertEquals(5, $lines[1]['x2']);
        $this->assertEquals(5, $lines[1]['y2']);
        $this->assertEquals(4, $lines[2]['x1']);
        $this->assertEquals(2, $lines[2]['y1']);
        $this->assertEquals(5, $lines[2]['x2']);
        $this->assertEquals(5, $lines[2]['y2']);

        // прерывание на первой же точке
        $points = [
            ['value' => 1, 'timestamp' => 1,],
            ['value' => 2, 'timestamp' => 2,],
            ['value' => 5, 'timestamp' => 3,],
            ['value' => 7, 'timestamp' => 4,],
            ['value' => 5, 'timestamp' => 5,],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points);
        $this->assertCount(1, $lines);
        $this->assertEquals(4, $lines[0]['x1']);
        $this->assertEquals(7, $lines[0]['y1']);
        $this->assertEquals(5, $lines[0]['x2']);
        $this->assertEquals(5, $lines[0]['y2']);
    }

    /** @test */
    public function getAllInterruptedLinesBetweenExtremumsTestBottom()
    {
        // максимально длинный, не считая последнего
        $points = [
            ['value' => 1, 'timestamp' => 1,],
            ['value' => 5, 'timestamp' => 2,],
            ['value' => 5, 'timestamp' => 3,],
            ['value' => 2, 'timestamp' => 4,],
            ['value' => 1, 'timestamp' => 5,],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points, false);
        $this->assertCount(2, $lines);
        $this->assertEquals(1, $lines[0]['x1']);
        $this->assertEquals(1, $lines[0]['y1']);
        $this->assertEquals(5, $lines[0]['x2']);
        $this->assertEquals(1, $lines[0]['y2']);
        $this->assertEquals(4, $lines[1]['x1']);
        $this->assertEquals(2, $lines[1]['y1']);
        $this->assertEquals(5, $lines[1]['x2']);
        $this->assertEquals(1, $lines[1]['y2']);

        // чуть менее длинный, не считая последнего
        $points = [
            ['value' => 5, 'timestamp' => 1,],
            ['value' => 1, 'timestamp' => 2,],
            ['value' => 5, 'timestamp' => 3,],
            ['value' => 2.5, 'timestamp' => 4,],
            ['value' => 2, 'timestamp' => 5,],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points, false);
        $this->assertCount(2, $lines);
        $this->assertEquals(2, $lines[0]['x1']);
        $this->assertEquals(1, $lines[0]['y1']);
        $this->assertEquals(5, $lines[0]['x2']);
        $this->assertEquals(2, $lines[0]['y2']);
        $this->assertEquals(4, $lines[1]['x1']);
        $this->assertEquals(2.5, $lines[1]['y1']);
        $this->assertEquals(5, $lines[1]['x2']);
        $this->assertEquals(2, $lines[1]['y2']);

        // два тренда, не считая последнего
        $points = [
            ['value' => 1, 'timestamp' => 1,],
            ['value' => 5, 'timestamp' => 2,],
            ['value' => 2, 'timestamp' => 3,],
            ['value' => 5, 'timestamp' => 4,],
            ['value' => 1, 'timestamp' => 5,],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points, false);
        $this->assertCount(3, $lines);
        $this->assertEquals(1, $lines[0]['x1']);
        $this->assertEquals(1, $lines[0]['y1']);
        $this->assertEquals(5, $lines[0]['x2']);
        $this->assertEquals(1, $lines[0]['y2']);
        $this->assertEquals(3, $lines[1]['x1']);
        $this->assertEquals(2, $lines[1]['y1']);
        $this->assertEquals(5, $lines[1]['x2']);
        $this->assertEquals(1, $lines[1]['y2']);
        $this->assertEquals(4, $lines[2]['x1']);
        $this->assertEquals(5, $lines[2]['y1']);
        $this->assertEquals(5, $lines[2]['x2']);
        $this->assertEquals(1, $lines[2]['y2']);

        // прерывание на первой же точке
        $points = [
            ['value' => 5, 'timestamp' => 1,],
            ['value' => 4, 'timestamp' => 2,],
            ['value' => 1, 'timestamp' => 3,],
            ['value' => 2, 'timestamp' => 4,],
            ['value' => 5, 'timestamp' => 5,],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points, false);
        $this->assertCount(1, $lines);
        $this->assertEquals(4, $lines[0]['x1']);
        $this->assertEquals(2, $lines[0]['y1']);
        $this->assertEquals(5, $lines[0]['x2']);
        $this->assertEquals(5, $lines[0]['y2']);
    }

    /** @test */
    public function getAllInterruptedLinesBetweenExtremumsTestTopWithPermissiblePercent()
    {
        $points = [
            ['value' => 4, 'timestamp' => 1,],
            ['value' => 4, 'timestamp' => 2,],
            ['value' => 5, 'timestamp' => 3,],
            ['value' => 5.05, 'timestamp' => 4,],
            ['value' => 5, 'timestamp' => 5,],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points);
        $this->assertCount(1, $lines);
        $this->assertEquals(3, $lines[0]['x1']);
        $this->assertEquals(5, $lines[0]['y1']);
        $this->assertEquals(5, $lines[0]['x2']);
        $this->assertEquals(5, $lines[0]['y2']);

        $points = [
            ['value' => 7, 'timestamp' => 1,],
            ['value' => 7, 'timestamp' => 2,],
            ['value' => 5, 'timestamp' => 3,],
            ['value' => 4.95, 'timestamp' => 4,],
            ['value' => 5, 'timestamp' => 5,],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points, false);
        $this->assertCount(1, $lines);
        $this->assertEquals(3, $lines[0]['x1']);
        $this->assertEquals(5, $lines[0]['x2']);
    }

    /** @test */
    public function getAllInterruptedLinesBetweenExtremumsCombinesLinesIdTheyAreContinueEachOther()
    {
        $points = [
            ['value' => 1, 'timestamp' => 1,],
            ['value' => 2, 'timestamp' => 2,],
            ['value' => 3, 'timestamp' => 3,],
        ];
        $lines = Trend::getAllInterruptedLinesToRightPoint($points);
        $this->assertCount(1, $lines);
        $this->assertEquals(1, $lines[0]['x1']);
        $this->assertEquals(1, $lines[0]['y1']);
        $this->assertEquals(3, $lines[0]['x2']);
        $this->assertEquals(3, $lines[0]['y2']);
    }

    /** @test */
    public function testPairingLinesByCorrelation()
    {
        // 2 параллельные линии
        $points = [
            [
                'value'     => 4,
                'timestamp' => 1,
            ],
            [
                'value'     => 5,
                'timestamp' => 2,
            ],
        ];
        $topLines = Trend::getAllInterruptedLinesToRightPoint($points);

        $points = [
            [
                'value'     => 1,
                'timestamp' => 1,
            ],
            [
                'value'     => 2,
                'timestamp' => 2,
            ],
        ];
        $bottomLines = Trend::getAllInterruptedLinesToRightPoint($points);
        $corPairs = Trend::pairTopAndBottomLinesByCorrelation($topLines, $bottomLines);
        $this->assertCount(1, $corPairs);

        // 2 совсем не параллельные линии
        $points = [
            [
                'value'     => 4,
                'timestamp' => 1,
            ],
            [
                'value'     => 5,
                'timestamp' => 2,
            ],
        ];
        $topLines = Trend::getAllInterruptedLinesToRightPoint($points);

        $points = [
            [
                'value'     => 3,
                'timestamp' => 1,
            ],
            [
                'value'     => 2,
                'timestamp' => 2,
            ],
        ];
        $bottomLines = Trend::getAllInterruptedLinesToRightPoint($points);
        $corPairs = Trend::pairTopAndBottomLinesByCorrelation($topLines, $bottomLines);
        $this->assertEquals([], $corPairs);
    }

    /** @test */
    public function itSavesTrends()
    {
        $timestampForTest = date('U') - 100; // 100 тут просто рандомное число

        // записываем максимумы
        Extremum::store($this->currencyPair->code, 'maximum', $timestampForTest + 1, 4);
        Extremum::store($this->currencyPair->code, 'maximum', $timestampForTest + 2, 5);
        // записываем минимумы
        Extremum::store($this->currencyPair->code, 'minimum', $timestampForTest + 1, 1);
        Extremum::store($this->currencyPair->code, 'minimum', $timestampForTest + 2, 2);

        $currencyPairID = $this->getCurrencyPair('test', 'BTC', 'USD')->id;

        // рассчитываем тренды
        Trend::calculate($currencyPairID, $this->currencyPair->code);

        // через бд проверяем наличие тренда
        $this->assertEquals(1, CurrencyPairTrend::where('currency_pair_id', $currencyPairID)->count());
    }

    /** @test */
    public function itSavesTrendsTestFromRates()
    {
        $extremumsSideWidth = 30;
        $peregibsCount = 5;
        $price = 1000;
        $timestamp = date('U') - $extremumsSideWidth * ($peregibsCount + 1);
        $listener = new CalculateCurrencyPairMetrics();
        for ($i = 0; $i < $peregibsCount; $i++) { // с помощью перегибов получается такая структура //\//\
            if ($i % 2 == 0) { // цена вверх
                $addend = 10;
            } else { // цена вниз, но чуть медленнее
                $addend = -5;
            }
            for ($j = 0; $j < $extremumsSideWidth + 1; $j++) { // +1, так как 30 + 1 + 30
                $price += $addend;
                $timestamp += $j + $i;

                // набиваем тестовые данные
                $rate = CurrencyPairRate::save($this->currencyPair->code, $price, $price, $timestamp);

                // и запускаем расчёт метрик
                $event = new CurrencyPairRateChanged(
                    $this->currencyPair->id,
                    $this->currencyPair->code,
                    $rate,
                    $timestamp
                );
                $listener->handle($event);
            }
        }

        // через бд проверяем наличие тренда
        $this->assertEquals(1, CurrencyPairTrend::where('currency_pair_id', $this->currencyPair->id)->count());
        // сразу же проверим тип тренда
        $trend = CurrencyPairTrend::where('currency_pair_id', $this->currencyPair->id)->first();
        $this->assertEquals('up', $trend->type);
    }

    /** @test */
    public function itDoesntSaveTrendsFromUnconnectedLines()
    {
        $timestampForTest = date('U') - 100; // 100 тут просто рандомное число

        // записываем в редис максимумы
        Extremum::store($this->currencyPair->code, 'maximum', $timestampForTest + 1, 4);
        Extremum::store($this->currencyPair->code, 'maximum', $timestampForTest + 2, 5);
        Extremum::store($this->currencyPair->code, 'maximum', $timestampForTest + 7, 4);
        Extremum::store($this->currencyPair->code, 'maximum', $timestampForTest + 8, 5);
        // записываем в редис минимумы
        Extremum::store($this->currencyPair->code, 'minimum', $timestampForTest + 3, 1);
        Extremum::store($this->currencyPair->code, 'minimum', $timestampForTest + 4, 2);
        Extremum::store($this->currencyPair->code, 'minimum', $timestampForTest + 5, 1);
        Extremum::store($this->currencyPair->code, 'minimum', $timestampForTest + 6, 2);

        // рассчитываем тренды
        Trend::calculate($this->currencyPair->id, $this->currencyPair->code);

        // через бд проверяем наличие тренда
        $this->assertEquals(0, CurrencyPairTrend::where('currency_pair_id', $this->currencyPair->id)->count());
    }

    /** @test */
    public function extremumsPairIsntAnalyzedTwice()
    {
        $timestampForTest = date('U') - 100; // 100 тут просто рандомное число

        // записываем в редис максимумы
        Extremum::store($this->currencyPair->code, 'maximum', $timestampForTest + 1, 4);
        Extremum::store($this->currencyPair->code, 'maximum', $timestampForTest + 2, 5);
        // записываем в редис минимумы
        Extremum::store($this->currencyPair->code, 'minimum', $timestampForTest + 1, 1);
        Extremum::store($this->currencyPair->code, 'minimum', $timestampForTest + 2, 2);

        // рассчитываем тренды
        Trend::calculate($this->currencyPair->id, $this->currencyPair->code);

        // через бд проверяем наличие тренда
        $this->assertEquals(1, CurrencyPairTrend::where('currency_pair_id', $this->currencyPair->id)->count());

        // рассчитываем тренды второй раз
        Trend::calculate($this->currencyPair->id, $this->currencyPair->code);

        // проверяем, что тренд не рассчитывается повторно на тех же данных
        $this->assertEquals(1, CurrencyPairTrend::where('currency_pair_id', $this->currencyPair->id)->count());
    }

    /** @test */
    public function determineTrendTypeMethodTest()
    {
        $this->assertEquals('up', Trend::determineTrendType(4, 5, 1, 2));
        $this->assertEquals(false, Trend::determineTrendType(4, 5, 3, 2));
        $this->assertEquals('down', Trend::determineTrendType(7, 5, 4, 2));
        $this->assertEquals(false, Trend::determineTrendType(4, 4, 2, 2)); // хотя это флет очевидно
    }
}