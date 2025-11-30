<?php

namespace App\Repositories;

use App\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{

    public function getAvailableStock(int $productId): int
    {
        return (int) DB::table('products')
            ->where('id', $productId)
            ->value('available_stock');
    }
}
