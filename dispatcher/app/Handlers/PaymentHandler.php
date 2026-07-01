<?php

namespace App\Handlers;

use App\Models\Dispatch;
use App\Models\PaymentRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentHandler
{
    public function handle(Dispatch $dispatch, bool $failure) {
        if ($failure) {
            Log::error("Payment failed for dispatch {$dispatch->payload['customer_id']}");
            throw new \Exception('Payment failed');
        }

        $amount = $dispatch->payload['amount'] ?? null;
        $customerId = $dispatch->payload['customer_id'] ?? null;
        if (!$amount || !$customerId) {
            Log::error("Payment failed for dispatch {$dispatch->payload['customer_id']}");
            throw new \Exception('Payment failed: Missing amount or customer_id');
        }

        // this function purely simulates payment process so that we can try out diff cases
        sleep(rand(1, 10));// sleep for random amount of time from 1 to 10 seconds
        // no need to retry if its already charged, so putting this before retry initiation block
        if ($this->alreadyCharged($dispatch)) {
            Log::info("Payment already completed for dispatch {$dispatch->payload['customer_id']}");
            return;
        }

        if (rand(1, 5) === 1) {
           Log::error("Payment failed for dispatch {$dispatch->payload['customer_id']}");
           throw new \Exception('Payment failed');
        }
        // if not already charged & no failure/error, record the payment success
        $this->recordPayment($dispatch);
        // logs to the logs/laravel.log file
        //logger("Payment completed for dispatch {$dispatch->payload['customer_id']}");
        Log::info("Payment completed for dispatch {$dispatch->payload['customer_id']}");
    }

    // server level idempotency check to avoid double charging even if api level check is bypassed
    private function alreadyCharged(Dispatch $dispatch): bool {
        // check if the payment record already exists for this dispatch
        return PaymentRecord::where('dispatch_id', $dispatch->id)->exists();
    }

    private function recordPayment(Dispatch $dispatch): void {
        // create a payment record for this dispatch
        DB::transaction(function () use ($dispatch) {
            PaymentRecord::create([
                'dispatch_id' => $dispatch->id,
                'customer_id' => $dispatch->payload['customer_id'],
            ]);
        });
    }
}