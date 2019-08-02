<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckOrders implements ShouldQueue{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(){
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(){
        /** @var Order $order */
        foreach (Order::undone()->get() as $order){
            $done = $order->check();

            if ($done){
                $order->basket()->first()->commitOrder($order);
            }
        }
    }
}
