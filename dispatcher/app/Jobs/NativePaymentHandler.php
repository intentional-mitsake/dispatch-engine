<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

// ref from Laravel docs
class NativePaymentHandler implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    // these two are built-in properties  
    // they have default values but can be overridden in the job class like here:
    public $tries = 5;
    public $backoff = [2, 4, 8, 16, 32]; // exponential backoff for retries
    public function __construct(private array $payload)
    {
    }
    // also built-in for shoulbeunique, gets the unique id for the job, 
    // so that if a job with the same unique id is already in the queue, it will not be added again
    // i.e idempotency
    public function uniqueId(): string
    {
        return $this->payload['customer_id'];
    }


    public function handle(): void
    {
        sleep(rand(1, 10));
        if (rand(1, 5) === 1) {
            throw new \Exception('Payment failed');
        }

        Log::info('Payment completed for dispatch ' . $this->payload['customer_id']);
    }
}
