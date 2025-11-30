<?php

namespace App\Repositories;

use App\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use App\Services\HoldService;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{
    public function getAvailableStock(int $productId): int
    {
        return (int) DB::table('products')
            ->where('id', $productId)
            ->value('available_stock');
    }

    public function find(int $productId): ?Product
    {
        return Product::find($productId);
    }

    public function getWithRealtimeStock(int $productId, HoldService $holdService): ?array
    {
        $product = Product::select('id', 'name', 'description', 'price', 'total_stock')
            ->find($productId);

        if (!$product) {
            return null;
        }

        $available = $holdService->getCurrentStock($productId);

        return [
            'id'              => $product->id,
            'name'            => $product->name,
            'description'     => $product->description ?? null,
            'price'           => (float) $product->price,
            'total_stock'     => (int) $product->total_stock,
            'available_stock' => $available,
            'is_sold_out'     => $available <= 0,
        ];
    }
}
