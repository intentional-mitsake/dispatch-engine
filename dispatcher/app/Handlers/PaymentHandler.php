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

        logger("Payment completed for dispatch {$dispatch->payload['customer_id']}");
    }

    private function process(Dispatch $dispatch) {
       match($dispatch->type) {// match is like switch case
           'payment' => (new PaymentHandler())->handle($dispatch),// if payment then call payment handler
       };

       $dispatch->update([
           'status' => 'completed'
       ])
    }
}