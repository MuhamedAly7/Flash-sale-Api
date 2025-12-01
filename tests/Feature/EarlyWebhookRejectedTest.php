<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use Redis;
use Tests\TestCase;

class EarlyWebhookRejectedTest extends TestCase
{
    public function test_webhook_before_order_is_rejected(): void
    {
        $redis = new Redis();
        $redis->connect('redis', 6379);
        $redis->flushall();

        $client = new Client(['base_uri' => 'http://web']);

        $response = $client->post('/api/webhook/payment-success', [
            'headers' => ['Idempotency-Key' => 'early-guzzle-001'],
            'json'    => [
                'hold_id' => '00000000-0000-0000-0000-000000000000',
                'payment_intent_id' => 'pi_early'
            ],
            'http_errors' => false
        ]);

        $this->assertEquals(410, $response->getStatusCode());

        $redis->close();
    }
}
