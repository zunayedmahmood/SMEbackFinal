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
     * @param array $stripeData [checkoutId, paymentId]
     * @return Order
     * @throws Exception
     */
    public function execute(
        array $customerDetails,
        string $paymentMethod,
        array $orderedProducts,
        array $address,
        array $stripeData = []
    ): Order {
        return DB::transaction(function () use ($customerDetails, $paymentMethod, $orderedProducts, $address, $stripeData) {
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
                    'id'    => $product->id,
                    'name'  => $product->name,
                    'qty'   => (int) $qty,
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
            $order = $orderList->orders()->create([
                'order_id'         => Order::generateUniqueId(),
                'order_status'     => 'Pending',
                'payment_status'   => 'Unpaid',
                'payment_method'   => $paymentMethod,
                'ordered_products' => $processedProducts,
                'customer_details' => $customerDetails,
                'address'          => $address,
                'delivery_charge'  => $deliveryCharge,
                'total_price'      => $totalPrice,
            ]);

            // If stripe data is provided and method is Online
            if ($paymentMethod === 'Online' && !empty($stripeData['stripe_checkout_session_id'])) {
                \App\Models\StripeId::setStripeIds(
                    $order,
                    $stripeData['stripe_checkout_session_id'],
                    $stripeData['stripe_payment_intent_id'] ?? null
                );
            }

            // Handle COD: sell inventory immediately, no reservation needed
            if ($paymentMethod === 'COD') {
                foreach ($processedProducts as $item) {
                    $product = Product::findOrFail($item['id']);
                    $result  = $product->sellProduct($item['qty']);

                    if (!$result['success']) {
                        throw new Exception("Insufficient stock for product: {$product->name}.");
                    }
                }

                $order->update([
                    'order_status'   => 'Confirmed',
                    'payment_status' => 'Paid',
                ]);
            }

            return $order;
        });
    }

    /**
     * Static helper for running the action.
     */
    public static function run(array $customerDetails, string $paymentMethod, array $orderedProducts, array $address, array $stripeData = []): Order
    {
        return (new self())->execute($customerDetails, $paymentMethod, $orderedProducts, $address, $stripeData);
    }
}