<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::delete('/cart/item/{id}', [CartController::class, 'delete']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::post('/cart/update', [CartController::class, 'update']);
});

Route::apiResource('products', ProductController::class)
    ->only(['index', 'show']);

Route::apiResource('products', ProductController::class)
    ->only(['store', 'update', 'destroy'])
    ->middleware(['auth:sanctum','role:admin']);

Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show']);

Route::apiResource('categories', CategoryController::class)
    ->only(['store', 'update', 'destroy'])
    ->middleware(['auth:sanctum','role:admin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/from-cart', [OrderController::class, 'placeOrderFromCart']);
});

Route::match(['POST', 'GET'],'/payment/process', [PaymentController::class, 'paymentProcess'])->name('payment.process');
Route::match(['GET','POST'],'/payment/callback', [PaymentController::class, 'callBack']);

// Upload single image for a product
Route::post('/products/images', [ProductImageController::class, 'upload']);

// Upload multiple images for a product
Route::post('/products/images/multiple', [ProductImageController::class, 'uploadMultiple']);

// Update an image
Route::put('/product-images', [ProductImageController::class, 'update']);

// Delete an image
Route::delete('/product-images/{productImage}', [ProductImageController::class, 'destroy']);
