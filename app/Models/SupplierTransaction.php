<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierTransaction extends Model
{
    use HasFactory;

    protected $table = 'supplier_transactions';

    protected $fillable = [
        'supplier_id',
        'date',
        'type',
        'reference_no',
        'description',
        'debit',
        'credit',
        'balance',
        'stock_id',
        'payment_id',
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * Relationships
     */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Scopes (Optional but useful)
     */

    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    public function scopePayments($query)
    {
        return $query->where('type', 'payment');
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }
}
