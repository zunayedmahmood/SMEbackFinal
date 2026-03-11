<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_list_id',
        'order_id',
        'order_status',
        'payment_status',
        'ordered_products',
        'customer_details',
        'address',
        'payment_method',
        'delivery_charge',
        'total_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ordered_products' => 'array',
        'customer_details' => 'array',
        'address' => 'array',
        'delivery_charge' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function orderList(): BelongsTo
    {
        return $this->belongsTo(OrderList::class);
    }

    /**
     * Relationship: An order has one StripeId record.
     */
    public function stripeIdRecord(): HasOne
    {
        return $this->hasOne(StripeId::class);
    }

    public function reservedProducts(): HasMany
    {
        return $this->hasMany(ReservedProduct::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending(Builder $query): void
    {
        $query->where('order_status', 'Pending');
    }

    public function scopeConfirmed(Builder $query): void
    {
        $query->where('order_status', 'Confirmed');
    }

    /*
    |--------------------------------------------------------------------------
    | Domain Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark order as confirmed
     */
    public function confirm(): bool
    {
        if ($this->order_status === 'Confirmed' && $this->payment_status === 'Paid') {
            return false;
        }

        // Finalize inventory if reservations exist
        try {
            if ($this->reservedProducts()->exists()) {
                \App\Actions\CommitInventoryAction::run($this);
            }
        } catch (\Exception $e) {
            // Log or rethrow if inventory commit is critical
            \Illuminate\Support\Facades\Log::error("Inventory commit failed during order confirmation: " . $e->getMessage());
            return false;
        }

        return $this->update([
            'order_status'   => 'Confirmed',
            'payment_status' => 'Paid',
        ]);
    }

    /**
     * Retrieve order by unique 7-digit order_id
     */
    public static function findByOrderId(string $orderId): ?self
    {
        return self::where('order_id', $orderId)
            ->with(['stripeIdRecord', 'orderList'])
            ->first();
    }

    /**
     * Generate a unique 7-digit order ID.
     */
    public static function generateUniqueId(): string
    {
        do {
            $id = str_pad((string)random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (self::where('order_id', $id)->exists());

        return $id;
    }
}