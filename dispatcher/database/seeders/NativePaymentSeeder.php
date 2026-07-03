<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Dispatch;

class NativePaymentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 10) as $i) {
            Dispatch::create([
                'type'            => 'payment',
                'idempotency_key' => 'native-pay-' . $i,
                'payload'         => [
                    'amount'      => 49.99,
                    'customer_id' => 'native-cust-' . $i,
                ],
                'available_at'    => now(),
            ]);
        }
    }
}