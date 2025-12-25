<?php

use App\Http\Controllers\StockController;
use App\Http\Controllers\StockReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashBoardController;

// Clear route cache first
// php artisan route:clear

// 1. API Routes (MUST come first)
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);
Route::put('/products/{product}', [ProductController::class, 'update']);

// stock routes
// routes/api.php

// Stock routes
Route::apiResource('stocks', StockController::class);
Route::get('/stocks/{stock}', [StockController::class, 'show'])->name('stocks.show');
Route::get('/stocks/parties/list', [StockController::class, 'getParties']);

// Stock report routes
       Route::get('/stocks-reports', [StockReportController::class, 'getStockReportOptimized']); // Use optimized version
    Route::get('/stocks/report/detailed', [StockReportController::class, 'getDetailedStockReport']);
    Route::get('/stocks/export', [StockReportController::class, 'exportToExcel']);
    Route::get('/stocks/low-stock', [StockReportController::class, 'getLowStockReport']);

    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/profit-trend', [DashboardController::class, 'getProfitTrend']);
        Route::get('/stock-overview', [DashboardController::class, 'getStockOverview']);
        Route::get('/low-stock-products', [DashboardController::class, 'getLowStockProducts']);
        Route::get('/recent-activities', [DashboardController::class, 'getRecentActivities']);
    });


// Products for dropdown with current stock
Route::get('/products/for-stock', function(Request $request) {
    $products = \App\Models\Product::select('id', 'name', 'unit', 'size', 'sale_price', 'purchase_price')
        ->addSelect([
            \Illuminate\Support\Facades\DB::raw('(
                (SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "purchase" THEN stock_details.quantity ELSE 0 END), 0) FROM stock_details JOIN stocks ON stock_details.stock_id = stocks.id WHERE stock_details.product_id = products.id) +
                (SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "return" THEN stock_details.quantity ELSE 0 END), 0) FROM stock_details JOIN stocks ON stock_details.stock_id = stocks.id WHERE stock_details.product_id = products.id) -
                (SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "sale" THEN stock_details.quantity ELSE 0 END), 0) FROM stock_details JOIN stocks ON stock_details.stock_id = stocks.id WHERE stock_details.product_id = products.id) -
                (SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "issue" THEN stock_details.quantity ELSE 0 END), 0) FROM stock_details JOIN stocks ON stock_details.stock_id = stocks.id WHERE stock_details.product_id = products.id)
            ) as current_stock')
        ])
        ->when($request->has('search'), function($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%');
        })
        ->orderBy('name')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $products
    ]);
});

// 2. SPA Routes (Vue.js) - Catch-all should be LAST
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');

// 3. Home route
Route::get('/', function () {
    return view('welcome');
});
