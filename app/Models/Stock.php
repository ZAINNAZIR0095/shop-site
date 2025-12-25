<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'description',
        'net_price',
        'stock_type',
        'party_name',
        'party_phone',
        'party_address' 
    ];

    protected $casts = [
        'date' => 'date',
        'net_price' => 'decimal:2'
    ];

    // Relationships
    public function details(): HasMany
    {
        return $this->hasMany(StockDetail::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'stock_details')
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }

    // Scopes
    public function scopeSales($query)
    {
        return $query->where('stock_type', 'sale');
    }

    public function scopePurchases($query)
    {
        return $query->where('stock_type', 'purchase');
    }

    public function scopeIssues($query)
    {
        return $query->where('stock_type', 'issue');
    }

    public function scopeReturns($query)
    {
        return $query->where('stock_type', 'return');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', now()->month)
                     ->whereYear('date', now()->year);
    }

    // Calculate net price on the fly
    public function calculateNetPrice()
    {
        return $this->details->sum(function($detail) {
            return $detail->quantity * $detail->unit_price;
        });
    }

    // Get net price attribute
    public function getNetPriceAttribute($value)
    {
        // If net_price is 0 or not set, calculate it
        if ($value == 0 && $this->details()->exists()) {
            return $this->calculateNetPrice();
        }
        return $value;
    }

    // Get party type
    public function getPartyTypeAttribute()
    {
        return $this->stock_type === 'sale' ? 'Customer' : 'Supplier';
    }

    // Get items count
    public function getItemsCountAttribute()
    {
        return $this->details()->count();
    }

    // Get total quantity
    public function getTotalQuantityAttribute()
    {
        return $this->details()->sum('quantity');
    }
}
