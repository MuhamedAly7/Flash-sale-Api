<?php

namespace Tests\Feature;

use App\Models\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Tests\TestCase;
use Redis;

class ConcurrencyNoOversellTest extends TestCase
{
    public function test_1000_real_parallel_requests_no_oversell(): void
    {
        Product::query()->update(['available_stock' => 100]);

        // Use REAL Redis connection — bypass Laravel's facade
        $redis = new Redis();
        $redis->connect('redis', 6379); // your redis container name
        $redis->flushall();

        $client = new Client([
            'base_uri' => 'http://web',
            'timeout'  => 30.0,
            'headers'  => ['Content-Type' => 'application/json'],
        ]);

        $promises = [];
        for ($i = 0; $i < 1000; $i++) {
            $promises[] = $client->postAsync('/api/hold', [
                'json' => ['product_id' => 1, 'quantity' => 1],
            ]);
        }

        echo "Firing 1000 concurrent requests...\n";
        $responses = Utils::settle($promises)->wait();

        $success = 0;
        foreach ($responses as $response) {
            if ($response['state'] === 'fulfilled' && $response['value']->getStatusCode() === 200) {
                $success++;
            }
        }

        $finalStock = (int) $redis->get('product:1:available_stock');
        $totalHolds = count($redis->keys('holds:*'));

        echo "1000 REQUESTS COMPLETED\n";
        echo "Success (200): $success\n";
        echo "Final Redis stock: $finalStock\n";
        echo "Total holds created: $totalHolds\n";

        $this->assertEquals(100, $success, "Exactly 100 should succeed");
        $this->assertEquals(0, $finalStock, "Stock must be 0");
        $this->assertEquals(100, $totalHolds, "Exactly 100 holds in Redis");

        $redis->close();
    }
}
