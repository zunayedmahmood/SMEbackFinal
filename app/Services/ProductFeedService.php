<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ProductFeedService
{
    /**
     * Get product feed with filtering, sorting, blending and pagination.
     */
    public function getFeed(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        $query = $this->applyFilters($query, $filters);

        $query = $this->applySortingOrBlending($query, $filters);

        return $query->paginate(
            $filters['per_page'] ?? 12
        );
    }

    /**
     * Base query — always eager loads categories
     */
    private function baseQuery(): Builder
    {
        return Product::query()
            ->whereHas('productBatches', function ($query) {
                $query->where('count', '>', 0);
            })
            ->with(['categories:id,name'])
            ->select('products.*');
    }

    /**
     * Apply filtering logic
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        // Filter by categories (Many-to-Many)
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->whereIn('categories.id', $filters['categories']);
            });
        }

        // Filter by minimum price
        if (isset($filters['min_price'])) {
            $query->where('selling_price', '>=', $filters['min_price']);
        }

        // Filter by maximum price
        if (isset($filters['max_price'])) {
            $query->where('selling_price', '<=', $filters['max_price']);
        }

        // Search by name
        if (!empty($filters['search'])) {
            $query->where('name', 'LIKE', '%' . $filters['search'] . '%');
        }

        return $query;
    }

    /**
     * Apply explicit sorting OR blended feed
     */
    private function applySortingOrBlending(Builder $query, array $filters): Builder
    {
        if (!empty($filters['sort_by'])) {
            return $this->applySorting($query, $filters['sort_by']);
        }

        return $this->applyBlendedFeed($query);
    }

    /**
     * Explicit sorting logic
     */
    private function applySorting(Builder $query, string $sortBy): Builder
    {
        return match ($sortBy) {
            'newest' => $query->orderByDesc('created_at'),
            'most_sold' => $query->orderByDesc('sold_count'),
            'price_low_high' => $query->orderBy('selling_price'),
            'price_high_low' => $query->orderByDesc('selling_price'),
            default => $this->applyBlendedFeed($query),
        };
    }

    /**
     * Blended feed logic
     */
    private function applyBlendedFeed(Builder $query): Builder
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        return $query->orderByRaw("
            CASE
                WHEN created_at >= ? THEN 1
                WHEN sold_count >= (
                    SELECT AVG(sold_count) FROM products
                ) THEN 2
                ELSE 3
            END
        ", [$sevenDaysAgo])
        ->orderByDesc('created_at');
    }
}