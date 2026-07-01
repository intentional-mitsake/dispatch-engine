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

        $affectedRows = DB::update(
            'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
            [$quantity, $productId, $quantity]
        );

        if($affectedRows === 0) {
            Log::error("Inventory failed for dispatch {$dispatch->id}");
            throw new \Exceptio("Insufficient stock for product {$productId}");
        }

        Log::info("Inventory updated for dispatch {$dispatch->id}: Product {$productId} stock reduced by {$quantity}");
    }
}
