<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCharge extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    /**
     * Get the global delivery charge.
     */
    public static function calculate(): float
    {
        return (float) SiteSetting::getValue('delivery_charge', 15.00);
    }
}
