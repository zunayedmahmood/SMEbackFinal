<?php

namespace App\Actions;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ReleaseInventoryAction
{
    /**
     * Release the inventory for the given order.
     * Deletes the reservation records for the entire order.
     * 
     * @param Order $order
     */
    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->reservedProducts()->delete();
        });
    }

    /**
     * Static helper method to run the release on an order.
     * 
     * @param Order $order
     */
    public static function run(Order $order): void
    {
        (new self())->execute($order);
    }
}
