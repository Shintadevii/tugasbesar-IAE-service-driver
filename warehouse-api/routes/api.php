<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WarehouseController;

Route::prefix('v1')->group(function () {

    // Products
    Route::apiResource('products', ProductController::class);

    // Stock (nested under product)
    Route::get('products/{product}/stock',          [StockController::class, 'index']);
    Route::post('products/{product}/stock',         [StockController::class, 'store']);
    Route::patch('products/{product}/stock/{stock}',[StockController::class, 'update']);

    // Orders
    Route::apiResource('orders', OrderController::class)->only(['index','store','show','update']);
    
    // Warehouse Operations (REST API untuk demo & monitoring)
    Route::prefix('warehouse')->group(function () {
        Route::get('/orders', [WarehouseController::class, 'orders']);
        Route::get('/products', [WarehouseController::class, 'products']);
        Route::get('/stocks', [WarehouseController::class, 'stocks']);
        Route::get('/low-stock', [WarehouseController::class, 'lowStock']);
        Route::patch('/orders/{id}/status', [WarehouseController::class, 'updateOrderStatus']);
        Route::post('/dispatch/{trackingNumber}', [WarehouseController::class, 'dispatch']);
    });
});