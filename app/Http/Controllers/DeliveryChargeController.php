<?php

namespace App\Http\Controllers;

use App\Models\DeliveryCharge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DeliveryChargeController extends Controller
{
    /**
     * Calculate and display delivery charge.
     */
    public function getDeliveryCharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'division' => 'required|string',
            'district' => 'required|string',
        ]);

        $charge = DeliveryCharge::calculate(
            $validated['division'],
            $validated['district']
        );

        return response()->json([
            'success'         => true,
            'delivery_charge' => $charge
        ]);
    }
}
