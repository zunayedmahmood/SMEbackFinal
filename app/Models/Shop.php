<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Actions\CreateOrderAction;


class Shop extends Model
{
    protected $fillable = [];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function orderList()
    {
        return $this->hasOne(OrderList::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Logic Orchestration
    |--------------------------------------------------------------------------
    |
    | Note: Core Product & Category retrieval/management has been moved 
    | directly into their respective models. 
    |
    */


    /*
    |--------------------------------------------------------------------------
    | Cart Validation
    |--------------------------------------------------------------------------
    */

    public function updateForCart(array $items)
    {
        $response = [];
        $someProductRemoved = false;

        foreach ($items as $item) {

            $productId = $item['product_id'];
            $variationId = $item['variation_id'] ?? null;
            $qty = $item['qty'];

            $product = Product::with('variations')->find($productId);

            if (!$product) {
                $someProductRemoved = true;
                continue;
            }

            if ($product->has_variations) {
                if (!$variationId) {
                    $someProductRemoved = true;
                    continue;
                }
                $variation = $product->variations->find($variationId);
                if (!$variation) {
                    $someProductRemoved = true;
                    continue;
                }
                $availableStock = $variation->getAvailableStock();
                $price = $variation->selling_price !== null ? (float)$variation->selling_price : 0;
                $productName = $product->name . ' (' . $variation->name . ')';
                $imageSrc = !empty($variation->image_src) ? $variation->image_src : $product->image_src;
            } else {
                $availableStock = $product->getAvailableStock();
                $price = $product->getPriceForQuantity($qty);
                $productName = $product->name;
                $imageSrc = $product->image_src;
            }

            $maxStockReached = false;

            if ($availableStock <= 0) {
                $qty = 0;
            } elseif ($availableStock < $qty) {
                $qty = $availableStock;
                $maxStockReached = true;
            } elseif ($availableStock == $qty) {
                $maxStockReached = true;
            }

            $response[] = [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'qty' => $qty,
                'price' => $price !== null ? (float)$price : 0,
                'product_name' => $productName,
                'image_src' => $imageSrc,
                'available_stock' => $availableStock,
                'max_stock_reached' => $maxStockReached
            ];
        }

        return [
            'items' => $response,
            'someProductRemoved' => $someProductRemoved
        ];
    }
}