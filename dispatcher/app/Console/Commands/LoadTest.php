<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

//#[Signature('loadtest:start {--workers=1}')]
#[Description('Command description')]
class LoadTest extends Command
{
    protected $signature = 'loadtest:start {--workers=1}';
    public function handle()
    {
        // wasnot working without int
        $workers = (int) $this->option('workers');
        Log::info("Starting load test with $workers workers");
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
        });

        $resutl = $pool->wait();
        Log::info("Load test completed");
    }
}
