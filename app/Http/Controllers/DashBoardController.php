<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function getStats()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonth = Carbon::now()->startOfMonth();

        // Total products
        $totalProducts = Product::count();

        // Today's sales
        $todaySales = Stock::where('stock_type', 'sale')
            ->whereDate('date', $today)
            ->sum('net_price');

        // Yesterday's sales for comparison
        $yesterdaySales = Stock::where('stock_type', 'sale')
            ->whereDate('date', $yesterday)
            ->sum('net_price');

        $salesChange = $yesterdaySales > 0
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 2)
            : 100;

        // Low stock count - Get from current stock levels
        $lowStockCount = $this->getLowStockCount();

        // Monthly profit
        $monthlyProfit = $this->calculateMonthlyProfit();

        // Get profit margin
        $profitMargin = $this->calculateProfitMargin();

        return response()->json([
            'success' => true,
            'data' => [
                'total_products' => $totalProducts,
                'products_vs_target' => min(100, round(($totalProducts / 100) * 100)),
                'today_sales' => $todaySales,
                'yesterday_sales' => $yesterdaySales,
                'sales_change' => $salesChange,
                'sales_trend' => $salesChange >= 0 ? 'up' : 'down',
                'low_stock_count' => $lowStockCount,
                'monthly_profit' => $monthlyProfit,
                'profit_margin' => $profitMargin,
                'user_name' => 'no user'
            ]
        ]);
    }

    public function getProfitTrend()
    {
        $days = 30;
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->format('Y-m-d');

            // Get sales for the day
            $sales = Stock::where('stock_type', 'sale')
                ->whereDate('date', $date)
                ->with('details.product')
                ->get();

            $revenue = $sales->sum('net_price');
            $cost = 0;
            $profit = 0;

            // Calculate cost and profit for each sale
            foreach ($sales as $sale) {
                foreach ($sale->details as $detail) {
                    // Get average cost price from stock history
                    $avgCost = $this->getProductAverageCost($detail->product_id, $date);
                    $cost += $avgCost * $detail->quantity;
                }
            }

            $profit = $revenue - $cost;

            $data[] = [
                'date' => $date->format('M d'),
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getStockOverview()
    {
        // Get current stock levels for each product
        $productsWithStock = Product::
            select([
                'products.id',
                'products.name',
                'products.sale_price',
                DB::raw('COALESCE((
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
                ), 0) as current_stock')
            ])
            ->having('current_stock', '>', 0)
            ->get();

        // Group by category and calculate total value
        $overview = [];
        foreach ($productsWithStock as $product) {
            $category = 'Uncategorized';
            $value = $product->current_stock * $product->sale_price;

            if (!isset($overview[$category])) {
                $overview[$category] = 0;
            }
            $overview[$category] += $value;
        }

        // Format for chart
        $chartData = [];
        foreach ($overview as $category => $totalValue) {
            $chartData[] = [
                'category' => $category,
                'total_value' => $totalValue
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $chartData
        ]);
    }

    public function getLowStockProducts()
    {
        // Get products with their current stock levels
        $products = Product::select([
                'products.*',
                DB::raw('COALESCE((
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
                ), 0) as current_stock')
            ])
            ->havingRaw('current_stock <= products.min_limit OR current_stock <= 10')
            ->orderBy('current_stock', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function getRecentActivities()
    {
        $activities = Stock::with(['user', 'details.product'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($stock) {
                $description = $this->generateActivityDescription($stock);

                return [
                    'id' => $stock->id,
                    'type' => $stock->stock_type,
                    'description' => $description,
                    'user' => $stock->user,
                    'created_at' => $stock->created_at
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    private function getLowStockCount()
    {
        return Product::select([
                'products.id',
                'products.min_limit',
                DB::raw('COALESCE((
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
                ), 0) as current_stock')
            ])
            ->havingRaw('current_stock <= min_limit')
            ->count();
    }

    private function calculateMonthlyProfit()
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $sales = Stock::where('stock_type', 'sale')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->with('details.product')
            ->get();

        $revenue = $sales->sum('net_price');
        $cost = 0;

        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                // Get average cost price at the time of sale
                $avgCost = $this->getProductAverageCost($detail->product_id, $sale->date);
                $cost += $avgCost * $detail->quantity;
            }
        }

        return $revenue - $cost;
    }

    private function calculateProfitMargin()
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $sales = Stock::where('stock_type', 'sale')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->with('details.product')
            ->get();

        $revenue = $sales->sum('net_price');

        if ($revenue == 0) return 0;

        $cost = 0;
        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                $avgCost = $this->getProductAverageCost($detail->product_id, $sale->date);
                $cost += $avgCost * $detail->quantity;
            }
        }

        $profit = $revenue - $cost;
        return round(($profit / $revenue) * 100, 2);
    }

    private function getProductAverageCost($productId, $asOfDate = null)
    {
        if (!$asOfDate) {
            $asOfDate = Carbon::now();
        }

        // Get all purchases before or on the given date
        $purchases = StockDetail::join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
        ->join('products' , 'stock_details.product_id' , 'products.id')
            ->where('stock_details.product_id', $productId)
            ->where('stocks.stock_type', 'purchase')
            ->whereDate('stocks.date', '<=', $asOfDate)
            ->select(
                'stock_details.quantity',
                'products.purchase_price as cost_price'
            )
            ->get();

        if ($purchases->isEmpty()) {
            // Fallback to product's cost price if no purchases found
            $product = Product::find($productId);
            return $product ? $product->cost_price : 0;
        }

        $totalQuantity = 0;
        $totalCost = 0;

        foreach ($purchases as $purchase) {
            $totalQuantity += $purchase->quantity;
            $totalCost += $purchase->quantity * $purchase->cost_price;
        }

        return $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
    }

    private function generateActivityDescription($stock)
    {
        $type = ucfirst($stock->stock_type);
        $party = $stock->party_name ? "to {$stock->party_name}" : '';
        $amount = "for PKR " . number_format($stock->net_price, 2);
        $itemCount = $stock->details->count();

        return "{$type} transaction {$party} {$amount} ({$itemCount} items)";
    }

    /**
     * Get current stock for a specific product
     */
    public function getProductCurrentStock($productId)
    {
        $stock = DB::table('stock_details')
            ->join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->where('stock_details.product_id', $productId)
            ->selectRaw('SUM(
                CASE
                    WHEN stocks.stock_type IN ("purchase", "return") THEN stock_details.quantity
                    WHEN stocks.stock_type IN ("sale", "issue") THEN -stock_details.quantity
                    ELSE 0
                END
            ) as current_stock')
            ->value('current_stock');

        return $stock ?? 0;
    }
}
