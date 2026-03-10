<?php

namespace App\Jobs;

use App\Models\Order;
use App\Actions\ReleaseInventoryAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ExpirePendingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900; // seconds

    public function __construct()
    {
        // Can optionally pass order IDs if needed
        //But, we'll not use it in this implementation since we're querying for pending orders directly in the handle method
    }

    public function handle()
    {
        // Find orders older than 15 minutes that are still unpaid and pending
        $orders = Order::pending()
            ->where('payment_status', 'Unpaid')
            ->where('created_at', '<', now()->subMinutes(15))
            ->get();

        foreach ($orders as $order) {
            DB::transaction(function () use ($order) {
                // Release reserved inventory
                ReleaseInventoryAction::run($order);

                // Mark order as cancelled
                $order->update([
                    'order_status' => 'Cancelled',
                    'payment_status' => 'Failed',
                ]);
            });
        }
    }
}