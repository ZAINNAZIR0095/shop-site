<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'date',
        'type',
        'reference_no',
        'description',
        'debit',
        'credit',
        'balance',
        'stock_id',
        'payment_id'
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function payment()
    {
        // return $this->belongsTo(Payment::class);
        return null;
    }
}
