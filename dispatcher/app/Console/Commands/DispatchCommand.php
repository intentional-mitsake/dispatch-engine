<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Dispatch;

#[Signature('app:dispatch-command')]
#[Description('Command description')]
class DispatchCommand extends Command
{
    /**
     * Execute the console command.
     */
    protected $signature = 'queue:claim'; // actual name of the command

    protected $description = 'Claim pending jobs'; //descp for list


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
            'status' => 'processing'
            'claimed_at' => now(),
            'claimed_by' => gethostname(),// get current hostname-->.i.e localhost
        ])
    }
}
