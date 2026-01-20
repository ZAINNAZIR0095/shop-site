<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'cnic',
        'notes',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * ======================
     * Relationships
     * ======================
     */

    public function transactions()
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * ======================
     * Query Scopes
     * ======================
     */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * ======================
     * Helper Methods
     * ======================
     */

    public function recalculateBalance(): void
    {
        $balance = $this->opening_balance;

        $this->transactions()
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->each(function ($transaction) use (&$balance) {
                // Credit = Purchase (increases what we owe supplier)
                // Debit = Payment (decreases what we owe supplier)
                $balance += $transaction->credit - $transaction->debit;
            });

        $this->current_balance = $balance;
        $this->save();
    }
}
