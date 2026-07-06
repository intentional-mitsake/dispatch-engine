<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

#[Signature('workerpool:start {--workers=1}')]
#[Description('Command description')]
class WorkerPool extends Command
{
    public function handle()
    {
        $workers = (int) $this->option('workers');
        Log::info("Starting a process pool with $workers workers");
        $pool = Process::pool(function ($pool) use ($workers) {
            for($i = 0; $i < $workers; $i++) {
                Log::info("Starting worker $i");
                $pool->path(base_path())
                ->command('php artisan worker:claim worker-' . $i)
                ->timeout(0)// disabling timeout, was stopping after 60 seconds without this
                ->start();
            }
            // also start a watchdog process to recover stuck jobs
            Log::info("Starting watchdog process");
            $pool->path(base_path())->command('php artisan watchdog:run')->timeout(0)->start();
        })->start();

        $pool->wait();
    }
}
