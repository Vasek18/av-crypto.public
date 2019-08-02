<?php

namespace Tests\Jobs;

use App\CurrencyPairsMetrics\BuyAmount;
use App\CurrencyPairsMetrics\SellQuantity;
use App\CurrencyPairsMetrics\Spread;
use App\Jobs\UpdateOrderBooksInfo;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class UpdateOrderBooksInfoTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $userAccount;
    public $exchangeMarket;

    public function setUp()
    {
        parent::setUp();

        // чистим редис перед каждым тестом
        Redis::flushall();
    }

    /**
     * @test
     *
     * @return void
     */
    public function itCalculatesPairsMetrics()
    {
        // сейчас собираем эти метрики только для exmo. Проверим, что у этих пар создались метрики
        $exmoExchangeMarket = ExchangeMarket::where('code', 'exmo')->first();
        $exmoCurrencyPairs = ExchangeMarketCurrencyPair
            ::where('exchange_market_id', $exmoExchangeMarket->id)
            ->active()
            ->get();

        foreach ($exmoCurrencyPairs as $pair) {
            $this->assertEquals(
                0,
                SellQuantity::count($pair->code)
            );
            $this->assertEquals(
                0,
                BuyAmount::count($pair->code)
            );
            $this->assertEquals(
                0,
                Spread::count($pair->code)
            );
        }

        UpdateOrderBooksInfo::dispatchNow();

        foreach ($exmoCurrencyPairs as $pair) {
            $this->assertEquals(
                1,
                SellQuantity::count($pair->code)
            );
            $this->assertEquals(
                1,
                BuyAmount::count($pair->code)
            );
            $this->assertEquals(
                1,
                Spread::count($pair->code)
            );
        }
    }
}