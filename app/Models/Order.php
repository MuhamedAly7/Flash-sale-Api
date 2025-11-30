<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id', 'quantity', 'unit_price', 'total_price',
        'hold_id', 'status', 'payment_intent_id', 'payment_data'
    ];

    protected $casts = [
        'payment_data' => 'array'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
