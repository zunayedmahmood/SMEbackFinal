<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Product;
use App\Models\ReservedProduct;
use Illuminate\Support\Facades\DB;
use Exception;

class ReserveInventoryAction
{
    /**
     * Reserve inventory for an order.
     * Uses pessimistic locking to prevent race conditions.
     * 
     * @param Order $order
     * @throws Exception
     */
    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->ordered_products as $item) {
                // Lock the product record for update to ensure accurate available stock check
                $product = Product::where('id', $item['id'])->lockForUpdate()->firstOrFail();
                
                // Get available stock (total_count - already reserved items)
                $availableStock = $product->getAvailableStock();

                if ($availableStock < $item['qty']) {
                    throw new Exception("Insufficient stock for product: {$product->name}. Requested: {$item['qty']}, Available: {$availableStock}");
                }

                // Create reserved product entry
                ReservedProduct::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'qty'        => $item['qty'],
                    'price'      => $item['price'],
                    'total'      => $item['total'],
                ]);
            }
        });
    }

    /**
     * Static helper for running the action.
     */
    public static function run(Order $order): void
    {
        (new self())->execute($order);
    }
}
