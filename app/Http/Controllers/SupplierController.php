<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierTransaction;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::withCount('transactions')
            ->withSum('transactions as total_debit', 'debit')
            ->withSum('transactions as total_credit', 'credit');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('cnic', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('balance') && $request->balance !== 'all') {
            if ($request->balance === 'positive') {
                $query->where('current_balance', '>', 0);
            } elseif ($request->balance === 'negative') {
                $query->where('current_balance', '<', 0);
            } elseif ($request->balance === 'zero') {
                $query->where('current_balance', 0);
            }
        }

        $perPage = $request->get('per_page', 20);
        $suppliers = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:suppliers,name',
            'phone' => 'nullable|string|max:20|unique:suppliers,phone',
            'email' => 'nullable|email|max:255|unique:suppliers,email',
            'address' => 'nullable|string',
            'cnic' => 'nullable|string|max:15|unique:suppliers,cnic',
            'notes' => 'nullable|string',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $supplier = Supplier::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'cnic' => $request->cnic,
                'notes' => $request->notes,
                'opening_balance' => $request->opening_balance ?? 0,
                'current_balance' => $request->opening_balance ?? 0,
                'is_active' => $request->boolean('is_active', true)
            ]);

            if ($supplier->opening_balance > 0) {
                SupplierTransaction::create([
                    'supplier_id' => $supplier->id,
                    'date' => now(),
                    'type' => 'opening_balance',
                    'description' => 'Opening Balance',
                    'credit' => $supplier->opening_balance, // Supplier opening balance is credit (they owe us)
                    'balance' => $supplier->opening_balance
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully',
                'data' => $supplier->loadCount('transactions')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $supplier = Supplier::with([
                'transactions' => function ($query) {
                    $query->orderBy('date', 'desc')->orderBy('id', 'desc');
                }
            ])->findOrFail($id);

            $balanceSummary = [
                'opening_balance' => $supplier->opening_balance,
                'total_debit' => $supplier->transactions->sum('debit'),
                'total_credit' => $supplier->transactions->sum('credit'),
                'current_balance' => $supplier->current_balance
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'supplier' => $supplier,
                    'balance_summary' => $balanceSummary
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }
    }

  public function update(Request $request, $id)
{
    $supplier = Supplier::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'name'              => 'required|string|max:255|unique:suppliers,name,' . $id,
        'phone'             => 'nullable|string|max:20|unique:suppliers,phone,' . $id,
        'email'             => 'nullable|email|max:255|unique:suppliers,email,' . $id,
        'address'           => 'nullable|string',
        'cnic'              => 'nullable|string|max:15|unique:suppliers,cnic,' . $id,
        'notes'             => 'nullable|string',
        'opening_balance'   => 'required|numeric',
        'is_active'         => 'boolean'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    DB::beginTransaction();

    try {
        // Get old values
        $oldOpeningBalance = $supplier->opening_balance;
        $newOpeningBalance = $request->opening_balance;

        // Calculate difference
        $difference = $newOpeningBalance - $oldOpeningBalance;

        // Update supplier main data
        $supplier->update([
            'name'            => $request->name,
            'phone'           => $request->phone,
            'email'           => $request->email,
            'address'         => $request->address,
            'cnic'            => $request->cnic,
            'notes'           => $request->notes,
            'opening_balance' => $newOpeningBalance,
            'is_active'       => $request->boolean('is_active', $supplier->is_active)
        ]);

        // Always update current_balance with the difference
        $supplier->current_balance += $difference;
        $supplier->save();

        // ──────────────────────────────────────────────────────────────
        // Find and update the existing "opening_balance" transaction
        // ──────────────────────────────────────────────────────────────
        $openingTransaction = SupplierTransaction::where('supplier_id', $supplier->id)
            ->where('type', 'opening_balance')
            ->first();

        if ($openingTransaction) {
            // Update existing opening balance transaction
            $openingTransaction->update([
                'date'        => now(), // or keep original date if you prefer
                'credit'      => $newOpeningBalance, // since opening balance is always credit
                'balance'     => $supplier->current_balance,
                'description' => 'Opening Balance (Updated: ' . number_format($oldOpeningBalance, 2) . ' → ' . number_format($newOpeningBalance, 2) . ')',
            ]);
        } else {
            // If somehow no opening transaction exists (rare case), create one
            SupplierTransaction::create([
                'supplier_id'   => $supplier->id,
                'date'          => now(),
                'type'          => 'opening_balance',
                'description'   => 'Opening Balance (Created during update)',
                'credit'        => $newOpeningBalance,
                'balance'       => $supplier->current_balance,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data'    => $supplier->fresh()->loadCount('transactions')
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to update supplier',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            if ($supplier->transactions()->exists() || $supplier->stocks()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete supplier with transaction history'
                ], 400);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier'
            ], 500);
        }
    }

    public function getTransactions($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $transactions = $supplier->transactions()
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(request()->get('per_page', 20));

            $runningBalance = $supplier->opening_balance;
            $transactions->getCollection()->transform(function ($transaction) use (&$runningBalance) {
                $runningBalance += ($transaction->debit - $transaction->credit);
                $transaction->running_balance = $runningBalance;
                return $transaction;
            });

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'id' => $id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }
    }

    public function addPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,bank_transfer,cheque,other',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $supplier = Supplier::findOrFail($id);

            // Create supplier payment record
            $payment = SupplierPayment::create([
                'supplier_id' => $supplier->id, // Use supplier_id instead of customer_id
                'date' => $request->date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_no' => $request->reference_no,
                'notes' => $request->notes,
                'received_by' => auth()->id()
            ]);

            // Create transaction record (payment to supplier)
            $transaction = SupplierTransaction::create([
                'supplier_id' => $supplier->id,
                'date' => $request->date,
                'type' => 'payment',
                'reference_no' => $request->reference_no ?? 'PAY-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                'description' => 'Payment - ' . ucfirst(str_replace('_', ' ', $request->payment_method)),
                'debit' => $request->amount, // Debit reduces supplier balance (we pay them)
                'balance' => $supplier->current_balance - $request->amount,
                'payment_id' => $payment->id
            ]);

            // Update supplier balance (paying reduces what we owe them)
            $supplier->current_balance -= $request->amount;
            $supplier->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => [
                    'payment' => $payment,
                    'transaction' => $transaction
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBalance($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            $summary = [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'opening_balance' => $supplier->opening_balance,
                'total_purchases' => $supplier->transactions()->where('type', 'purchase')->sum('credit'),
                'total_payments' => $supplier->transactions()->where('type', 'payment')->sum('debit'),
                'current_balance' => $supplier->current_balance,
                'last_transaction_date' => $supplier->transactions()->latest()->first()->date ?? null,
                'total_transactions' => $supplier->transactions()->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }
    }

    public function search(Request $request)
    {
        $query = Supplier::where('is_active', true);

        if ($request->has('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone', 'current_balance']);

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    public function updateBalance(Request $request, $id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $amount = $request->amount;
            $type = $request->type; // 'purchase' or 'payment'

            if ($type === 'purchase') {
                // Purchase increases what we owe supplier (credit)
                $supplier->current_balance += $amount;
            } elseif ($type === 'payment') {
                // Payment reduces what we owe supplier (debit)
                $supplier->current_balance -= $amount;
            }

            $supplier->save();

            return response()->json([
                'success' => true,
                'message' => 'Balance updated successfully',
                'data' => ['current_balance' => $supplier->current_balance]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePayment(Request $request, $paymentId)
{
    $validator = Validator::make($request->all(), [
        'date' => 'required|date',
        'amount' => 'required|numeric|min:1',
        'payment_method' => 'required|string|in:cash,bank_transfer,cheque,other',
        'reference_no' => 'nullable|string|max:100',
        'notes' => 'nullable|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }
    $transaction = SupplierTransaction::where('payment_id', $paymentId)->first();

if (!$transaction) {
    return response()->json([
        'success' => false,
        'message' => 'No transaction found for this payment. The link may be missing. '.$paymentId
    ], 404);
}

    DB::beginTransaction();

    try {
        $payment = SupplierPayment::findOrFail($paymentId);
        $transaction = SupplierTransaction::where('payment_id', $payment->id)->firstOrFail();
        $supplier = Supplier::findOrFail($payment->supplier_id);

        $oldAmount = $payment->amount;
        $newAmount = $request->amount;
        $difference = $newAmount - $oldAmount;

        // 1️⃣ Reverse old payment effect
        $supplier->current_balance += $oldAmount;

        // 2️⃣ Apply new payment effect
        $supplier->current_balance -= $newAmount;
        $supplier->save();

        // 3️⃣ Update payment record
        $payment->update([
            'date' => $request->date,
            'amount' => $newAmount,
            'payment_method' => $request->payment_method,
            'reference_no' => $request->reference_no,
            'notes' => $request->notes,
        ]);

        // 4️⃣ Update transaction record
        $transaction->update([
            'date' => $request->date,
            'debit' => $newAmount,
            'reference_no' => $request->reference_no ?? $transaction->reference_no,
            'description' => 'Payment - ' . ucfirst(str_replace('_', ' ', $request->payment_method)),
            'balance' => $supplier->current_balance
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to update payment',
            'error' => $e->getMessage()
        ], 500);
    }
}


}
