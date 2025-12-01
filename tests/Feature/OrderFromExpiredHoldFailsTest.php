<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use Redis;
use Tests\TestCase;

class OrderFromExpiredHoldFailsTest extends TestCase
{
    public function test_cannot_order_from_expired_hold(): void
    {
        $redis = new Redis();
        $redis->connect('redis', 6379);
        $redis->flushall();

        $client = new Client(['base_uri' => 'http://web']);

        $res = $client->post('/api/hold', ['json' => ['product_id' => 1, 'quantity' => 15]]);
        $holdId = json_decode($res->getBody())->data->hold_id;

        // Expire it
        $this->artisan('tinker --execute="Date::setTestNow(now()->addMinutes(4));"');
        $this->artisan('hold:release-expired');

        $response = $client->post('/api/order', [
            'json' => ['hold_id' => $holdId, 'payment_intent_id' => 'pi_expired'],
            'http_errors' => false
        ]);

        $this->assertEquals(410, $response->getStatusCode());

        $redis->close();
    }
}
