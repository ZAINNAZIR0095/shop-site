<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionReportExport;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionReport extends Controller
{
    /**
     * Get sales and purchase report
     */
    public function index(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'type' => 'nullable|in:sale,purchase',
                'search' => 'nullable|string|max:255',
                'payment_status' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'sort_by' => 'nullable|string',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            // Base query
            $query = Stock::with(['details.product'])
                ->whereIn('stock_type', ['sale', 'purchase'])
                ->select([
                    'id',
                    'stock_type',
                    'date',
                    'party_name',
                    'party_phone',
                    'party_address',
                    'description',
                    'net_price',
                    'created_at',
                ]);

            // Apply filters
            if ($request->filled('type')) {
                $query->where('stock_type', $request->type);
            }

            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('party_name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhereHas('details.product', function($q) use ($search) {
                          $q->where('name', 'like', '%' . $search . '%');
                      });
                });
            }

            // Apply sorting
            $sortField = 'date';
            $sortDirection = 'desc';

            if ($request->filled('sort_by')) {
                $sortBy = $request->sort_by;
                if (str_starts_with($sortBy, '-')) {
                    $sortField = substr($sortBy, 1);
                    $sortDirection = 'desc';
                } else {
                    $sortField = $sortBy;
                    $sortDirection = 'asc';
                }

                // Map sort fields
                $fieldMapping = [
                    'date' => 'date',
                    'amount' => 'net_price',
                    'type' => 'stock_type'
                ];

                $sortField = $fieldMapping[$sortField] ?? 'date';
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('date', 'desc');
            }

            // Get paginated results
            $perPage = $request->get('per_page', 25);
            $transactions = $query->paginate($perPage);

            // Transform the data for response
            $transformedTransactions = $transactions->getCollection()->map(function($stock) {
                return $this->formatTransaction($stock);
            });

            // Get summary statistics
            $summary = $this->getSummaryStats($request);

            // Get chart data
            $chartData = $this->getChartData($request);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transformedTransactions,
                    'summary' => $summary,
                    'chart_data' => $chartData,
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'from' => $transactions->firstItem(),
                        'to' => $transactions->lastItem(),
                        'total' => $transactions->total(),
                        'per_page' => $transactions->perPage()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transaction report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format transaction for response
     */
    private function formatTransaction($stock)
    {
        // Get the first product from details for display
        $firstDetail = $stock->details->first();
        $product = $firstDetail->product ?? null;

        // Calculate total quantity across all items
        $totalQuantity = $stock->details->sum('quantity');

        // Calculate average unit price (weighted by quantity)
        $totalAmount = $stock->details->sum(function($detail) {
            return $detail->quantity * $detail->unit_price;
        });

        $averageUnitPrice = $totalQuantity > 0 ? $totalAmount / $totalQuantity : 0;

        return [
            'id' => $stock->id,
            'type' => $stock->stock_type,
            'invoice_number' => null,
            'reference_number' => $stock->reference_no,
            'date' => $stock->date,
            'created_at' => $stock->created_at,
            'product_name' => $product ? $product->name : 'Multiple Products',
            'product_id' => $product ? $product->id : null,
            'customer_name' => $stock->stock_type === 'sale' ? $stock->party_name : null,
            'supplier_name' => $stock->stock_type === 'purchase' ? $stock->party_name : null,
            'quantity' => $totalQuantity,
            'unit' => $product ? $product->unit : 'pcs',
            'unit_price' => $averageUnitPrice,
            'total_amount' => $stock->net_price,
            'payment_status' => 'pending',
            'paid_amount' => $stock->paid_amount ?? 0,
            'party_phone' => $stock->party_phone,
            'party_address' => $stock->party_address,
            'description' => $stock->description,
            'items_count' => $stock->details->count(),
            'items' => $stock->details->map(function($detail) {
                return [
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->name ?? 'N/A',
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->unit_price,
                    'total' => $detail->quantity * $detail->unit_price,
                    'unit' => $detail->product->unit ?? 'pcs'
                ];
            })
        ];
    }

    /**
     * Get summary statistics
     */
    private function getSummaryStats(Request $request)
    {
        $summaryQuery = Stock::whereIn('stock_type', ['sale', 'purchase']);

        // Apply date filters
        if ($request->filled('start_date')) {
            $summaryQuery->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $summaryQuery->whereDate('date', '<=', $request->end_date);
        }

        // Apply type filter if exists
        if ($request->filled('type')) {
            $summaryQuery->where('stock_type', $request->type);
        }

        // Get sales summary
        $sales = $summaryQuery->clone()
            ->where('stock_type', 'sale')
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(net_price), 0) as total')
            )
            ->first();

        // Get purchases summary
        $purchases = $summaryQuery->clone()
            ->where('stock_type', 'purchase')
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(net_price), 0) as total')
            )
            ->first();

        // Calculate net profit and margin
        $totalSales = $sales->total ?? 0;
        $totalPurchases = $purchases->total ?? 0;
        $netProfit = $totalSales - $totalPurchases;
        $profitMargin = $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0;

        // Get top selling product
        $topSellingProduct = $this->getTopSellingProduct($request);

        return [
            'total_sales' => (float) $totalSales,
            'total_purchases' => (float) $totalPurchases,
            'net_profit' => (float) $netProfit,
            'profit_margin' => round($profitMargin, 2),
            'total_sales_count' => (int) ($sales->count ?? 0),
            'total_purchases_count' => (int) ($purchases->count ?? 0),
            'top_selling_product' => $topSellingProduct
        ];
    }

    /**
     * Get top selling product
     */
    private function getTopSellingProduct(Request $request)
    {
        $query = DB::table('stock_details')
            ->join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->join('products', 'stock_details.product_id', '=', 'products.id')
            ->where('stocks.stock_type', 'sale')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(stock_details.quantity) as total_sold'),
                DB::raw('SUM(stock_details.quantity * stock_details.unit_price) as total_value')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(1);

        // Apply date filters
        if ($request->filled('start_date')) {
            $query->whereDate('stocks.date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('stocks.date', '<=', $request->end_date);
        }

        $result = $query->first();

        if (!$result) {
            return null;
        }

        return [
            'id' => $result->id,
            'name' => $result->name,
            'total_sold' => (int) $result->total_sold,
            'total_value' => (float) $result->total_value
        ];
    }

    /**
     * Get chart data for visualization
     */
    private function getChartData(Request $request)
    {
        $chartData = [
            'time_series' => [
                'daily' => $this->getDailyTimeSeries($request),
                'weekly' => $this->getWeeklyTimeSeries($request),
                'monthly' => $this->getMonthlyTimeSeries($request),
                'yearly' => $this->getYearlyTimeSeries($request)
            ]
        ];

        return $chartData;
    }

    /**
     * Get daily time series data
     */
    private function getDailyTimeSeries(Request $request)
    {
        $query = Stock::whereIn('stock_type', ['sale', 'purchase'])
            ->select(
                DB::raw('DATE(date) as date'),
                'stock_type',
                DB::raw('SUM(net_price) as amount')
            )
            ->groupBy(DB::raw('DATE(date)'), 'stock_type')
            ->orderBy('date');

        // Apply date filters
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        } else {
            // Default to last 30 days
            $query->whereDate('date', '>=', now()->subDays(30));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Apply type filter if exists
        if ($request->filled('type')) {
            $query->where('stock_type', $request->type);
        }

        $results = $query->get();

        // Format for chart
        $labels = [];
        $sales = [];
        $purchases = [];

        // Get all dates in range
        $startDate = $request->filled('start_date')
            ? $request->start_date
            : now()->subDays(30)->toDateString();
        $endDate = $request->filled('end_date')
            ? $request->end_date
            : now()->toDateString();

        $dates = $this->getDateRange($startDate, $endDate);

        foreach ($dates as $date) {
            $labels[] = date('M d', strtotime($date));

            $saleAmount = $results->where('stock_type', 'sale')
                ->where('date', $date)
                ->first();
            $sales[] = $saleAmount ? (float) $saleAmount->amount : 0;

            $purchaseAmount = $results->where('stock_type', 'purchase')
                ->where('date', $date)
                ->first();
            $purchases[] = $purchaseAmount ? (float) $purchaseAmount->amount : 0;
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'purchases' => $purchases
        ];
    }

    /**
     * Get weekly time series data
     */
    private function getWeeklyTimeSeries(Request $request)
    {
        $query = Stock::whereIn('stock_type', ['sale', 'purchase'])
            ->select(
                DB::raw('YEAR(date) as year'),
                DB::raw('WEEK(date, 1) as week'),
                'stock_type',
                DB::raw('SUM(net_price) as amount')
            )
            ->groupBy(DB::raw('YEAR(date)'), DB::raw('WEEK(date, 1)'), 'stock_type')
            ->orderBy('year')
            ->orderBy('week');

        // Apply date filters
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        } else {
            // Default to last 12 weeks
            $query->whereDate('date', '>=', now()->subWeeks(12));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Apply type filter if exists
        if ($request->filled('type')) {
            $query->where('stock_type', $request->type);
        }

        $results = $query->get();

        // Format for chart
        $labels = [];
        $sales = [];
        $purchases = [];

        foreach ($results->groupBy(['year', 'week']) as $year => $weeks) {
            foreach ($weeks as $week => $data) {
                $labels[] = "Week $week, $year";

                $saleAmount = $data->where('stock_type', 'sale')->first();
                $sales[] = $saleAmount ? (float) $saleAmount->amount : 0;

                $purchaseAmount = $data->where('stock_type', 'purchase')->first();
                $purchases[] = $purchaseAmount ? (float) $purchaseAmount->amount : 0;
            }
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'purchases' => $purchases
        ];
    }

    /**
     * Get monthly time series data
     */
    private function getMonthlyTimeSeries(Request $request)
    {
        $query = Stock::whereIn('stock_type', ['sale', 'purchase'])
            ->select(
                DB::raw('YEAR(date) as year'),
                DB::raw('MONTH(date) as month'),
                'stock_type',
                DB::raw('SUM(net_price) as amount')
            )
            ->groupBy(DB::raw('YEAR(date)'), DB::raw('MONTH(date)'), 'stock_type')
            ->orderBy('year')
            ->orderBy('month');

        // Apply date filters
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        } else {
            // Default to last 12 months
            $query->whereDate('date', '>=', now()->subMonths(12));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Apply type filter if exists
        if ($request->filled('type')) {
            $query->where('stock_type', $request->type);
        }

        $results = $query->get();

        // Format for chart
        $labels = [];
        $sales = [];
        $purchases = [];

        foreach ($results->groupBy(['year', 'month']) as $year => $months) {
            foreach ($months as $month => $data) {
                $labels[] = date('M Y', mktime(0, 0, 0, $month, 1, $year));

                $saleAmount = $data->where('stock_type', 'sale')->first();
                $sales[] = $saleAmount ? (float) $saleAmount->amount : 0;

                $purchaseAmount = $data->where('stock_type', 'purchase')->first();
                $purchases[] = $purchaseAmount ? (float) $purchaseAmount->amount : 0;
            }
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'purchases' => $purchases
        ];
    }

    /**
     * Get yearly time series data
     */
    private function getYearlyTimeSeries(Request $request)
    {
        $query = Stock::whereIn('stock_type', ['sale', 'purchase'])
            ->select(
                DB::raw('YEAR(date) as year'),
                'stock_type',
                DB::raw('SUM(net_price) as amount')
            )
            ->groupBy(DB::raw('YEAR(date)'), 'stock_type')
            ->orderBy('year');

        // Apply date filters if exists
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Apply type filter if exists
        if ($request->filled('type')) {
            $query->where('stock_type', $request->type);
        }

        $results = $query->get();

        // Format for chart
        $labels = [];
        $sales = [];
        $purchases = [];

        foreach ($results->groupBy('year') as $year => $data) {
            $labels[] = $year;

            $saleAmount = $data->where('stock_type', 'sale')->first();
            $sales[] = $saleAmount ? (float) $saleAmount->amount : 0;

            $purchaseAmount = $data->where('stock_type', 'purchase')->first();
            $purchases[] = $purchaseAmount ? (float) $purchaseAmount->amount : 0;
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'purchases' => $purchases
        ];
    }

    /**
     * Get date range between two dates
     */
    private function getDateRange($startDate, $endDate)
    {
        $dates = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);

        while ($current <= $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        return $dates;
    }

    /**
     * Export report to Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            // Validate export request
            $request->validate([
                'type' => 'nullable|in:sale,purchase',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'payment_status' => 'nullable|string'
            ]);

            // $export = new TransactionReportExport($request->all());

            $filename = 'transaction-report-' . date('Y-m-d') . '.xlsx';

            return null;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to PDF
     */
    public function exportPDF(Request $request)
    {
        try {
            // Validate export request
            $request->validate([
                'type' => 'nullable|in:sale,purchase',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'payment_status' => 'nullable|string'
            ]);

            // Get data for PDF
            $data = [];

            if ($request->filled('type')) {
                $data['type'] = $request->type;
            }
            if ($request->filled('start_date')) {
                $data['start_date'] = $request->start_date;
            }
            if ($request->filled('end_date')) {
                $data['end_date'] = $request->end_date;
            }

            // Get transactions and summary
            $transactions = $this->getTransactionsForExport($request);
            $summary = $this->getSummaryStats($request);

            // $pdf = Pdf::loadView('exports.transaction-report', [
            //     'transactions' => $transactions,
            //     'summary' => $summary,
            //     'filters' => $data
            // ]);

            $filename = 'transaction-report-' . date('Y-m-d') . '.pdf';

            // return $pdf->download($filename);
            return null;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions for export (without pagination)
     */
    private function getTransactionsForExport(Request $request)
    {
        $query = Stock::with(['details.product'])
            ->whereIn('stock_type', ['sale', 'purchase'])
            ->orderBy('date', 'desc');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('stock_type', $request->type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $transactions = $query->get();

        return $transactions->map(function($stock) {
            return $this->formatTransaction($stock);
        });
    }

    /**
     * Print single transaction
     */
    public function printTransaction($id)
    {
        try {
            $stock = Stock::with(['details.product', 'user'])->findOrFail($id);

            $pdf = Pdf::loadView('prints.transaction', [
                'transaction' => $this->formatTransaction($stock),
                'stock' => $stock
            ]);

            $filename = 'transaction-' . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate print',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
