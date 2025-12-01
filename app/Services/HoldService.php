<?php

namespace App\Services;

use App\Contracts\ProductRepositoryInterface;
use App\Repositories\ProductRepository;
use Illuminate\Support\Str;

class HoldService
{
    public $redis;
    protected $productRepository;
    protected const STOCK_KEY_PREFIX = 'product:';
    protected const HOLD_PREFIX      = 'holds:';

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->redis = new \Redis();
        $this->redis->connect('redis', 6379);
        $this->productRepository = $productRepository;
    }

    public function hold(int $productId, int $quantity, int $ttlSeconds = 120): ?string
    {
        $stockKey = "product:{$productId}:available_stock";
        $holdId   = (string) \Str::uuid();

        $lua = <<<LUA
        local stock_key = KEYS[1]
        local qty       = tonumber(ARGV[1])
        local hold_id   = ARGV[2]
        local ttl       = tonumber(ARGV[3])
        local db_stock  = tonumber(ARGV[4])

        -- Restore from DB if key gone
        if redis.call('EXISTS', stock_key) == 0 then
            redis.call('SET', stock_key, db_stock)
        end

        local current = tonumber(redis.call('GET', stock_key))
        if current < qty then
            return 0
        end

        redis.call('DECRBY', stock_key, qty)

        -- STORE stock_key so OrderService can find it later
        redis.call('HSET', 'holds:'..hold_id,
            'product_id', {$productId},
            'quantity', qty,
            'stock_key', stock_key
        )

        redis.call('EXPIRE', 'holds:'..hold_id, ttl)

        -- For automatic expiry return
        redis.call('SET', 'expired_hold:'..hold_id, qty, 'EX', ttl + 60)

        return 1
LUA;

        $dbStock = $this->productRepository->getAvailableStock($productId);

        $result = $this->redis->eval($lua, [
            $stockKey,
            $quantity,
            $holdId,
            $ttlSeconds,
            $dbStock
        ], 1);

        return $result === 1 ? $holdId : null;
    }

    public function release(string $holdId): void
    {
        $holdKey = "holds:{$holdId}";
        $qtyKey  = "hold_qty:{$holdId}";

        $data = $this->redis->hGetAll($holdKey);
        $quantity = (int) ($data['quantity'] ?? $this->redis->get($qtyKey) ?? 0);

        if ($quantity > 0 && isset($data['product_id'])) {
            $stockKey = "product:{$data['product_id']}:available_stock";
            if ($this->redis->exists($stockKey)) {
                $this->redis->incrby($stockKey, $quantity);
            }
        }

        $this->redis->del($holdKey);
        $this->redis->del($qtyKey);
    }

    public function commit(string $holdId): void
    {
        $this->redis->del(self::HOLD_PREFIX . $holdId);
    }

    public function getHoldData(string $holdId): ?array
    {
        $data = $this->redis->hGetAll(self::HOLD_PREFIX . $holdId);
        return !empty($data) ? $data : null;
    }

    public function getCurrentStock(int $productId): int
    {
        $stockKey = self::STOCK_KEY_PREFIX . $productId . ':available_stock';

        if (!$this->redis->exists($stockKey)) {
            $dbStock = $this->productRepository->getAvailableStock($productId);
            $this->redis->set($stockKey, $dbStock);
        }

        return (int) $this->redis->get($stockKey);
    }

    public function holdExists(string $holdId): bool
    {
        return $this->redis->exists("holds:{$holdId}") === 1;
    }
}
