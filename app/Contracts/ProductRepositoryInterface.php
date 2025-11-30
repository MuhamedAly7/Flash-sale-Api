<?php

namespace App\Contracts;

use App\Services\HoldService;

interface ProductRepositoryInterface
{
    public function getAvailableStock(int $productId): int;
    public function getWithRealtimeStock(int $productId, HoldService $holdService): ?array;
}
