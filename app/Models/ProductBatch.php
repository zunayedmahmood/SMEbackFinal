<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'count',
        'cost_price',
    ];

    protected $casts = [
        'cost_price' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Logic
    |--------------------------------------------------------------------------
    */

    public static function addInventory(Product $product, float $costPrice, int $quantity): void
    {
        $costPrice = round($costPrice, 2);

        $existingBatch = $product->productBatches()
            ->whereRaw('ROUND(cost_price, 2) = ?', [$costPrice])
            ->first();

        if ($existingBatch) {
            $existingBatch->count += $quantity;
            $existingBatch->save();
        } else {
            $product->productBatches()->create([
                'cost_price' => $costPrice,
                'count'      => $quantity,
            ]);
        }

        $product->updateTotalCount();
    }

    public static function removeInventory(Product $product, int $productBatchId, int $qty): void
    {
        $batch = self::findOrFail($productBatchId);

        if ($batch->product_id !== $product->id) {
            return;
        }

        $batch->count -= $qty;

        if ($batch->count <= 0) {
            self::deleteProductBatch($product, $batch);
        } else {
            $batch->save();
        }

        $product->updateTotalCount();
    }

    public static function deleteProductBatch(Product $product, $productBatch): void
    {
        if (is_numeric($productBatch)) {
            $productBatch = self::find($productBatch);
        }

        if ($productBatch && $productBatch->product_id === $product->id) {
            $productBatch->delete();
        }
    }
}