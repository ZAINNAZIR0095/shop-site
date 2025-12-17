<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// Use apiResource for all RESTful routes
Route::apiResource('products', ProductController::class);
