<?php

namespace Tests\Jobs;

use App\CurrencyPairsMetrics\BuyAmount;
use App\CurrencyPairsMetrics\SellQuantity;
use App\CurrencyPairsMetrics\Spread;
use App\Jobs\UpdateOrderBooksInfo;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOrderBooksInfoTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $userAccount;
    public $exchangeMarket;

    /**
     * @test
     *
     * @return void
     */
    public function itCalculatesPairsMetrics()
    {
        // сейчас собираем эти метрики только для exmo, поэтому проверяем только для него
        $exmoExchangeMarket = ExchangeMarket::where('code', 'exmo')->first();
        $pair = ExchangeMarketCurrencyPair::create( // раньше валюты exmo создавалась через миграцию, но это лишнее, поэтому создаём одну валюту специально для этого теста
            array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 100,
                'min_price'             => 1,
                'max_price'             => 30000,
                'currency_2_max_amount' => 200000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoExchangeMarket->id,
                'active'                => true,
            )
        );

        UpdateOrderBooksInfo::dispatchNow(); // todo внешние запросы тут надо мокать

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