<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'description',
        'price',
        'total_stock',
        'available_stock',
    ];

    public function setAvailableStock(int $stock): void
    {
        $this->available_stock = $stock;
        $this->save();
    }
}
