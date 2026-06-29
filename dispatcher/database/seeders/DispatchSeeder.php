<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Models\Dispatch;

class DispatchSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 20) as $i) {
            Dispatch::create([
                'type' => 'payment',
                'idempotency_key' => 'pay-ord-' . $i,
                'payload' => [ 
                    'amount' => rand(1, 100),
                    'customer_id' => 'user-'. $i . 'test',
                ]
            ]);
        }
    }
}
