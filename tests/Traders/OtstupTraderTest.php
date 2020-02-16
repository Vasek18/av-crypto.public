<?php

namespace Tests\Traders;

use App\Traders\OtstupTrader;
use App\Traders\TraderDecision;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OtstupTraderTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;


    public function setUp()
    {
        parent::setUp();

        // сидер нужно запускать после очистки редиса, ведь именно туда мы сохраняем котировки
        $this->seed('TestExchangeMarketsDayRatesSeeder');
    }

    /**
     * @test
     *
     * @return void
     */
    public function traderReturnsTraderFalse()
    {
        /** @var OtstupTrader $trader */
        $trader = $this->getMockBuilder(OtstupTrader::class)
            ->disableOriginalConstructor()
            ->setMethods(['isThisIsPriceOfBuy', 'isThisIsPriceOfSell'])
            ->getMock();
        $trader->method('isThisIsPriceOfBuy')
            ->willReturn(false);
        $trader->method('isThisIsPriceOfSell')
            ->willReturn(false);

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            $this->faker->randomFloat(),
            $this->faker->randomFloat(),
            date('U')
        );

        $decision = $trader->getDecision($rate);

        $this->assertFalse($decision);
    }

    /**
     * @test
     *
     * @return void
     */
    public function traderReturnsTraderDecisionObject()
    {
        // решил, что пора покупать
        /** @var OtstupTrader $trader */
        $trader = $this->getMockBuilder(OtstupTrader::class)
            ->disableOriginalConstructor()
            ->setMethods(['isThisIsPriceOfBuy', 'isThisIsPriceOfSell'])
            ->getMock();
        $trader->method('isThisIsPriceOfBuy')
            ->willReturn(true);
        $trader->method('isThisIsPriceOfSell')
            ->willReturn(false);

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            $this->faker->randomFloat(),
            $this->faker->randomFloat(),
            date('U')
        );

        $decision = $trader->getDecision($rate);

        $this->assertInstanceOf(TraderDecision::class, $decision);
    }

    /**
     * @test
     *
     * @return void
     */
    public function traderUpdatesItsTempData()
    {
        $trader = new OtstupTrader($this->testCurrencyPair->code, false);

        // сначала переменные должны быть пусты
        $this->assertFalse($trader->getBuyLastMax());
        $this->assertFalse($trader->getBuyLastMin());
        $this->assertFalse($trader->getSellLastMax());
        $this->assertFalse($trader->getSellLastMin());

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            1,
            1,
            date('U')
        );
        $trader->getDecision($rate);

        // после первого решения они уже не пустые
        $this->assertNotFalse($trader->getBuyLastMax());
        $this->assertNotFalse($trader->getBuyLastMin());
        $this->assertNotFalse($trader->getSellLastMax());
        $this->assertNotFalse($trader->getSellLastMin());
        // а максимумы и минимумы равны
        $this->assertEquals($trader->getBuyLastMax(), $trader->getBuyLastMin());
        $this->assertEquals($trader->getSellLastMax(), $trader->getSellLastMin());

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            2,
            2,
            date('U')
        );
        $trader->getDecision($rate);

        // после второго решения максимумы и минимумы отличаются
        $this->assertNotEquals($trader->getBuyLastMax(), $trader->getBuyLastMin());
        $this->assertNotEquals($trader->getSellLastMax(), $trader->getSellLastMin());
    }

    /**
     * @test
     *
     * @return void
     */
    public function traderDiffersItsTempDataBetweenCurrencyPairs()
    {
        $trader1 = new OtstupTrader($this->testCurrencyPair->code, false);
        $rate1 = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            1,
            1,
            date('U')
        );
        $trader1->getDecision($rate1);

        // готовим трейдера для другой валюты
        $btcRubCurrencyPair = $this->getCurrencyPair('test', 'BTC', 'RUB');
        $trader2 = new OtstupTrader($btcRubCurrencyPair->code, false);
        $rate2 = new CurrencyPairRate(
            $btcRubCurrencyPair->code,
            2,
            2,
            date('U')
        );
        $trader2->getDecision($rate2);

        $this->assertNotEquals(
            $trader1->getBuyLastMax(),
            $trader2->getBuyLastMax()
        );
        $this->assertNotEquals(
            $trader1->getBuyLastMin(),
            $trader2->getBuyLastMin()
        );
        $this->assertNotEquals(
            $trader1->getSellLastMax(),
            $trader2->getSellLastMax()
        );
        $this->assertNotEquals(
            $trader1->getSellLastMin(),
            $trader2->getSellLastMin()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function traderCanClearItsTempData()
    {
        $trader = new OtstupTrader($this->testCurrencyPair->code, false);

        $rate = new CurrencyPairRate(
            $this->testCurrencyPair->code,
            1,
            1,
            date('U')
        );
        $trader->getDecision($rate);

        $this->assertNotFalse($trader->getBuyLastMax());
        $this->assertNotFalse($trader->getBuyLastMin());
        $this->assertNotFalse($trader->getSellLastMax());
        $this->assertNotFalse($trader->getSellLastMin());

        // чистим
        $trader->clearBuyLastMax();
        $trader->clearBuyLastMin();
        $trader->clearSellLastMax();
        $trader->clearSellLastMin();

        // теперь пусты
        $this->assertFalse($trader->getBuyLastMax());
        $this->assertFalse($trader->getBuyLastMin());
        $this->assertFalse($trader->getSellLastMax());
        $this->assertFalse($trader->getSellLastMin());
    }
}
