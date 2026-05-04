<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Payment;
use App\Models\Stock;
use App\Models\StockDetail;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
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
            $query->whereHas('details', function ($q) use ($request) {
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
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.unit_price' => 'nullable|numeric|min:0',

        // Sale receive payment
        'receive_payment' => 'nullable|array',
        'receive_payment.amount' => 'nullable|numeric|min:0',
        'receive_payment.payment_method' => 'nullable|in:cash,bank_transfer,cheque,other',
        'receive_payment.reference_no' => 'nullable|string|max:100',
        'receive_payment.notes' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    DB::beginTransaction();

    try {

        /** 1️⃣ Create stock */
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

        /** 2️⃣ Stock details */
        foreach ($request->items as $item) {

            $product = Product::findOrFail($item['product_id']);

            $unitPrice = isset($item['unit_price']) && is_numeric($item['unit_price'])
                ? $item['unit_price']
                : ($request->stock_type === 'sale' ? $product->sale_price : $product->purchase_price);

            $itemTotal = $item['quantity'] * $unitPrice;
            $netPrice += $itemTotal;

            StockDetail::create([
                'stock_id' => $stock->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice
            ]);
        }

        $stock->update(['net_price' => $netPrice]);

        /** ───────────── SALE ───────────── */
        if ($request->stock_type === 'sale' && $request->party_name) {

            $customer = Customer::firstOrCreate(
                ['name' => $request->party_name],
                [
                    'phone' => $request->party_phone,
                    'address' => $request->party_address,
                    'current_balance' => 0
                ]
            );

            /** Sale transaction (customer owes us) */
            $newBalance = $customer->current_balance + $netPrice;

            CustomerTransaction::create([
                'customer_id' => $customer->id,
                'date' => $request->date,
                'type' => 'sale',
                'reference_no' => 'SAL-' . str_pad($stock->id, 6, '0', STR_PAD_LEFT),
                'description' => 'Sale Transaction',
                'debit' => $netPrice,
                'credit' => 0,
                'balance' => $newBalance,
                'stock_id' => $stock->id
            ]);

            $customer->current_balance = $newBalance;

            /** Receive payment (if any) */
            $received = $request->receive_payment['amount'] ?? 0;

            if ($received > 0) {

                $payment = Payment::create([
                    'customer_id' => $customer->id,
                    'date' => $request->date,
                    'amount' => $received,
                    'payment_method' => $request->receive_payment['payment_method'] ?? 'cash',
                    'reference_no' => $request->receive_payment['reference_no'],
                    'notes' => $request->receive_payment['notes'],
                    'received_by' => auth()->id()
                ]);

                $newBalance -= $received;

                CustomerTransaction::create([
                    'customer_id' => $customer->id,
                    'date' => $request->date,
                    'type' => 'payment',
                    'reference_no' => $request->receive_payment['reference_no']
                        ?? 'PAY-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                    'description' => 'Payment Received',
                    'debit' => 0,
                    'credit' => $received,
                    'balance' => $newBalance,
                    'payment_id' => $payment->id,
                    'stock_id' => $stock->id
                ]);

                $customer->current_balance = $newBalance;
            }

            $customer->save();
        }

        /** ───────────── PURCHASE ───────────── */
        if ($request->stock_type === 'purchase' && $request->party_name) {

            $supplier = Supplier::firstOrCreate(
                ['name' => $request->party_name],
                [
                    'phone' => $request->party_phone,
                    'address' => $request->party_address,
                    'current_balance' => 0
                ]
            );

            $newBalance = $supplier->current_balance + $netPrice;

            SupplierTransaction::create([
                'supplier_id' => $supplier->id,
                'date' => $request->date,
                'type' => 'purchase',
                'reference_no' => 'PUR-' . str_pad($stock->id, 6, '0', STR_PAD_LEFT),
                'description' => 'Purchase Transaction',
                'debit' => 0,
                'credit' => $netPrice,
                'balance' => $newBalance,
                'stock_id' => $stock->id
            ]);

            $supplier->current_balance = $newBalance;
            $supplier->save();
        }

        DB::commit();

        $stock->load(['details.product', 'user']);

        return response()->json([
            'success' => true,
            'supplier_id' => $supplier->id ?? null,
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
        'date'          => 'sometimes|date',
        'description'   => 'nullable|string',
        'party_name'    => 'nullable|string|max:255',
        'party_phone'   => 'nullable|string|max:20',
        'party_address' => 'nullable|string',
        'stock_type'    => 'sometimes|in:sale,purchase,issue,return',
        'items'         => 'sometimes|array',
        'items.*.product_id' => 'required_with:items|exists:products,id',
        'items.*.quantity'   => 'required_with:items|integer|min:1',
        'items.*.unit_price' => 'nullable|numeric|min:0',

        // Optional: allow updating / removing receive_payment
        'receive_payment' => 'nullable|array',
        'receive_payment.amount'        => 'nullable|numeric|min:0',
        'receive_payment.payment_method' => 'nullable|in:cash,bank_transfer,cheque,other',
        'receive_payment.reference_no'  => 'nullable|string|max:100',
        'receive_payment.notes'         => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    DB::beginTransaction();

    try {
        // ─── 1. Revert old financial impact ────────────────────────────────────────

        $oldNet       = $stock->net_price;
        $oldType      = $stock->stock_type;
        $oldPartyName = $stock->party_name;

        // Revert customer side (sale)
        if ($oldType === 'sale' && $oldPartyName) {
            $oldSaleTx = CustomerTransaction::where('stock_id', $stock->id)
                ->where('type', 'sale')
                ->first();

            if ($oldSaleTx) {
                $customer = Customer::find($oldSaleTx->customer_id);
                if ($customer) {
                    $customer->current_balance -= $oldNet;
                    $customer->save();
                }
            }

            // Delete old sale + payment transactions & payment record
            $oldPaymentTx = CustomerTransaction::where('stock_id', $stock->id)
                ->where('type', 'payment')
                ->first();

            if ($oldPaymentTx && $oldPaymentTx->payment_id) {
                Payment::where('id', $oldPaymentTx->payment_id)->delete();
            }

            CustomerTransaction::where('stock_id', $stock->id)->delete();
        }

        // Revert supplier side (purchase)
        if ($oldType === 'purchase' && $oldPartyName) {
            $oldPurchaseTx = SupplierTransaction::where('stock_id', $stock->id)
                ->where('type', 'purchase')
                ->first();

            if ($oldPurchaseTx) {
                $supplier = Supplier::find($oldPurchaseTx->supplier_id);
                if ($supplier) {
                    $supplier->current_balance -= $oldNet;
                    $supplier->save();
                }
            }

            SupplierTransaction::where('stock_id', $stock->id)->delete();
        }

        // ─── 2. Delete old details ─────────────────────────────────────────────────
        $stock->details()->delete();

        // ─── 3. Update core stock fields ───────────────────────────────────────────
        $stock->update([
            'date'          => $request->date          ?? $stock->date,
            'description'   => $request->description   ?? $stock->description,
            'party_name'    => $request->party_name    ?? $stock->party_name,
            'party_phone'   => $request->party_phone   ?? $stock->party_phone,
            'party_address' => $request->party_address ?? $stock->party_address,
            'stock_type'    => $request->stock_type    ?? $stock->stock_type,
        ]);

        // ─── 4. Recalculate net price & create new details ─────────────────────────
        $netPrice = 0;

        if ($request->has('items') && count($request->items) > 0) {
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                $unitPrice = isset($item['unit_price']) && is_numeric($item['unit_price'])
                    ? $item['unit_price']
                    : ($stock->stock_type === 'sale' ? $product->sale_price : $product->purchase_price);

                $itemTotal = $item['quantity'] * $unitPrice;
                $netPrice += $itemTotal;

                StockDetail::create([
                    'stock_id'   => $stock->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $unitPrice,
                ]);
            }
        }
        // If no items sent → keep old net price (or set to 0 if you prefer)

        $stock->update(['net_price' => $netPrice]);

        // ─── 5. Recreate party + transactions (just like in store) ─────────────────

        // SALE
        if ($stock->stock_type === 'sale' && $stock->party_name) {
            $customer = Customer::firstOrCreate(
                ['name' => $stock->party_name],
                [
                    'phone'           => $stock->party_phone,
                    'address'         => $stock->party_address,
                    'current_balance' => 0,
                ]
            );

            $newBalance = $customer->current_balance + $netPrice;

            CustomerTransaction::create([
                'customer_id'   => $customer->id,
                'date'          => $stock->date,
                'type'          => 'sale',
                'reference_no'  => 'SAL-' . str_pad($stock->id, 6, '0', STR_PAD_LEFT),
                'description'   => 'Sale Transaction',
                'debit'         => $netPrice,
                'credit'        => 0,
                'balance'       => $newBalance,
                'stock_id'      => $stock->id,
            ]);

            $customer->current_balance = $newBalance;

            // Handle receive_payment (allow update / new / remove)
            $received = $request->receive_payment['amount'] ?? 0;

            if ($received > 0) {
                $payment = Payment::create([
                    'customer_id'    => $customer->id,
                    'date'           => $stock->date,
                    'amount'         => $received,
                    'payment_method' => $request->receive_payment['payment_method'] ?? 'cash',
                    'reference_no'   => $request->receive_payment['reference_no'] ?? null,
                    'notes'          => $request->receive_payment['notes'] ?? null,
                    'received_by'    => auth()->id(),
                ]);

                $newBalance -= $received;

                CustomerTransaction::create([
                    'customer_id'   => $customer->id,
                    'date'          => $stock->date,
                    'type'          => 'payment',
                    'reference_no'  => $request->receive_payment['reference_no']
                        ?? 'PAY-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                    'description'   => 'Payment Received',
                    'debit'         => 0,
                    'credit'        => $received,
                    'balance'       => $newBalance,
                    'payment_id'    => $payment->id,
                    'stock_id'      => $stock->id,
                ]);

                $customer->current_balance = $newBalance;
            }

            $customer->save();
        }

        // PURCHASE
        if ($stock->stock_type === 'purchase' && $stock->party_name) {
            $supplier = Supplier::firstOrCreate(
                ['name' => $stock->party_name],
                [
                    'phone'           => $stock->party_phone,
                    'address'         => $stock->party_address,
                    'current_balance' => 0,
                ]
            );

            $newBalance = $supplier->current_balance + $netPrice;

            SupplierTransaction::create([
                'supplier_id'  => $supplier->id,
                'date'         => $stock->date,
                'type'         => 'purchase',
                'reference_no' => 'PUR-' . str_pad($stock->id, 6, '0', STR_PAD_LEFT),
                'description'  => 'Purchase Transaction',
                'debit'        => 0,
                'credit'       => $netPrice,
                'balance'      => $newBalance,
                'stock_id'     => $stock->id,
            ]);

            $supplier->current_balance = $newBalance;
            $supplier->save();
        }

        // ─── 6. Finalize ───────────────────────────────────────────────────────────
        $stock->load(['details.product', 'user']);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Stock record updated successfully',
            'data'    => $stock
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to update stock record',
            'error'   => $e->getMessage(),
            // 'trace'   => $e->getTraceAsString()   // ← uncomment only in dev
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
