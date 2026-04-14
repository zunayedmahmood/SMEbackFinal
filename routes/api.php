<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryImageController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\DeliveryChargeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderListController;
use App\Http\Controllers\ProductBatchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StripeIdController;
use App\Http\Controllers\StripeWebhookController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Webhook 
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);


// Category
Route::get('/category/inventory', [CategoryController::class, 'getCategoryInventory']);
Route::get('/category/{id}/image', [CategoryImageController::class, 'getImage']);

// Product
Route::get('/product/feed', [ShopController::class, 'getProductFeed']);
Route::get('/product/{id}', [ProductController::class, 'getProductById']);
Route::post('/cart/update', [ShopController::class, 'updateForCart']);
Route::get('/shop/stats', [ShopController::class, 'getPublicStats']);

// Order
Route::post('/order/sell', [ShopController::class, 'sellProduct']);
Route::get('/order/{order_id}', [OrderController::class, 'getOrderById']);
Route::post('/order/manual-payment', [OrderController::class, 'manualOrderPayment']);

// Delivery
Route::get('/admin/settings/delivery', [DeliveryChargeController::class, 'getDeliveryCharge']);

// Contact
Route::post('/contact', [ContactMessageController::class, 'saveMessage']);

/*
|--------------------------------------------------------------------------
| Private Routes (Admin)
|--------------------------------------------------------------------------
*/

// Auth
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin Category Image (must be registered BEFORE the wildcard /category/{id} route)
    Route::post('/category/image', [CategoryImageController::class, 'saveImage']);
    Route::patch('/category/image/update', [CategoryImageController::class, 'updateImage']);

    // Admin Category
    Route::post('/category', [CategoryController::class, 'createNewCategory']);
    Route::get('/category', [CategoryController::class, 'getAllCategories']);
    Route::get('/category/{id}', [CategoryController::class, 'getCategoryById']);
    Route::patch('/category/{id}', [CategoryController::class, 'updateCategoryName']);
    Route::delete('/category/{id}', [CategoryController::class, 'deleteCategory']);
    Route::delete('/category/{id}/image', [CategoryImageController::class, 'deleteImage']);

    // Admin Product
    Route::get('/admin/product', [ProductController::class, 'getAllProducts']);
    Route::get('/admin/product/paginated', [ProductController::class, 'getAllProductsPaginated']);
    Route::post('/product', [ProductController::class, 'createProduct']);
    Route::delete('/product/{id}', [ProductController::class, 'deleteProductById']);
    
    Route::patch('/product/{id}/name', [ProductController::class, 'updateProductName']);
    Route::patch('/product/{id}/price', [ProductController::class, 'updateSellingPrice']);
    Route::patch('/product/{id}/dynamic-pricing', [ProductController::class, 'updateDynamicPricing']);
    Route::patch('/product/{id}/description', [ProductController::class, 'updateProductDescription']);
    
    Route::post('/product/{id}/categories', [ProductController::class, 'addCategory']);
    Route::delete('/product/{id}/categories', [ProductController::class, 'removeCategory']);
    
    Route::post('/product/{id}/images', [ProductController::class, 'addNewImage']);
    Route::delete('/product/{id}/images', [ProductController::class, 'deleteImage']);
    
    Route::get('/product/{id}/total-count', [ProductController::class, 'getTotalCount']);
    Route::post('/product/{id}/update-total-count', [ProductController::class, 'updateTotalCount']);

    // Admin Variations
    Route::patch('/product/{id}/has-variations', [ProductController::class, 'updateHasVariations']);
    Route::post('/product/{id}/variation', [ProductController::class, 'createVariation']);
    Route::patch('/variation/{id}', [ProductController::class, 'updateVariation']);
    Route::delete('/variation/{id}', [ProductController::class, 'deleteVariation']);
    Route::post('/variation/{id}/images', [ProductController::class, 'addVariationImage']);
    Route::delete('/variation/{id}/images', [ProductController::class, 'deleteVariationImage']);

    // Admin Inventory (Product Batch)
    Route::post('/inventory/add', [ProductBatchController::class, 'addInventory']);
    Route::post('/inventory/remove', [ProductBatchController::class, 'removeInventory']);
    Route::delete('/inventory/batch', [ProductBatchController::class, 'deleteProductBatch']);

    // Admin Order
    Route::get('/admin/order-list', [OrderListController::class, 'index']);
    Route::post('/order/{order_id}/confirm-payment', [OrderController::class, 'confirmOrderPayment']);
    Route::post('/order/create', [OrderListController::class, 'store']);
    Route::patch('/order/{order_id}/update', [OrderController::class, 'updateOrder']);
    Route::patch('/order/stripe-id', [StripeIdController::class, 'updateRecord']);
    Route::delete('/order/{order_id}', [OrderController::class, 'deleteOrder']);
    

    // Admin Contact
    Route::get('/admin/contact', [ContactMessageController::class, 'getMessages']);
    Route::delete('/admin/contact/{id}', [ContactMessageController::class, 'deleteMessage']);

    // Admin Settings
    Route::post('/admin/settings/delivery', [DeliveryChargeController::class, 'updateDeliveryCharge']);
});