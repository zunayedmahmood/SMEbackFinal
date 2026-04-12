<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variation extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'selling_price',
        'has_dynamic_pricing',
        'price_slabs',
        'image_src',
    ];

    protected $appends = ['available_stock'];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'has_dynamic_pricing' => 'boolean',
        'price_slabs' => 'array',
        'image_src' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function reservedProducts(): HasMany
    {
        return $this->hasMany(ReservedProduct::class);
    }

    public function getTotalCount(): int
    {
        return (int) $this->productBatches()->sum('count');
    }

    public function getReservedQty(): int
    {
        return (int) $this->reservedProducts()->sum('qty');
    }

    public function getAvailableStock(): int
    {
        return $this->getTotalCount() - $this->getReservedQty();
    }

    public function getAvailableStockAttribute(): int
    {
        return $this->getAvailableStock();
    }

    public function getPriceForQuantity(int $qty): ?float
    {
        if ($this->has_dynamic_pricing && !empty($this->price_slabs)) {
            foreach ($this->price_slabs as $slab) {
                $min = $slab['min_qty'] ?? 0;
                $max = $slab['max_qty'] ?? PHP_INT_MAX;

                if ($qty >= $min && $qty <= $max) {
                    return (float) $slab['price'];
                }
            }
        }
        return $this->selling_price ? (float) $this->selling_price : null;
    }

    public function sellProduct(int $count): array
    {
        if ($this->getTotalCount() < $count) {
            return [
                'success' => false,
                'message' => 'Insufficient inventory for variation',
            ];
        }

        $remaining      = $count;
        $totalCostPrice = 0;

        $batches = $this->productBatches()
            ->orderBy('cost_price', 'asc')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            if ($batch->count > $remaining) {
                $batch->count   -= $remaining;
                $totalCostPrice += $remaining * $batch->cost_price;
                $batch->save();
                $remaining = 0;
            } else {
                $totalCostPrice += $batch->count * $batch->cost_price;
                $remaining      -= $batch->count;
                ProductBatch::deleteProductBatch($this->product, $batch);
            }
        }

        // We update the parent product's sold_count and total_count
        $this->product->increment('sold_count', $count);
        $this->product->updateTotalCount();

        return [
            'success'        => true,
            'totalCostPrice' => round($totalCostPrice, 2),
        ];
    }
}
