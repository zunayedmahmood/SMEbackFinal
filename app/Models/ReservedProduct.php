<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservedProduct extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'price',
        'total',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}