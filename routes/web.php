<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// Clear route cache first
// php artisan route:clear

// 1. API Routes (MUST come first)
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);
Route::put('/products/{product}', [ProductController::class, 'update']);

// 2. SPA Routes (Vue.js) - Catch-all should be LAST
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');

// 3. Home route
Route::get('/', function () {
    return view('welcome');
});
