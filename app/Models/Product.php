<?php
namespace App\Models;

use App\Models\ReservedProduct;
use App\Services\ImageKitService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'selling_price',
        'sold_count',
        'total_count',
        'image_src',
        'description',
        'has_dynamic_pricing',
        'price_slabs',
        'has_variations',
    ];

    protected $appends = ['available_stock'];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    */

    public function reservedProducts(): HasMany
    {
        return $this->hasMany(ReservedProduct::class);
    }

    protected $casts = [
        'image_src'          => 'array',
        'selling_price'      => 'decimal:2',
        'has_dynamic_pricing' => 'boolean',
        'price_slabs'        => 'array',
        'has_variations'     => 'boolean',
    ];

    public function setPriceSlabsAttribute($value)
    {
        $this->attributes['price_slabs'] = is_string($value) ? json_decode($value, true) : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function productBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(Variation::class);
    }

    /**
     * Many-to-many via the category_product pivot table.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    |
    | Required : name, selling_price
    | Optional : image_src (array of UploadedFile), description, category (id)
    |
    | - Stores images in ImageKit (folder: products)
    | - If a product with the same name already exists, appends _1, _2, etc.
    | - Attaches the product to the given category via the pivot table
    |
    */

    public static function createProduct(
        string $name,
        ?float $sellingPrice = null,
        array  $imageSrc = [],
        ?string $description = null,
        ?array  $categories_id = [],
        bool   $hasDynamicPricing = false,
        ?array $priceSlabs = null,
        bool   $hasVariations = false,
    ): self {
        // ── Resolve unique name ──────────────────────────────────────
        $finalName = $name;
        $counter   = 1;

        while (self::where('name', $finalName)->exists()) {
            $finalName = $name . '_' . $counter;
            $counter++;
        }

        // ── Store images ─────────────────────────────────────────────
        $storedPaths  = [];
        $imageKit     = new ImageKitService();

        foreach ($imageSrc as $image) {
            $storedPaths[] = $imageKit->upload($image, 'products');
        }

        // ── Create the product ──────────────────────────────────────
        $product = self::create([
            'name'                => $finalName,
            'selling_price'       => $sellingPrice !== null ? round($sellingPrice, 2) : null,
            'image_src'           => empty($storedPaths) ? [] : $storedPaths,
            'description'         => $description,
            'has_dynamic_pricing' => $hasDynamicPricing,
            'price_slabs'         => $priceSlabs,
            'has_variations'      => $hasVariations,
        ]);

        // ── Attach category (pivot) ─────────────────────────────────
        if ($categories_id !== null) {
            foreach ($categories_id as $category_id) {
                $product->categories()->attach($category_id);
            }
        }

        return $product;
    }

    /*
    |--------------------------------------------------------------------------
    | Core Retrieval
    |--------------------------------------------------------------------------
    */

    /**
     * Get a single product by its ID (with batches & categories).
     */
    public static function getProductById(int $id): ?self
    {
        return self::with(['productBatches', 'categories', 'variations', 'variations.productBatches'])->find($id);
    }

    /**
     * Get every product (with batches, categories & variations).
     */
    public static function getAllProducts()
    {
        return self::with(['productBatches', 'categories', 'variations'])->get();
    }

    /**
     * Get paginated products (with batches & categories).
     */
    public static function getAllProductsPaginated(int $perPage = 7, int $page = 1, ?string $search = null): array
    {
        $query = self::with(['categories', 'productBatches', 'variations'])
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhereHas('categories', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(function ($product) {
                return [
                    'id'            => $product->id,
                    'name'          => $product->name,
                    'description'   => $product->description,
                    'selling_price' => $product->selling_price,
                    'categories'    => $product->categories,
                    'sold_count'    => $product->sold_count,
                    'total_count'   => $product->total_count,
                    'available_stock' => $product->getAvailableStock(),
                    'image_src'     => $product->image_src ?? [],

                    'product_batches' => $product->productBatches->map(function ($batch) {
                        return [
                            'id'         => $batch->id,
                            'count'      => $batch->count,
                            'cost_price' => $batch->cost_price ?? null,
                            'created_at' => $batch->created_at,
                        ];
                    })->values(),
                ];
            }),

            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Basic Updates
    |--------------------------------------------------------------------------
    */

    /**
     * Rename the product.
     */
    public function updateProductName(string $name): void
    {
        $this->name = $name;
        $this->save();
    }

    /**
     * Update description.
     */
    public function updateDescription(?string $description): void
    {
        $this->description = $description;
        $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Category Management (pivot)
    |--------------------------------------------------------------------------
    */

    /**
     * Attach one or more categories without detaching existing ones.
     *
     * @param int|array $categoryIds  Single ID or array of IDs
     */
    public function addCategory(int|array $categoryIds): void
    {
        $this->categories()->syncWithoutDetaching($categoryIds);
    }

    /**
     * Detach one or more categories.
     *
     * @param int|array $categoryIds  Single ID or array of IDs
     */
    public function removeCategory(int|array $categoryIds): void
    {
        $this->categories()->detach($categoryIds);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    /**
     * Delete the product with the given ID (and its stored images).
     */
    public static function deleteProductById(int $id): bool
    {
        $product = self::find($id);

        if (!$product) {
            return false;
        }

        // Clean up stored images
        $imageKit = new ImageKitService();
        foreach ($product->image_src ?? [] as $url) {
            $imageKit->delete($url);
        }

        return (bool) $product->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Aggregation
    |--------------------------------------------------------------------------
    */

    public function updateTotalCount(): void
    {
        $this->total_count = $this->productBatches()->sum('count');
        $this->save();
    }

    public function getTotalCount(): int
    {
        // Read-only: sum directly from batches without writing to the DB.
        // Use updateTotalCount() explicitly when you need to persist the aggregated value.
        return (int) $this->productBatches()->sum('count');
    }

    /**
     * Get reserved quantity across all orders
     */
    public function getReservedQty(): int
    {
        return (int) $this->reservedProducts()->whereNull('variation_id')->sum('qty');
    }

    /**
     * Get available stock : total_count - reserved_qty
     */
    public function getAvailableStock(): int
    {
        if ($this->has_variations) {
            return (int) $this->variations->sum(fn ($variation) => $variation->getAvailableStock());
        }
        return $this->getTotalCount() - $this->getReservedQty();
    }

    public function getAvailableStockAttribute(): int
    {
        return $this->getAvailableStock();
    }

    /*
    |--------------------------------------------------------------------------
    | Selling Logic (Cheapest First)
    |--------------------------------------------------------------------------
    */

    public function sellProduct(int $count, ?int $variationId = null): array
    {
        if ($this->has_variations && $variationId) {
            $variation = $this->variations()->find($variationId);
            if ($variation) {
                return $variation->sellProduct($count);
            }
            return [
                'success' => false,
                'message' => 'Variation not found',
            ];
        }

        if ($this->getTotalCount() < $count) {
            return [
                'success' => false,
                'message' => 'Insufficient inventory',
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
                ProductBatch::deleteProductBatch($this, $batch);
            }
        }

        $this->sold_count += $count;
        $this->updateTotalCount();
        $this->save();

        return [
            'success'        => true,
            'totalCostPrice' => round($totalCostPrice, 2),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Price Update
    |--------------------------------------------------------------------------
    */

    public function updateSellingPrice(?float $newPrice): void
    {
        $this->selling_price = $newPrice !== null ? round($newPrice, 2) : null;
        $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Dynamic Pricing Logic
    |--------------------------------------------------------------------------
    */

    /**
     * Get the unit price for a given quantity.
     * 
     * @param int $qty
     * @return float|null
     */
    public function getPriceForQuantity(int $qty, ?int $variationId = null): ?float
    {
        if ($this->has_variations && $variationId) {
            $variation = $this->variations()->find($variationId);
            if ($variation) {
                return $variation->getPriceForQuantity($qty);
            }
        }

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

    /*
    |--------------------------------------------------------------------------
    | Image Handling
    |--------------------------------------------------------------------------
    */

    public function addNewImage(array $images): void
    {
        $paths    = $this->image_src ?? [];
        $imageKit = new ImageKitService();

        foreach ($images as $image) {
            $paths[] = $imageKit->upload($image, 'products');
        }

        $this->image_src = $paths;
        $this->save();
    }

    public function deleteImage(array $pathsToDelete): void
    {
        $currentImages = collect($this->image_src ?? []);
        $imageKit      = new ImageKitService();

        foreach ($pathsToDelete as $url) {
            $imageKit->delete($url);
            $currentImages = $currentImages->reject(fn($img) => $img === $url);
        }

        $this->image_src = $currentImages->values()->toArray();
        $this->save();
    }
}