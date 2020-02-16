<?php

namespace Tests\Traders;

use App\CurrencyPairsMetrics\Average;
use App\Traders\AveragePriceTrader;
use App\Traders\TraderDecision;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AveragePriceTraderTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $currentMinuteTimestamp;

    public function setUp()
    {
        parent::setUp();

        $this->seed('TestExchangeMarketsDayRatesSeeder');

        $this->currentMinuteTimestamp = floor($this->timestampNow / SECONDS_IN_MINUTE) * SECONDS_IN_MINUTE;
    }

    public function setAveragesInCache($graphicType, $count = null)
    {
        // нужно именно 31 значение, а не 30, так как значения берутся с двух границ, а не только с одной
        $values = [];
        if ($graphicType == 'buy') {
            $values = [
                15,
                14,
                13,
                12,
                11,
                10,
                9,
                8,
                7,
                6,
                5,
                4,
                3,
                2,
                1,
                2,
                3,
                4,
                5,
                6,
                7,
                8,
                9,
                10,
                11,
                12,
                13,
                14,
                15,
            ];
        }
        if ($graphicType == 'sell') {
            $values = [
                1,
                2,
                3,
                4,
                5,
                6,
                7,
                8,
                9,
                10,
                11,
                12,
                13,
                14,
                15,
                14,
                13,
                12,
                11,
                10,
                9,
                8,
                7,
                6,
                5,
                4,
                3,
                2,
                1,
            ];
        }
        if ($graphicType == 'flat') {
            $values = [
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
                10,
                11,
            ];
        }
        if ($graphicType == 'near_flat_buy') {
            $values = [
                11.0015,
                11.0014,
                11.0013,
                11.0012,
                11.0011,
                11.0010,
                11.0009,
                11.0008,
                11.0007,
                11.0006,
                11.0005,
                11.0004,
                11.0003,
                11.0002,
                11.0001,
                11.0002,
                11.0003,
                11.0004,
                11.0005,
                11.0006,
                11.0007,
                11.0008,
                11.0009,
                11.0010,
                11.0011,
                11.0012,
                11.0013,
                11.0014,
                11.0015,
            ];
        }

        $averageType = 'buy';
        if ($graphicType == 'sell') {
            $averageType = 'sell';
        }

        if ($count) {
            $values = array_slice($values, 0, $count);
        }

        for ($i = 0; $i < count($values); $i++) {
            Average::store(
                $this->testCurrencyPair->code,
                $averageType,
                1,
                $this->currentMinuteTimestamp - (SECONDS_IN_MINUTE * $i),
                $values[$i]
            );
        }
    }

    /**
     * @test
     *
     * @return void
     */
    public function isThereIsDownUpPeregibMethodTest()
    {
        $trader = new AveragePriceTrader($this->testCurrencyPair->code);

        $this->assertTrue($trader->isThereIsDownUpPeregib([3, 2, 1, 2, 3]));
        $this->assertTrue($trader->isThereIsDownUpPeregib([7, 5, 3, 5, 7]));
        $this->assertTrue($trader->isThereIsDownUpPeregib([7, 5, 1, 2, 3]));
        $this->assertFalse($trader->isThereIsDownUpPeregib([2, 2, 1, 2, 2]));
        $this->assertFalse($trader->isThereIsDownUpPeregib([1, 2, 3, 2, 1]));
        $this->assertFalse($trader->isThereIsDownUpPeregib([2, 2, 1, 2, 3]));
        $this->assertFalse($trader->isThereIsDownUpPeregib([3, 2, 1, 2, 2]));
        $this->assertFalse($trader->isThereIsDownUpPeregib([3, 2, 2, 2, 3]));
    }

    /**
     * @test
     *
     * @return void
     */
    public function isThereIsUpDownPeregibMethodTest()
    {
        $trader = new AveragePriceTrader($this->testCurrencyPair->code);

        $this->assertTrue($trader->isThereIsUpDownPeregib([1, 2, 3, 2, 1]));
        $this->assertFalse($trader->isThereIsUpDownPeregib([1, 2, 2, 2, 1]));
        $this->assertFalse($trader->isThereIsUpDownPeregib([2, 2, 3, 2, 1]));
        $this->assertFalse($trader->isThereIsUpDownPeregib([1, 2, 3, 2, 2]));
        $this->assertFalse($trader->isThereIsUpDownPeregib([3, 2, 1, 2, 3]));
    }

    /**
     * @test
     *
     * @return void
     */
    public function itMakeBuyDecisionOnDownUpPeregibTest()
    {
        $trader = new AveragePriceTrader($this->testCurrencyPair->code);

        // закидываем в кеш средние
        $this->setAveragesInCache('buy');

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            1,
            1,
            $this->currentMinuteTimestamp
        );

        $decision = $trader->getDecision($rate);

        $this->assertInstanceOf(TraderDecision::class, $decision);
        $this->assertEquals('buy', $decision->action);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itMakeSellDecisionOnUpDownPeregibTest()
    {
        $trader = new AveragePriceTrader($this->testCurrencyPair->code);

        // закидываем в кеш средние
        $this->setAveragesInCache('sell');

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            1,
            1,
            $this->currentMinuteTimestamp
        );

        $decision = $trader->getDecision($rate);

        $this->assertInstanceOf(TraderDecision::class, $decision);
        $this->assertEquals('sell', $decision->action);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntMakeDecisionIfThereIsNotEnoughValues()
    {
        $trader = new AveragePriceTrader($this->testCurrencyPair->code);

        // закидываем в кеш средние
        // берём столько значений, чтобы их осталось меньше 6 * (5 - 1) + 1 (в методе создаётся 29)
        $this->setAveragesInCache('buy', 23);

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            1,
            1,
            $this->currentMinuteTimestamp
        );

        $decision = $trader->getDecision($rate);

        $this->assertFalse($decision);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntMakeDecisionOnFlatGraphic()
    {
        $trader = new AveragePriceTrader($this->testCurrencyPair->code);

        // закидываем в кеш средние
        $this->setAveragesInCache('flat');

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            1,
            1,
            $this->currentMinuteTimestamp
        );

        $decision = $trader->getDecision($rate);

        $this->assertFalse($decision);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntMakeDecisionOnNearFlatGraphic()
    {
        $trader = new AveragePriceTrader($this->testCurrencyPair->code);

        // закидываем в кеш средние
        $this->setAveragesInCache('near_flat_buy');

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            1,
            1,
            $this->currentMinuteTimestamp
        );

        $decision = $trader->getDecision($rate);

        $this->assertFalse($decision);
    }
}
