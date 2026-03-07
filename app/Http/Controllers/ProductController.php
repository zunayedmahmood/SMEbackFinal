<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Get all products.
     *
     * @return JsonResponse
     */
    public function getAllProducts(): JsonResponse
    {
        $products = Product::getAllProducts();

        return response()->json([
            'success' => true,
            'data'    => $products
        ], 200);
    }

    /**
     * Get paginated products.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getAllProductsPaginated(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 7);
        $page    = $request->input('page', 1);
        $search  = $request->input('search');

        $result = Product::getAllProductsPaginated((int)$perPage, (int)$page, $search);

        return response()->json([
            'success' => true,
            'data'    => $result
        ], 200);
    }

    /**
     * Get product by ID.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getProductById(int $id): JsonResponse
    {
        $product = Product::getProductById($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $product
        ], 200);
    }

    /**
     * Create a new product.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function createProduct(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'image_src'     => 'nullable|array',
            'image_src.*'   => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'description'   => 'nullable|string',
            'categories_id'   => 'nullable|array',
            'categories_id.*' => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $product = Product::createProduct(
            $request->name,
            (float)$request->selling_price,
            $request->file('image_src', []),
            $request->description,
            $request->categories_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data'    => $product
        ], 201);
    }

    /**
     * Update product name.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateProductName(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $product->updateProductName($request->name);

        return response()->json([
            'success' => true,
            'message' => 'Product name updated successfully.',
            'data'    => $product
        ], 200);
    }

    /**
     * Update product description.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateProductDescription(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $product->updateDescription($request->description);

        return response()->json([
            'success' => true,
            'message' => 'Product description updated successfully.',
            'data'    => $product
        ], 200);
    }

    /**
     * Update selling price.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateSellingPrice(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'selling_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $product->updateSellingPrice((float)$request->selling_price);

        return response()->json([
            'success' => true,
            'message' => 'Selling price updated successfully.',
            'data'    => $product
        ], 200);
    }

    /**
     * Add categories to product.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function addCategory(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_ids'   => 'required|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $product->addCategory($request->category_ids);

        return response()->json([
            'success' => true,
            'message' => 'Categories added successfully.',
            'data'    => $product->fresh('categories')
        ], 200);
    }

    /**
     * Remove categories from product.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function removeCategory(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_ids'   => 'required|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $product->removeCategory($request->category_ids);

        return response()->json([
            'success' => true,
            'message' => 'Categories removed successfully.',
            'data'    => $product->fresh('categories')
        ], 200);
    }

    /**
     * Delete product by ID.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function deleteProductById(int $id): JsonResponse
    {
        if (!Product::deleteProductById($id)) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.'
        ], 200);
    }

    /**
     * Add new images to product.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function addNewImage(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $product->addNewImage($request->file('images'));

        return response()->json([
            'success' => true,
            'message' => 'Images added successfully.',
            'data'    => $product
        ], 200);
    }

    /**
     * Delete images from product.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function deleteImage(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'paths'   => 'required|array',
            'paths.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $product->deleteImage($request->paths);

        return response()->json([
            'success' => true,
            'message' => 'Images deleted successfully.',
            'data'    => $product
        ], 200);
    }

    /**
     * Manually trigger total count update.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateTotalCount(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        $product->updateTotalCount();

        return response()->json([
            'success'     => true,
            'message'     => 'Total count updated.',
            'total_count' => $product->total_count
        ], 200);
    }

    /**
     * Get total count of product.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getTotalCount(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        return response()->json([
            'success'     => true,
            'total_count' => $product->getTotalCount()
        ], 200);
    }
}