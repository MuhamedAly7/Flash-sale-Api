<?php

namespace App\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function create(array $data): Order;
    public function existsByHoldId(string $holdId): bool;
    public function findByHoldId(string $holdId): ?Order;
}
