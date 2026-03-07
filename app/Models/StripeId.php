<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeId extends Model
{
    protected $fillable = [
        'order_id',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
