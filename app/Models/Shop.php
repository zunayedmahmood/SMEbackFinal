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
            $qty = $item['qty'];

            $product = Product::find($productId);

            if (!$product) {
                $someProductRemoved = true;
                continue;
            }

            $maxStockReached = false;

            $availableStock = $product->getAvailableStock();

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
                'qty' => $qty,
                'price' => $product->selling_price,
                'product_name' => $product->name,
                'image_src' => $product->image_src,
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