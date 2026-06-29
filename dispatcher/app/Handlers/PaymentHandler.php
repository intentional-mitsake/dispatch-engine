<?php

namespace App\Handlers;

use App\Models\Dispatch;

class PaymentHandler
{
    public function handle(Dispatch $dispatch) {
        // this function purely simulates payment process so that we can try out diff cases
        sleep(rand(1, 10));// sleep for random amount of time from 1 to 10 seconds

        if (rand(1, 5) === 1) {
           throw new \Exception('Payment failed');
        }
        // logs to the logs/laravel.log file
        logger("Payment completed for dispatch {$dispatch->payload['customer_id']}");
        // to print to the console
        echo "Payment completed for dispatch {$dispatch->payload['customer_id']}\n";
        
    }
}