<?php

namespace App\Repositories;

use App\Contracts\OrderRepositoryInterface;
use App\Models\Order;

class OrderRepository implements OrderRepositoryInterface
{

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function existsByHoldId(string $holdId): bool
    {
        return Order::where('hold_id', $holdId)->exists();
    }

    public function findByHoldId(string $holdId): ?Order
    {
        return Order::where('hold_id', $holdId)->first();
    }
}
