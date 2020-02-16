<?php

namespace Tests\Feature;

use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyPairRateTest extends TestCase
{

    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntFailIfWeTryToDeleteTooOldRates()
    {
        // создадим пару котировок
        CurrencyPairRate::save($this->testCurrencyPair->code, 1, 1, $this->timestampNow - SECONDS_IN_MINUTE);
        CurrencyPairRate::save($this->testCurrencyPair->code, 1, 1, $this->timestampNow);

        CurrencyPairRate::clearOlderThan(
            $this->testCurrencyPair->code,
            $this->timestampNow - (10 * SECONDS_IN_MINUTE)
        ); // удаляем котировки старше 10 минут, а таких нет

        $this->assertEquals(
            2,
            CurrencyPairRate::count($this->testCurrencyPair->code)
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntFailIfWeTryToDeleteEmptyListOfRates()
    {
        CurrencyPairRate::clearOlderThan(
            $this->testCurrencyPair->code,
            date('U') - (10 * SECONDS_IN_MINUTE)
        ); // удаляем котировки старше 10 минут

        $this->assertEquals(
            0,
            CurrencyPairRate::count($this->testCurrencyPair->code)
        );
    }
}