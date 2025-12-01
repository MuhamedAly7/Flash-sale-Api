<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use Redis;
use Tests\TestCase;
use Illuminate\Support\Facades\Date;

class HoldExpiryReturnsStockTest extends TestCase
{
    public function test_expired_hold_returns_stock(): void
    {
        $redis = new Redis();
        $redis->connect('redis', 6379);
        $redis->flushall();

        $client = new Client(['base_uri' => 'http://web']);

        // Create hold
        $response = $client->post('/api/hold', [
            'json' => ['product_id' => 1, 'quantity' => 40]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $holdId = json_decode($response->getBody())->data->hold_id;

        $this->assertEquals(60, $redis->get('product:1:available_stock'));

//        // Travel time forward (TTL = 120s)
//        Date::setTestNow(now()->addMinutes(3));
//
//        // Call the command to release expired holds
//        $this->artisan('hold:release-expired')->assertExitCode(0);

        sleep(121);

        $this->assertEquals(100, $redis->get('product:1:available_stock'));
        $this->assertFalse($redis->exists("holds:{$holdId}"));

        $redis->close();
    }
}
