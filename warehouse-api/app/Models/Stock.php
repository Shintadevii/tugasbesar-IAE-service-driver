<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    
    protected $fillable = ['product_id', 'location', 'quantity', 'reorder_level'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function isBelowReorderLevel(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }
}