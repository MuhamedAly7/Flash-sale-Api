<?php

namespace App\Services;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\ProductRepositoryInterface;

class ProductService
{
    protected HoldService $holdService;
    protected ProductRepositoryInterface $productRepository;

    public function __construct(HoldService $holdService, ProductRepositoryInterface $productRepository, OrderRepositoryInterface $orderRepository)
    {
        $this->holdService = $holdService;
        $this->productRepository = $productRepository;
    }

    public function getProductStockInfo(int $productId): ?array
    {
        return $this->productRepository->getWithRealtimeStock($productId, $this->holdService);
    }
}
