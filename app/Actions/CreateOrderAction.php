<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\OrderList;
use App\Models\Product;
use App\Models\DeliveryCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class CreateOrderAction
{
    /**
     * Execute the order creation process.
     *
     * @param array $customerDetails
     * @param string $paymentMethod
     * @param array $orderedProducts [productId => qty]
     * @param array $address
     * @return Order
     * @throws Exception
     */
    public function execute(
        array $customerDetails,
        string $paymentMethod,
        array $orderedProducts,
        array $address
    ): Order {
        return DB::transaction(function () use ($customerDetails, $paymentMethod, $orderedProducts, $address) {
            $processedProducts = [];
            $subtotal = 0;

            foreach ($orderedProducts as $productId => $qty) {
                $product = Product::find($productId);

                if (!$product) {
                    throw new ModelNotFoundException("Product ID {$productId} not found.");
                }

                $lineTotal = (float) $product->selling_price * (int) $qty;
                $subtotal += $lineTotal;

                $processedProducts[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'qty' => (int) $qty,
                    'price' => (float) $product->selling_price,
                    'total' => $lineTotal,
                ];
            }

            // Calculate delivery charge
            $division = $address['selection']['division'] ?? null;
            $district = $address['selection']['district'] ?? null;
            $deliveryCharge = DeliveryCharge::calculate($division, $district);

            $totalPrice = $subtotal + $deliveryCharge;

            // Get or create OrderList
            $orderList = OrderList::firstOrCreate();

            // Create Order
            return $orderList->orders()->create([
                'order_id'         => Order::generateUniqueId(),
                'order_status'     => $paymentMethod === 'COD' ? 'Confirmed' : 'Pending',
                'payment_status'   => $paymentMethod === 'COD' ? 'Paid' : 'Unpaid',
                'payment_method'   => $paymentMethod,
                'ordered_products' => $processedProducts,
                'customer_details' => $customerDetails,
                'address'          => $address,
                'delivery_charge'  => $deliveryCharge,
                'total_price'      => $totalPrice,
            ]);
        });
    }

    /**
     * Static helper for running the action.
     */
    public static function run(array $customerDetails, string $paymentMethod, array $orderedProducts, array $address): Order
    {
        return (new self())->execute($customerDetails, $paymentMethod, $orderedProducts, $address);
    }
}
