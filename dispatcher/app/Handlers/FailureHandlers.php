<?php

use App\Models\Dispatch;
use Illuminate\Support\Facades\Log;

class FailureHandler {
    private const MAX_ATTEMPTS = 5;
    // ref from HackerOne
    public function handle(Dispatch $dispatch, \Throwable $exception) {
        $dispatch->increment('attempts');
        $dispatch->refresh(); // reloads the model from updated values in database
        // only change status to failed if the max attempts has been reached
        if($dispatch->attempts >= self::MAX_ATTEMPTS) {
            $dispatch->update([
                'status' => 'failed',
                'failed_at' => now(),
            ]);
            Log::error("Job {$dispatch->id} proessing failed", ['error' => $exception->getMessage()]);
            return;
        }
        // get the delay for the next retry
        $delay = $this->backoffWithJitter($dispatch->attempts);
        Log::info("Delaying job {$dispatch->id} retry attempt for $delay seconds");
        // update the status to pending, and set the available_at to now + delay 
        // so that it can only be claimed after the delay
        $dispatch->update([
            'status' => 'pending',
            'available_at' => now()->addSeconds($delay),
        ]);
    }

    private function backoffWithJitter(int $attempt): int {
        $baseDelay = 2 ** $attempt; // exponential backoff--> 2^1, 2^2, 2^3
        $jitter = rand(0, 3); // jitter to avoid synchronization of retries
        return $baseDelay + $jitter;
    }
}