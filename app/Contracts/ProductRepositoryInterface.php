<?php

namespace App\Contracts;

interface ProductRepositoryInterface
{
    public function getAvailableStock(int $productId): int;
}
