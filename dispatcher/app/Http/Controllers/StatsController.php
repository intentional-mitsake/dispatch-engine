<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    public function index() {
        // remember returns cahced value if it exists
        // else run the function, store for 5 seconds and then return it
        $data = Cache::remember('stats', 5, function() {
            // number of pending jobs
            $pendingNum = Dispatch::where('status', 'pending')->count();
            // jobs completed in the last minute
            $throughput = Dispatch::where('status', 'completed')->where('updated_at', '>=', now()->subMinute())->count();
            // failed jobs
            $totalProcessed = Dipatch::whereIn('status', ['completed', 'failed'])->count();
            $failed = Dispatch::where('status', 'failed')->count();
            $failRate = $totalProcessed > 0? round(($failed / $totalProcessed) * 100, 2) : 0;

            //latency - seconds btewn created and completed
            // faster
            $latency = DB::selectOne("SELECT percentile_cont(0.50) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (updated_at - created_at))) as p50,
                percentile_cont(0.95) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (updated_at - created_at))) as p95,
                percentile_cont(0.99) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (updated_at - created_at))) as p99
                FROM dispatches
                WHERE status = 'completed'
                ");

            return [
                'queue_depth' => $pendingNum,
                'throughput' => $throughput, 
                'failure_rate' => $failRate, 
                'latency' => [
                    'p50' => round($latency->p50 ?? 0, 2),
                    'p95' => round($latency->p95 ?? 0, 2),
                    'p99' => round($latency->p99 ?? 0, 2),
                    ]
                ];
        });

        return response()->json($data);
    }
}
