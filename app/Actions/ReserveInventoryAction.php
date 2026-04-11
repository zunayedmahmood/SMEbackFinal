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
                // Lock the product record or variation record
                $product = Product::where('id', $item['id'])->lockForUpdate()->firstOrFail();
                $variationId = $item['variation_id'] ?? null;

                if ($product->has_variations && $variationId) {
                    // Lock Variation
                    $variation = \App\Models\Variation::where('id', $variationId)->lockForUpdate()->firstOrFail();
                    $availableStock = $variation->getAvailableStock();
                } else {
                    $availableStock = $product->getAvailableStock();
                }

                if ($availableStock < $item['qty']) {
                    throw new Exception("Insufficient stock for product: {$item['name']}. Requested: {$item['qty']}, Available: {$availableStock}");
                }

                ReservedProduct::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'variation_id' => $variationId,
                    'qty'          => $item['qty'],
                    'price'        => $item['price'],
                    'total'        => $item['total'],
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
