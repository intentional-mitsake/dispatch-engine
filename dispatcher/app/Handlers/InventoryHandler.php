<?php

namespace App\Handlers;

use App\Models\Products;
use App\Models\Dispatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InventoryHandler 
{
    public function handle(Dispatch $dispatch)
    {
        $productId = $dispatch->payload['product_id'] ?? null;
        $quantity = $dispatch->payload['quantity'] ?? null;
        if (!$productId || !$quantity) {
            Log::error("Inventory failed for dispatch {$dispatch->id}");
            throw new \Exception('Inventory failed: Missing product_id or quantity');
        }
        // implicit locking is used so race conditions are avoided thru atomic operations
        // no other transaction can modify this row while the UPDATE is executing
        // readers see the old value until the update commits — writes are what's serialized
        // meaning from reading the stock to updating it is an atomic operation  
        // reading(WHERE id = ?) and updating(SET stock = stock - ?) the stock is done in a single atomic operation
        // so no one can sneak in and change the stock between the read and write until the update commits
        $affectedRows = DB::update(
            'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
            [$quantity, $productId, $quantity]
        );

        if($affectedRows === 0) {
            Log::error("Inventory failed for dispatch {$dispatch->id}");
            throw new \Exception("Insufficient stock for product {$productId}");
        }

        Log::info("Inventory updated for dispatch {$dispatch->id}: Product {$productId} stock reduced by {$quantity}");
    }
}
