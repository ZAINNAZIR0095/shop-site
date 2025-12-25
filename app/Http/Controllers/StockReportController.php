<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockReportController extends Controller
{
    /**
     * Get stock report with summary and products
     */
    public function getStockReport(Request $request)
    {
        // Build query with dynamic stock calculation
        $query = Product::query();

        // Apply filters
        $this->applyFilters($query, $request);

        // Get total counts before pagination for summary
        $totalProducts = $query->count();

        // Apply pagination
        $perPage = $request->get('per_page', 15);
        $paginatedProducts = $query->paginate($perPage);

        // Transform data with accessors
        $transformedProducts = $paginatedProducts->getCollection()->map(function ($product) {
            return $this->transformProduct($product);
        });

        // Calculate summary
        $summary = $this->getSummaryStats($transformedProducts);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $transformedProducts,
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $paginatedProducts->currentPage(),
                    'last_page' => $paginatedProducts->lastPage(),
                    'per_page' => $paginatedProducts->perPage(),
                    'total' => $paginatedProducts->total(),
                    'from' => $paginatedProducts->firstItem(),
                    'to' => $paginatedProducts->lastItem(),
                ],
                'links' => [
                    'first' => $paginatedProducts->url(1),
                    'last' => $paginatedProducts->url($paginatedProducts->lastPage()),
                    'prev' => $paginatedProducts->previousPageUrl(),
                    'next' => $paginatedProducts->nextPageUrl(),
                ]
            ]
        ]);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, $request)
    {
        // Search by name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by date range (using product creation date)
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by min limit
        if ($request->has('min_limit') && $request->min_limit) {
            $query->where('min_limit', '>=', $request->min_limit);
        }

        // Stock status filters (we'll handle these after getting results)
        // For now, we'll just order
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'name':
                    $query->orderBy('name');
                    break;
                case 'stock':
                    // For stock sorting, we need a different approach
                    $query->orderBy('name');
                    break;
                case 'stock_desc':
                    $query->orderBy('name');
                    break;
                case 'value':
                    $query->orderBy('name');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Transform product data for response using model accessors
     */
    private function transformProduct($product)
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'type' => $product->type,
            'unit' => $product->unit,
            'size' => $product->size,
            'min_limit' => $product->min_limit,
            'sale_price' => $product->sale_price,
            'purchase_price' => $product->purchase_price,
            'current_stock' => $product->current_stock, // Using the accessor
            'stock_value' => $product->stock_value,     // Using the accessor
            'is_low_stock' => $product->is_low_stock,   // Using the accessor
            'is_out_of_stock' => $product->is_out_of_stock, // Using the accessor
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at
        ];
    }

    /**
     * Get summary statistics from products
     */
    private function getSummaryStats($products)
    {
        return $products->reduce(function ($carry, $product) {
            $carry['total_products']++;
            $carry['total_stock_value'] += $product['stock_value'];

            if ($product['is_low_stock']) {
                $carry['low_stock_items']++;
            }

            if ($product['is_out_of_stock']) {
                $carry['out_of_stock_items']++;
            }

            return $carry;
        }, [
            'total_products' => 0,
            'total_stock_value' => 0,
            'low_stock_items' => 0,
            'out_of_stock_items' => 0
        ]);
    }

    /**
     * Optimized version using raw SQL for better performance
     */
    public function getStockReportOptimized(Request $request)
    {
        $query = Product::select([
                'products.*',
                DB::raw('
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type IN ("purchase", "return") THEN stock_details.quantity
                                WHEN stocks.stock_type IN ("sale", "issue") THEN -stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) as current_stock_calculated
                '),
                DB::raw('
                    (COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type IN ("purchase", "return") THEN stock_details.quantity
                                WHEN stocks.stock_type IN ("sale", "issue") THEN -stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) * products.purchase_price) as stock_value_calculated
                ')
            ]);

        // Apply optimized filters
        $this->applyOptimizedFilters($query, $request);

        // Get total count for summary
        $totalQuery = clone $query;
        $summary = $this->getOptimizedSummary($totalQuery);

        // Apply pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        // Transform data
        $transformedProducts = $products->getCollection()->map(function ($product) {
            $currentStock = $product->current_stock_calculated ?? 0;
            $stockValue = $product->stock_value_calculated ?? 0;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'unit' => $product->unit,
                'size' => $product->size,
                'min_limit' => $product->min_limit,
                'sale_price' => $product->sale_price,
                'purchase_price' => $product->purchase_price,
                'current_stock' => $currentStock,
                'stock_value' => $stockValue,
                'is_low_stock' => $currentStock <= $product->min_limit && $currentStock > 0,
                'is_out_of_stock' => $currentStock <= 0,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $transformedProducts,
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]
        ]);
    }

    /**
     * Apply optimized filters with SQL
     */
    private function applyOptimizedFilters($query, $request)
    {
        // Search by name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('products.name', 'like', "%{$search}%");
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('products.created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('products.created_at', '<=', $request->date_to);
        }

        // Filter by min limit
        if ($request->has('min_limit') && $request->min_limit) {
            $query->where('products.min_limit', '>=', $request->min_limit);
        }

        // Filter by stock status
        if ($request->has('stock_status') && $request->stock_status) {
            switch ($request->stock_status) {
                case 'low':
                    $query->havingRaw('current_stock_calculated <= products.min_limit AND current_stock_calculated > 0');
                    break;
                case 'out':
                    $query->havingRaw('current_stock_calculated <= 0');
                    break;
                case 'sufficient':
                    $query->havingRaw('current_stock_calculated > products.min_limit');
                    break;
            }
        }

        // Filter by min stock level
        if ($request->has('min_stock') && $request->min_stock) {
            $query->havingRaw('current_stock_calculated >= ?', [$request->min_stock]);
        }

        // Filter by max stock level
        if ($request->has('max_stock') && $request->max_stock) {
            $query->havingRaw('current_stock_calculated <= ?', [$request->max_stock]);
        }

        // Sort products
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'name':
                    $query->orderBy('products.name');
                    break;
                case 'stock':
                    $query->orderBy('current_stock_calculated');
                    break;
                case 'stock_desc':
                    $query->orderBy('current_stock_calculated', 'desc');
                    break;
                case 'value':
                    $query->orderBy('stock_value_calculated', 'desc');
                    break;
                default:
                    $query->orderBy('products.created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('products.created_at', 'desc');
        }
    }

    /**
     * Get optimized summary statistics
     */
    private function getOptimizedSummary($query)
    {
        $products = $query->get();

        return $products->reduce(function ($carry, $product) {
            $currentStock = $product->current_stock_calculated ?? 0;
            $purchasePrice = $product->purchase_price ?? 0;

            $carry['total_products']++;
            $carry['total_stock_value'] += ($currentStock * $purchasePrice);

            if ($currentStock <= $product->min_limit && $currentStock > 0) {
                $carry['low_stock_items']++;
            }

            if ($currentStock <= 0) {
                $carry['out_of_stock_items']++;
            }

            return $carry;
        }, [
            'total_products' => 0,
            'total_stock_value' => 0,
            'low_stock_items' => 0,
            'out_of_stock_items' => 0
        ]);
    }

    /**
     * Export stock report
     */
    public function exportToExcel(Request $request)
    {
        $query = Product::select([
                'products.*',
                DB::raw('
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type IN ("purchase", "return") THEN stock_details.quantity
                                WHEN stocks.stock_type IN ("sale", "issue") THEN -stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) as current_stock
                ')
            ]);

        $this->applyOptimizedFilters($query, $request);
        $products = $query->get();

        $exportData = $products->map(function ($product) {
            $currentStock = $product->current_stock ?? 0;
            $purchasePrice = $product->purchase_price ?? 0;

            return [
                'Product Name' => $product->name,
                'Product Type' => $product->type,
                'Unit' => $product->unit,
                'Size' => $product->size,
                'Current Stock' => $currentStock,
                'Min Stock Level' => $product->min_limit,
                'Purchase Price' => $purchasePrice,
                'Sale Price' => $product->sale_price,
                'Stock Value' => ($currentStock * $purchasePrice),
                'Status' => $this->getStockStatus($currentStock, $product->min_limit),
                'Created Date' => $product->created_at->format('Y-m-d')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $exportData
        ]);
    }

    /**
     * Get stock status
     */
    private function getStockStatus($currentStock, $minLimit)
    {
        if ($currentStock <= 0) {
            return 'Out of Stock';
        } elseif ($currentStock <= $minLimit) {
            return 'Low Stock';
        } else {
            return 'In Stock';
        }
    }

    /**
     * Get low stock report
     */
    public function getLowStockReport()
    {
        $products = Product::select([
                'products.*',
                DB::raw('
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type IN ("purchase", "return") THEN stock_details.quantity
                                WHEN stocks.stock_type IN ("sale", "issue") THEN -stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) as current_stock
                ')
            ])
            ->havingRaw('current_stock <= products.min_limit AND current_stock > 0')
            ->get();

        $summary = [
            'total_low_stock_items' => $products->count(),
            'total_value_at_risk' => $products->sum(function ($product) {
                return (($product->current_stock ?? 0) * ($product->purchase_price ?? 0));
            })
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'summary' => $summary
            ]
        ]);
    }

    /**
     * Get stock report with all details
     */
    public function getDetailedStockReport(Request $request)
    {
        $query = Product::select([
                'products.*',
                DB::raw('
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type = "purchase" THEN stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) as total_purchased
                '),
                DB::raw('
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type = "sale" THEN stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) as total_sold
                '),
                DB::raw('
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type = "return" THEN stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) as total_returned
                '),
                DB::raw('
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type = "issue" THEN stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) as total_issued
                '),
                DB::raw('
                    (COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type = "purchase" THEN stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) +
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type = "return" THEN stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) -
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type = "sale" THEN stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0) -
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN stocks.stock_type = "issue" THEN stock_details.quantity
                                ELSE 0
                            END
                        )
                        FROM stock_details
                        JOIN stocks ON stock_details.stock_id = stocks.id
                        WHERE stock_details.product_id = products.id
                    ), 0)) as current_stock
                ')
            ]);

        $this->applyOptimizedFilters($query, $request);

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $transformedProducts = $products->getCollection()->map(function ($product) {
            $currentStock = $product->current_stock ?? 0;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'unit' => $product->unit,
                'size' => $product->size,
                'min_limit' => $product->min_limit,
                'sale_price' => $product->sale_price,
                'purchase_price' => $product->purchase_price,
                'current_stock' => $currentStock,
                'stock_value' => $currentStock * $product->purchase_price,
                'total_purchased' => $product->total_purchased ?? 0,
                'total_sold' => $product->total_sold ?? 0,
                'total_returned' => $product->total_returned ?? 0,
                'total_issued' => $product->total_issued ?? 0,
                'is_low_stock' => $currentStock <= $product->min_limit && $currentStock > 0,
                'is_out_of_stock' => $currentStock <= 0,
                'created_at' => $product->created_at
            ];
        });

        // Calculate summary
        $summary = $transformedProducts->reduce(function ($carry, $product) {
            $carry['total_products']++;
            $carry['total_stock_value'] += $product['stock_value'];

            if ($product['is_low_stock']) {
                $carry['low_stock_items']++;
            }

            if ($product['is_out_of_stock']) {
                $carry['out_of_stock_items']++;
            }

            $carry['total_purchased'] += $product['total_purchased'];
            $carry['total_sold'] += $product['total_sold'];
            $carry['total_returned'] += $product['total_returned'];
            $carry['total_issued'] += $product['total_issued'];

            return $carry;
        }, [
            'total_products' => 0,
            'total_stock_value' => 0,
            'low_stock_items' => 0,
            'out_of_stock_items' => 0,
            'total_purchased' => 0,
            'total_sold' => 0,
            'total_returned' => 0,
            'total_issued' => 0
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $transformedProducts,
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]
        ]);
    }
}
