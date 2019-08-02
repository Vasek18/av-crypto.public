<?php

namespace App\Events;

use App\Trading\CurrencyPairRate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CurrencyPairRateChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $currencyPairID;
    public $currencyPairCode;
    public $rate;
    public $serverTimestamp; // todo стал равен таймстемпу котировки

    /**
     * @param integer $currencyPairID
     * @param string $currencyPairCode
     * @param CurrencyPairRate $rate
     * @param integer $serverTimestamp
     */
    public function __construct(
        $currencyPairID,
        $currencyPairCode,
        CurrencyPairRate $rate,
        $serverTimestamp
    ) {
        $this->currencyPairID = $currencyPairID;
        $this->currencyPairCode = $currencyPairCode;
        $this->rate = $rate;
        $this->serverTimestamp = $serverTimestamp;
    }
}
