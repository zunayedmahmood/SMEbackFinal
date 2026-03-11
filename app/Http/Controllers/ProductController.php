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
        $product = Product::with(['productBatches', 'categories'])->findOrFail($id);

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
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'image_src'     => 'nullable|array',
            'image_src.*'   => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'description'   => 'nullable|string',
            'categories_id'   => 'nullable|array',
            'categories_id.*' => 'integer|exists:categories,id',
        ]);

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
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $product->updateProductName($validated['name']);

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
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string',
        ]);

        $product->updateDescription($validated['description']);

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
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'selling_price' => 'required|numeric|min:0',
        ]);

        $product->updateSellingPrice((float)$validated['selling_price']);

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
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_ids'   => 'required|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $product->addCategory($validated['category_ids']);

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
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_ids'   => 'required|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $product->removeCategory($validated['category_ids']);

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
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Product not found.");
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
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

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
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'paths'   => 'required|array',
            'paths.*' => 'string',
        ]);

        $product->deleteImage($validated['paths']);

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
        $product = Product::findOrFail($id);

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
        $product = Product::findOrFail($id);

        return response()->json([
            'success'     => true,
            'total_count' => $product->getTotalCount()
        ], 200);
    }
}