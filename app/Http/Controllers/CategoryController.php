<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     *
     * @return JsonResponse
     */
    public function getAllCategories(): JsonResponse
    {
        $categories = Category::getAllCategories();

        return response()->json([
            'success' => true,
            'data'    => $categories
        ], 200);
    }

    /**
     * Store a newly created category.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function createNewCategory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $category = Category::createCategory($validated['name']);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'The category name is reserved.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data'    => $category
        ], 201);
    }

    /**
     * Display the specified category.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getCategoryById(int $id): JsonResponse
    {
        $category = Category::getCategoryById($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $category
        ], 200);
    }

    /**
     * Update the specified category.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateCategoryName(Request $request, int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        if (!$category->updateCategoryName($validated['name'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update this category or the name is reserved.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data'    => $category
        ], 200);
    }

    /**
     * Remove the specified category.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function deleteCategory(int $id): JsonResponse
    {
        $success = Category::deleteCategory($id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found or could not be deleted.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.'
        ], 200);
    }

    /**
     * Get inventory overview (Total product counts) for all categories.
     *
     * @return JsonResponse
     */
    public function getCategoryInventory(): JsonResponse
    {
        $inventory = Category::getCategoryInventory();

        return response()->json([
            'success' => true,
            'data'    => $inventory
        ], 200);
    }
}