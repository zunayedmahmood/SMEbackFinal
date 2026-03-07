<?php

namespace App\Actions;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Exception;

class CommitInventoryAction
{
    /**
     * Commit the inventory for the given order.
     * Deducts stock permanently from product batches and deletes reservations.
     * 
     * @param Order $order
     * @throws Exception
     */
    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $reservedItems = $order->reservedProducts()->with('product')->get();

            if ($reservedItems->isEmpty()) {
                throw new Exception("No reserved items found for order ID: {$order->id}");
            }

            foreach ($reservedItems as $item) {
                $product = $item->product;

                // Deduct from batches
                $result = $product->sellProduct($item->qty);

                if (!$result['success']) {
                    throw new Exception("Failed to commit inventory for product: {$product->name}. Error: " . ($result['message'] ?? 'Unknown error'));
                }

                // Delete the reservation record
                $item->delete();
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
