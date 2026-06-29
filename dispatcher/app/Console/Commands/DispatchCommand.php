<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Dispatch;
use App\Handlers\PaymentHandler;

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
            $dispatch = Dispatch::where('status', 'pending')->where('available_at', '<=', now())->first();
            if ($dispatch) {
                $this->process($dispatch);
            } else {
                sleep(4); // if no pending jobs, sleep for 4 seconds
            }
        }
    }

    private function process(Dispatch $dispatch) {
        $dispatch->update([
            'status' => 'processing',
            'claimed_at' => now(),
            'claimed_by' => gethostname(),// get current hostname-->.i.e localhost
        ]);

        match($dispatch->type) {// match is like switch case
           'payment' => (new PaymentHandler())->handle($dispatch),// if payment then call payment handler
       };

       $dispatch->update([
           'status' => 'completed'
       ]);
    }
}
