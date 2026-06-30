<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Models\Dispatch;
use Illuminate\Support\Str;

class DispatchSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 20) as $i) {
            Dispatch::create([
                'type' => 'payment',
                'idempotency_key' => 'pay-ord-' . $i,
                // if available_at is not set, it will use the DB's timezone, Laravel default is UTC, DB is not
                // so it causses some issues
                'available_at' => now(),// this way its set to current time in UTC
                'payload' => [ 
                    'amount' => rand(1, 100),
                    'customer_id' => 'user-'. Str::uuid(),
                ]
            ]);
        }
    }
}
