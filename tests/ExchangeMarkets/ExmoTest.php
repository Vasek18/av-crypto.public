<?php

namespace Tests\ExchangeMarkets;

use App\ExchangeMarkets\ExmoExchangeMarket;
use App\Jobs\UpdateCurrenciesSettings;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExmoTest extends TestCase
{
    public $exmoInDB;

    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();

        $this->exmoInDB = ExchangeMarket::where('code', 'exmo')->first();
    }

    /**
     * @test
     *
     * @return void
     */
    public function itReturnsRates()
    {
        $exm = new ExmoExchangeMarket();
        $answer = $exm->getCurrenciesRates();

        // берём таймстемп до получения котировки
        $expectedTimestamp = floor(date('U') / SECONDS_IN_MINUTE) * SECONDS_IN_MINUTE;

        $this->assertIsArray($answer);
        $this->assertIsArray($answer['rates']);
        $this->assertInstanceOf(CurrencyPairRate::class, $answer['rates'][0]);

        $this->assertEquals($expectedTimestamp, $answer['rates'][0]->timestamp);
    }

    /**
     * Тест, что ничего не падает при падении биржи при попытке обновления котировок
     *
     * @test
     *
     * @return void
     */
    public function testExmoFailAtReturningRates()
    {
        $stub = $this->getMockBuilder(ExmoExchangeMarket::class)
            ->disableOriginalConstructor()
            ->setMethods(['makeRequest'])
            ->getMock();
        $stub->method('makeRequest')
            ->willReturn(false);

        $answer = $stub->getCurrenciesRates();

        $this->assertFalse($answer);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itUpdatesCurrenciesSettings()
    {
        /** проверяем, что новые валюты создаются */
        $prevCurrenciesCount = ExchangeMarketCurrencyPair::count();
        // хоть это и не конкретно Эксмо проверяет, но в том числе Эксмо
        UpdateCurrenciesSettings::dispatchNow();
        $currentCurrenciesCount = ExchangeMarketCurrencyPair::count();
        // идея в том, что в изначальной миграции меньше пар, чем после того, как мы заберём их из апи
        $this->assertGreaterThan($prevCurrenciesCount, $currentCurrenciesCount);

        /** проверяем, что параметры затираются на настоящие */
        // запоминаем правильное значение
        $pair = ExchangeMarketCurrencyPair
            ::where('currency_1_code', 'BTC')
            ->where('currency_2_code', 'USD')
            ->where('exchange_market_id', $this->exmoInDB->id)
            ->first();
        $rightMaxPrice = $pair->max_price;

        // ставим явно неправильные значения
        ExchangeMarketCurrencyPair
            ::where('currency_1_code', 'BTC')
            ->where('currency_2_code', 'USD')
            ->where('exchange_market_id', $this->exmoInDB->id)
            ->update(['max_price' => 0]);

        // проверяем, что значение поменялось на неправильное
        $pair = ExchangeMarketCurrencyPair
            ::where('currency_1_code', 'BTC')
            ->where('currency_2_code', 'USD')
            ->where('exchange_market_id', $this->exmoInDB->id)
            ->first();
        $this->assertEquals(0, $pair->max_price);

        // запускаем задачу на обновление настроек валют
        UpdateCurrenciesSettings::dispatchNow();

        // проверяем, что значение поменялось на верное
        $pair = ExchangeMarketCurrencyPair
            ::where('currency_1_code', 'BTC')
            ->where('currency_2_code', 'USD')
            ->where('exchange_market_id', $this->exmoInDB->id)
            ->first();
        $this->assertEquals($rightMaxPrice, $pair->max_price);
    }

    /**
     * Тест, что ничего не падает при падении биржи при попытке обновления пар
     *
     * @test
     *
     * @return void
     */
    public function testExmoFailAtUpdatingPairsAndSettings()
    {
        $stub = $this->getMockBuilder(ExmoExchangeMarket::class)
            ->disableOriginalConstructor()
            ->setMethods(['makeRequest'])
            ->getMock();
        $stub->method('makeRequest')
            ->willReturn(false);

        $answer = $stub->updatePairsAndSettings();

        $this->assertFalse($answer);
    }
}
