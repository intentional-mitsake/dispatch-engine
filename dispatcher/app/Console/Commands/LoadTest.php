<?php

namespace App\Console\Commands;

use App\Models\Dispatch;
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
        $totalDispatches = 1000; //for testing purposes, we will create 1000 dispatches

        // Seed DB
        Artisan::call('migrate:fresh'); // reset DB
        Artisan::call('db:seed', [
            '--class' => 'ProductSeeder'
        ]);
        Artisan::call('db:seed', [
            '--class' => 'LoadTestSeeder'
        ]);
        Log::info("Starting a process pool with $workers workers");
        $pool = Process::pool(function ($pool) use ($workers) {
            Log::debug("Reaching here");
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
        })->start();// need to start, using wait() starts the pool and waits auto

        while(true){
            $completed = Dispatch::where('status', 'completed')->count();
            if($completed >= $totalDispatches) {
                Log::info("All dispatches completed. Stopping the pool.");
                $pool->stop();
                Log::info("Using DB timestamps to calculate total time taken for all dispatches to complete");
                $first = Dispatch::min('claimed_at');
                $last = Dispatch::where('status', 'completed')->max('updated_at');
                $totalTime = strtotime($last) - strtotime($first);// strtotime returns how many seconds since 1970 for the given timestamp
                $throughput = round($totalDispatches/($totalTime/60));// jobs per minute
                Log::info("Total workers: $workers");
                Log::info("Total dispatches: $totalDispatches");
                Log::info("Total time taken for all dispatches to complete: $totalTime seconds");
                Log::info("Throughput: $throughput dispatches per minute");
                break;
            }
            sleep(1);// check every second
        }
        // wait stops once all processes are completed or stopped--> the claimers have no conditon to stop yet
        //$result = $pool->wait();
    }
}
