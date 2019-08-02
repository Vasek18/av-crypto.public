<?php

namespace Tests\ExchangeMarkets;

use App\ExchangeMarkets\TestExchangeMarket;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestTest extends TestCase
{

    use RefreshDatabase;

    public $userAccount;
    public $exchangeMarket;
    public $basket;

    public function setUp()
    {
        parent::setUp();

    }

    /**
     * @test
     *
     * @return void
     */
    public function exchangeMarketReturnsRates()
    {
        $exm = new TestExchangeMarket();
        $answer = $exm->getCurrenciesRates();

        $this->assertIsArray($answer);
        $this->assertIsArray($answer['rates']);
        $this->assertInstanceOf(CurrencyPairRate::class, $answer['rates'][0]);
    }
}
