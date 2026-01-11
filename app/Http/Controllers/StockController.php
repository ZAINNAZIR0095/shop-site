<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::with(['user', 'details.product']);
$query->orderByDesc('id');

        if ($request->has('stock_type')) {
            $query->where('stock_type', $request->stock_type);
        }

        if ($request->has('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->has('party_name')) {
            $query->where('party_name', 'like', '%' . $request->party_name . '%');
        }

        if ($request->has('product_id')) {
            $query->whereHas('details', function($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        $stocks = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $stocks
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'description' => 'nullable|string',
            'stock_type' => 'required|in:sale,purchase,issue,return',
            'party_name' => 'nullable|string|max:255',
            'party_phone' => 'nullable|string|max:20',
            'party_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create stock record
            $stock = Stock::create([
                'user_id' => auth()->id(),
                'date' => $request->date,
                'description' => $request->description,
                'stock_type' => $request->stock_type,
                'party_name' => $request->party_name,
                'party_phone' => $request->party_phone,
                'party_address' => $request->party_address
            ]);

            $netPrice = 0;

            // Create stock details
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                if (!$product) {
                    throw new \Exception("Product not found: " . $item['product_id']);
                }

                // Determine unit price based on stock type
                $unitPrice = $request->stock_type === 'sale'
                    ? $product->sale_price
                    : $product->purchase_price;

                $itemTotal = $item['quantity'] * $unitPrice;
                $netPrice += $itemTotal;

                // Create stock detail
                StockDetail::create([
                    'stock_id' => $stock->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice
                ]);
            }

            // Update net price
            $stock->net_price = $netPrice;
            $stock->save();

            // Load relationships for response
            $stock->load(['details.product', 'user']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst($request->stock_type) . ' created successfully',
                'data' => $stock
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function formatStockResponse($stock)
{
    return [
        'id' => $stock->id,
        'reference_no' => $stock->reference_no ?? null,
        'stock_type' => $stock->stock_type,
        'date' => $stock->date,
        'description' => $stock->description,
        'party_name' => $stock->party_name,
        'party_phone' => $stock->party_phone,
        'party_address' => $stock->party_address,
        'total_items' => $stock->details->count(),
        'net_price' => $stock->net_price,
        'created_at' => $stock->created_at,
        'updated_at' => $stock->updated_at,
        'items' => $stock->details->map(function ($detail) {
            return [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'unit_price' => $detail->unit_price,
                'total_price' => $detail->total_price,
                'product' => $detail->product ? [
                    'id' => $detail->product->id,
                    'name' => $detail->product->name,
                    'unit' => $detail->product->unit,
                    'sale_price' => $detail->product->sale_price,
                    'purchase_price' => $detail->product->purchase_price,
                ] : null
            ];
        })
    ];
}


public function show($id)
{
    try {
        $stock = Stock::with(['details', 'details.product'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatStockResponse($stock)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Stock entry not found.',
            'error' => $e->getMessage()
        ], 404);
    }
}

public function edit($id)
{
    try {
        $stock = Stock::with(['details', 'details.product'])->findOrFail($id);

        return view('stocks.edit', compact('stock'));

    } catch (\Exception $e) {
        return redirect()->route('stocks.index')
            ->with('error', 'Stock entry not found.');
    }
}

    public function update(Request $request, Stock $stock)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date',
            'description' => 'nullable|string',
            'party_name' => 'nullable|string|max:255',
            'party_phone' => 'nullable|string|max:20',
            'party_address' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Delete old details
            $stock->details()->delete();

            // Update stock info
            $stock->update([
                'date' => $request->date ?? $stock->date,
                'description' => $request->description ?? $stock->description,
                'party_name' => $request->party_name ?? $stock->party_name,
                'party_phone' => $request->party_phone ?? $stock->party_phone,
                'party_address' => $request->party_address ?? $stock->party_address
            ]);

            $netPrice = 0;

            // Create new stock details
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);

                    // Determine unit price
                    $unitPrice = $stock->stock_type === 'sale'
                        ? $product->sale_price
                        : $product->purchase_price;

                    $itemTotal = $item['quantity'] * $unitPrice;
                    $netPrice += $itemTotal;

                    // Create stock detail
                    StockDetail::create([
                        'stock_id' => $stock->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice
                    ]);
                }
            }

            // Update net price
            $stock->net_price = $netPrice;
            $stock->save();

            // Load relationships
            $stock->load(['details.product', 'user']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock record updated successfully',
                'data' => $stock
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Stock $stock)
    {
        DB::beginTransaction();

        try {
            $stock->details()->delete();
            $stock->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock record deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stock record'
            ], 500);
        }
    }

    // Get unique parties
    public function getParties(Request $request)
    {
        $query = Stock::select('party_name', 'party_phone')
            ->whereNotNull('party_name')
            ->where('party_name', '!=', '')
            ->groupBy('party_name', 'party_phone');

        if ($request->has('stock_type')) {
            $query->where('stock_type', $request->stock_type);
        }

        if ($request->has('search')) {
            $query->where('party_name', 'like', '%' . $request->search . '%');
        }

        $parties = $query->orderBy('party_name')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parties
        ]);
    }
}
