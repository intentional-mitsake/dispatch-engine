<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Dispatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('watchdog:run')]
#[Description('Run the watchdog process to recover stuck jobs')]
class WatchdogCommand extends Command
{
    private const TIMEOUT_MIN = 5;// 5 min
    public function handle()
    {
        while(true){
            DB::transaction(function () {
                $stuckDispatches = Dispatch::where('status', 'processing')
                ->where('claimed_at', '<', now()->subMinutes(self::TIMEOUT_MIN)) // was claimed more than 2 min ago so timeout
                ->lock('FOR UPDATE SKIP LOCKED')
                ->get();// retrieve results
                if (!$stuckDispatches->count()) {
                    Log::debug('No stuck jobs to recover');
                    return; // exits out of the transaction NOT the while loop
                }
                foreach ($stuckDispatches as $dispatch) {
                  Log::debug("Job {$dispatch->id} is currently claimed by {$dispatch->claimed_by}");
                    $dispatch->update([
                        'status' => 'pending',
                        //clear the claimed_at and claimed_by
                        'claimed_at' => null,
                        'claimed_by' => null,
                    ]);
                    Log::debug("Job {$dispatch->id} is stuck, moving to pending status");
                }
            });
            Log::debug('Watchdog is sleeping for 20 seconds');
            sleep(20);// sleep for 20 seconds before checking again
        }
    }
}
