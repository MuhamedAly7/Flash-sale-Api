<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class HoldService
{
    protected $redis;
    protected $productRepository;
    protected const STOCK_KEY_PREFIX = 'product:';
    protected const HOLD_PREFIX      = 'holds:';

    public function __construct(ProductRepository $productRepository)
    {
        // Direct connection — bypass Laravel config hell
        $this->redis = new \Redis();
        $this->redis->connect('redis', 6379);
        $this->productRepository = $productRepository;
    }

    public function hold(int $productId, int $quantity, int $ttlSeconds = 120): ?string
    {
        $stockKey = self::STOCK_KEY_PREFIX . $productId . ':available_stock';
        $holdId   = (string) Str::uuid();

        // Sync from DB only if Redis key doesn't exist
        if (!$this->redis->exists($stockKey)) {
            $dbStock = $this->productRepository->getAvailableStock($productId);
            $this->redis->set($stockKey, $dbStock);
        }

        $lua = <<<LUA
        local current = tonumber(redis.call('GET', KEYS[1]) or '0')
        local qty     = tonumber(ARGV[1])

        if current < qty then
            return 0
        end

        redis.call('DECRBY', KEYS[1], qty)
        redis.call('HSET', 'holds:'..ARGV[2], 'stock_key', KEYS[1], 'quantity', qty)
        redis.call('EXPIRE', KEYS[1], ARGV[3])
        redis.call('EXPIRE', 'holds:'..ARGV[2], ARGV[3])

        return 1
LUA;

        $result = $this->redis->eval($lua, [$stockKey, $quantity, $holdId, $ttlSeconds], 1);

        return $result === 1 ? $holdId : null;
    }

    public function release(string $holdId): void
    {
        $data = $this->redis->hGetAll(self::HOLD_PREFIX . $holdId);
        if (empty($data['stock_key'] ?? null)) return;

        $this->redis->incrBy($data['stock_key'], $data['quantity']);
        $this->redis->del(self::HOLD_PREFIX . $holdId);
    }

    public function commit(string $holdId): void
    {
        $this->redis->del(self::HOLD_PREFIX . $holdId);
    }
}
