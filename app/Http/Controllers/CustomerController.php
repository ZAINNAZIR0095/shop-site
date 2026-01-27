<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('transactions')
            ->withSum('transactions as total_debit', 'debit')
            ->withSum('transactions as total_credit', 'credit');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
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
        $customers = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:customers,name',
            'phone' => 'nullable|string|max:20|unique:customers,phone',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'address' => 'nullable|string',
            'cnic' => 'nullable|string|max:15|unique:customers,cnic',
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
            $customer = Customer::create([
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

            if ($customer->opening_balance > 0) {
                CustomerTransaction::create([
                    'customer_id' => $customer->id,
                    'date' => now(),
                    'type' => 'opening_balance',
                    'description' => 'Opening Balance',
                    'debit' => $customer->opening_balance,
                    'balance' => $customer->opening_balance
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => $customer->loadCount('transactions')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $customer = Customer::with(['transactions' => function($query) {
                $query->orderBy('date', 'desc')->orderBy('id', 'desc');
            }])->findOrFail($id);

            $balanceSummary = [
                'opening_balance' => $customer->opening_balance,
                'total_debit' => $customer->transactions->sum('debit'),
                'total_credit' => $customer->transactions->sum('credit'),
                'current_balance' => $customer->current_balance
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => $customer,
                    'balance_summary' => $balanceSummary
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:customers,name,' . $id,
            'phone' => 'nullable|string|max:20|unique:customers,phone,' . $id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
            'address' => 'nullable|string',
            'cnic' => 'nullable|string|max:15|unique:customers,cnic,' . $id,
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'opening_balance' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $oldOpening = $customer->opening_balance ?? 0;
            $newOpening = $request->has('opening_balance') ? (float) $request->opening_balance : $oldOpening;
            $delta = $newOpening - $oldOpening;

            $customer->name = $request->name;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->address = $request->address;
            $customer->cnic = $request->cnic;
            $customer->notes = $request->notes;
            $customer->is_active = $request->boolean('is_active', $customer->is_active);

            if ($request->has('opening_balance')) {
                $customer->opening_balance = $newOpening;
                $customer->current_balance = ($customer->current_balance ?? 0) + $delta;
            }

            $customer->save();

            if ($request->has('opening_balance')) {
                $openingTx = CustomerTransaction::where('customer_id', $customer->id)
                    ->where('type', 'opening_balance')
                    ->first();

                if ($openingTx) {
                    $openingTx->debit = $newOpening;
                    $openingTx->balance = ($openingTx->balance ?? 0) + $delta;
                    $openingTx->save();
                } elseif ($newOpening > 0) {
                    CustomerTransaction::create([
                        'customer_id' => $customer->id,
                        'date' => now(),
                        'type' => 'opening_balance',
                        'description' => 'Opening Balance',
                        'debit' => $newOpening,
                        'balance' => $newOpening
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);

            if ($customer->transactions()->exists() || $customer->stocks()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete customer with transaction history'
                ], 400);
            }

            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer'
            ], 500);
        }
    }

    public function getTransactions($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $transactions = $customer->transactions()
                ->with(['stock' => function($query) {
                    $query->select('id', 'reference_no', 'net_price', 'date');
                }])
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(request()->get('per_page', 20));

            $runningBalance = $customer->opening_balance;
            $transactions->getCollection()->transform(function ($transaction) use (&$runningBalance) {
                $runningBalance += ($transaction->debit - $transaction->credit);
                $transaction->running_balance = $runningBalance;
                return $transaction;
            });

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
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
            $customer = Customer::findOrFail($id);

            // Create payment record
            $payment = Payment::create([
                'customer_id' => $customer->id,
                'date' => $request->date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_no' => $request->reference_no,
                'notes' => $request->notes,
                'received_by' => auth()->id()
            ]);

            // Create transaction record
            $transaction = CustomerTransaction::create([
                'customer_id' => $customer->id,
                'date' => $request->date,
                'type' => 'payment',
                'reference_no' => $request->reference_no ?? 'PAY-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                'description' => 'Payment - ' . ucfirst(str_replace('_', ' ', $request->payment_method)),
                'credit' => $request->amount,
                'balance' => $customer->current_balance - $request->amount,
                'payment_id' => $payment->id
            ]);

            // Update customer balance
            $customer->current_balance -= $request->amount;
            $customer->save();

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

    public function updatePayment(Request $request, $paymentId)
{
    $validator = Validator::make($request->all(), [
        'date'            => 'required|date',
        'amount'          => 'required|numeric|min:1',
        'payment_method'  => 'required|string|in:cash,bank_transfer,cheque,other',
        'reference_no'    => 'nullable|string|max:100',
        'notes'           => 'nullable|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    DB::beginTransaction();

    try {
        $payment = Payment::findOrFail($paymentId);
        $customer = $payment->customer;

        // Get old amount (to adjust balance correctly)
        $oldAmount = $payment->amount;

        // Update payment record
        $payment->update([
            'date'            => $request->date,
            'amount'          => $request->amount,
            'payment_method'  => $request->payment_method,
            'reference_no'    => $request->reference_no,
            'notes'           => $request->notes,
        ]);

        // Update related transaction
        $transaction = CustomerTransaction::where('payment_id', $payment->id)->firstOrFail();

        $transaction->update([
            'date'          => $request->date,
            'reference_no'  => $request->reference_no ?? 'PAY-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            'description'   => 'Payment - ' . ucfirst(str_replace('_', ' ', $request->payment_method)),
            'credit'        => $request->amount,
            'balance'       => $customer->current_balance - $request->amount + $oldAmount, // adjust for old amount
            'payment_method'=> $request->payment_method,
            'notes'         => $request->notes,
        ]);

        // Adjust customer balance: remove old credit, add new credit
        $customer->current_balance = $customer->current_balance + $oldAmount - $request->amount;
        $customer->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data'    => $payment
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to update payment',
            'error'   => $e->getMessage()
        ], 500);
    }
}
    public function getBalance($id)
    {
        try {
            $customer = Customer::findOrFail($id);

            $summary = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'opening_balance' => $customer->opening_balance,
                'total_sales' => $customer->transactions()->where('type', 'sale')->sum('debit'),
                'total_payments' => $customer->transactions()->where('type', 'payment')->sum('credit'),
                'current_balance' => $customer->current_balance,
                'last_transaction_date' => $customer->transactions()->latest()->first()->date ?? null,
                'total_transactions' => $customer->transactions()->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
    }

    public function search(Request $request)
    {
        $query = Customer::where('is_active', true);

        if ($request->has('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone', 'current_balance']);

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }
}
