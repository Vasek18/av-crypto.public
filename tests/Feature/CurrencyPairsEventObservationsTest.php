<?php

namespace Tests\Feature;

use App\Models\CurrencyPairEventObservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyPairsEventObservationsTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    /**
     * @test
     *
     * @return void
     */
    public function itSavesParamsAsArray()
    {
        CurrencyPairEventObservation::commitEvent(
            $this->testCurrencyPair->code,
            [
                'type'   => 'test',
                'params' => [
                    'ololo' => 'trololo',
                ],
            ],
            1
        );

        $observation = CurrencyPairEventObservation::where('currency_pair_code', $this->testCurrencyPair->code)
            ->first();

        $this->assertIsArray($observation->params);
    }

    /**
     * @test
     *
     * @return void
     */
    public function identicalEventsWithoutParamsAreCountedInOneObservation()
    {
        $event = [
            'type' => 'test',
        ];
        CurrencyPairEventObservation::commitEvent(
            $this->testCurrencyPair->code,
            $event,
            1
        );
        CurrencyPairEventObservation::commitEvent(
            $this->testCurrencyPair->code,
            $event,
            1
        );

        $this->assertEquals(1, CurrencyPairEventObservation::count());
    }

    /**
     * @test
     *
     * @return void
     */
    public function identicalEventsWithParamsAreCountedInOneObservation()
    {
        $event = [
            'type'   => 'test',
            'params' => [
                'ololo' => 'trololo',
            ],
        ];
        CurrencyPairEventObservation::commitEvent(
            $this->testCurrencyPair->code,
            $event,
            1
        );
        CurrencyPairEventObservation::commitEvent(
            $this->testCurrencyPair->code,
            $event,
            1
        );

        $this->assertEquals(1, CurrencyPairEventObservation::count());
    }
}

