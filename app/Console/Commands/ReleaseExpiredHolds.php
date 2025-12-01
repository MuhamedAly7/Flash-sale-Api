<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'hold:release-expired';
    protected $description = 'Release ALL holds whose TTL has expired (even if key is gone)';

    public function handle(): int
    {
        // We scan the hold_qty:* keys — they live 60 seconds longer than holds:*
        $qtyKeys = Redis::keys('hold_qty:*');

        $released = 0;
        foreach ($qtyKeys as $key) {
            $holdId = str_replace('hold_qty:', '', $key);
            $quantity = (int) Redis::get($key);

            if ($quantity > 0) {
                $stockKey = "product:1:available_stock"; // or extract from hold if multi-product
                if (Redis::exists($stockKey)) {
                    Redis::incrby($stockKey, $quantity);
                }
                Redis::del($key); // cleanup
                $released += $quantity;
            }
        }

        $this->info("Returned {$released} units to stock from expired holds.");
        return self::SUCCESS;
    }
}
