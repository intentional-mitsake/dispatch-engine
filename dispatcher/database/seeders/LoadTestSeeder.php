<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Dispatch;

class LoadTestSeeder extends Seeder
{
    public function run(): void
    {
        foreach(range(1, 1000) as $i) {
            $type = rand(0, 1) ? 'payment' : 'inventory'; // randomly choose between payment and inventory
            Dispatch::create([
                'type' => $type,
                'idempotency_key' => $type . '-ord-' . $i,
                'available_at' => now(),
                'status' => 'pending',
                'payload' => [
                    'amount' => $type === 'payment' ? rand(1, 100) : null,
                    'customer_id' => $type === 'payment' ? 'user-'. \Illuminate\Support\Str::uuid() : null,
                    'product_id' => $type === 'inventory' ? rand(1, 10) : null,
                    'quantity' => $type === 'inventory' ? rand(1, 5) : null,
                ]
            ]);
        }
    }
}
