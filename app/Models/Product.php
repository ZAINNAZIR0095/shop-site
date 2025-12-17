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
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'min_limit' => 'integer'
    ];
}
