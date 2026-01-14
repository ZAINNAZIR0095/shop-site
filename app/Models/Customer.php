<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'cnic',
        'notes',
        'opening_balance',
        'current_balance',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2'
    ];

    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class)->orderBy('date', 'desc');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'party_name', 'name')->where('stock_type', 'sale');
    }

    public function updateBalance()
    {
        $totalDebit = $this->transactions()->sum('debit');
        $totalCredit = $this->transactions()->sum('credit');
        $this->current_balance = ($this->opening_balance + $totalDebit) - $totalCredit;
        $this->save();
    }
}
