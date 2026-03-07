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
    protected $fillable = [
        'division',
        'district',
        'charge',
    ];

    /**
     * Get the delivery charge based on division and district.
     */
    public static function calculate(?string $division, ?string $district): float
    {
        // Try to find in database first
        $record = self::where('division', $division)
            ->where('district', $district)
            ->first();

        if ($record) {
            return (float) $record->charge;
        }

        // Fallback to legacy hardcoded logic
        if ($district === "Dhaka") {
            return 80.0;
        }

        if ($division === "Dhaka" && $district !== "Dhaka") {
            return 100.0;
        }

        return 120.0;
    }
}
