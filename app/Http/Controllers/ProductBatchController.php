<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductBatchController extends Controller
{
    /**
     * Add inventory to a product.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function addInventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'cost_price' => 'required|numeric|min:0',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        ProductBatch::addInventory($product, (float)$validated['cost_price'], (int)$validated['quantity']);

        return response()->json([
            'success' => true,
            'message' => 'Inventory added successfully.',
            'data'    => $product->fresh('productBatches')
        ], 200);
    }

    /**
     * Remove inventory from a specific batch.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function removeInventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'       => 'required|integer|exists:products,id',
            'product_batch_id' => 'required|integer|exists:product_batches,id',
            'quantity'         => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        ProductBatch::removeInventory($product, (int)$validated['product_batch_id'], (int)$validated['quantity']);

        return response()->json([
            'success' => true,
            'message' => 'Inventory removed successfully.',
            'data'    => $product->fresh('productBatches')
        ], 200);
    }

    /**
     * Delete a specific product batch.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deleteProductBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'       => 'required|integer|exists:products,id',
            'product_batch_id' => 'required|integer|exists:product_batches,id',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        ProductBatch::deleteProductBatch($product, (int)$validated['product_batch_id']);
        $product->updateTotalCount();

        return response()->json([
            'success' => true,
            'message' => 'Product batch deleted successfully.',
            'data'    => $product->fresh('productBatches')
        ], 200);
    }
}
