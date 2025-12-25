<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'unit',
        'size',
        'min_limit',
        'sale_price',
        'purchase_price'
        // Removed: current_stock
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'min_limit' => 'integer'
    ];

    // Relationships
    public function stockDetails()
    {
        return $this->hasMany(StockDetail::class);
    }

    public function stocks()
    {
        return $this->belongsToMany(Stock::class, 'stock_details')
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }

    // Calculate current stock dynamically
    public function getCurrentStockAttribute()
    {
        // Formula: total_purchases + total_returns - total_sales - total_issues
        $totalPurchases = StockDetail::join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->where('stock_details.product_id', $this->id)
            ->where('stocks.stock_type', 'purchase')
            ->sum('stock_details.quantity');

        $totalSales = StockDetail::join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->where('stock_details.product_id', $this->id)
            ->where('stocks.stock_type', 'sale')
            ->sum('stock_details.quantity');

        $totalReturns = StockDetail::join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->where('stock_details.product_id', $this->id)
            ->where('stocks.stock_type', 'return')
            ->sum('stock_details.quantity');

        $totalIssues = StockDetail::join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->where('stock_details.product_id', $this->id)
            ->where('stocks.stock_type', 'issue')
            ->sum('stock_details.quantity');

        return $totalPurchases + $totalReturns - $totalSales - $totalIssues;
    }

    // Calculate total purchased quantity
    public function getTotalPurchasedAttribute()
    {
        return StockDetail::join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->where('stock_details.product_id', $this->id)
            ->where('stocks.stock_type', 'purchase')
            ->sum('stock_details.quantity');
    }

    // Calculate total sold quantity
    public function getTotalSoldAttribute()
    {
        return StockDetail::join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->where('stock_details.product_id', $this->id)
            ->where('stocks.stock_type', 'sale')
            ->sum('stock_details.quantity');
    }

    // Calculate total issued quantity
    public function getTotalIssuedAttribute()
    {
        return StockDetail::join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->where('stock_details.product_id', $this->id)
            ->where('stocks.stock_type', 'issue')
            ->sum('stock_details.quantity');
    }

    // Calculate total returned quantity
    public function getTotalReturnedAttribute()
    {
        return StockDetail::join('stocks', 'stock_details.stock_id', '=', 'stocks.id')
            ->where('stock_details.product_id', $this->id)
            ->where('stocks.stock_type', 'return')
            ->sum('stock_details.quantity');
    }

    // Check if product is low stock
    public function getIsLowStockAttribute()
    {
        return $this->current_stock <= $this->min_limit && $this->current_stock > 0;
    }

    // Check if product is out of stock
    public function getIsOutOfStockAttribute()
    {
        return $this->current_stock <= 0;
    }

    // Get stock value (current stock * purchase price)
    public function getStockValueAttribute()
    {
        return $this->current_stock * $this->purchase_price;
    }

    // Get stock movement history
    public function stockMovement($limit = 10)
    {
        return StockDetail::with(['stock' => function($query) {
                $query->select('id', 'date', 'stock_type', 'party_name');
            }])
            ->where('product_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('
            (SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "purchase" THEN stock_details.quantity ELSE 0 END), 0) -
             SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "sale" THEN stock_details.quantity ELSE 0 END), 0) +
             SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "return" THEN stock_details.quantity ELSE 0 END), 0) -
             SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "issue" THEN stock_details.quantity ELSE 0 END), 0)
            ) <= products.min_limit
        ');
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('
            (SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "purchase" THEN stock_details.quantity ELSE 0 END), 0) -
             SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "sale" THEN stock_details.quantity ELSE 0 END), 0) +
             SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "return" THEN stock_details.quantity ELSE 0 END), 0) -
             SELECT COALESCE(SUM(CASE WHEN stocks.stock_type = "issue" THEN stock_details.quantity ELSE 0 END), 0)
            ) <= 0
        ');
    }
}
