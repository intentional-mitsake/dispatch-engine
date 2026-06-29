<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Dispatch;
use App\Handlers\PaymentHandler;
use App\DispatchClaimer;
use Illuminate\Support\Facades\Log;

#[Signature('worker:claim')]
#[Description('Claim pending jobs')]
class DispatchCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        while(true) {
            $claimedDispatch = (new DispatchClaimer())->claim();
            if(!$claimedDispatch) {
                Log::debug('No jobs to process: Sleeping');
                sleep(4);// sleep for 4 seconds
                continue;
            }
            Log::info("Job {$claimedDispatch->id} proessing started");
            $this->process($claimedDispatch);
        }
    }

    private function process(Dispatch $dispatch) {
        try{// PaymentHandler can throw exception
             match($dispatch->type) {// match is like switch case
               'payment' => (new PaymentHandler())->handle($dispatch),// if payment then call payment handler
           };
           $dispatch->update([
               'status' => 'completed'
           ]);
           Log::info("Job {$dispatch->id} proessing completed");
        } catch(\Exception $e) {
            $dispatch->update([
                'status' => 'failed',
                'failed_at' => now(),
            ]);
            Log::error("Job {$dispatch->id} proessing failed", ['error' => $e->getMessage()]);
        }
    }
}
