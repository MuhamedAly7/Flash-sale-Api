<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use Redis;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    public function test_webhook_is_idempotent(): void
    {
        $redis = new Redis();
        $redis->connect('redis', 6379);
        $redis->flushall();

        $client = new Client(['base_uri' => 'http://web']);

        // 1. Hold
        $res = $client->post('/api/hold', ['json' => ['product_id' => 1, 'quantity' => 25]]);
        $holdId = json_decode($res->getBody())->data->hold_id;

        // 2. Create order
        $client->post('/api/order', ['json' => [
            'hold_id' => $holdId,
            'payment_intent_id' => 'pi_idempotent'
        ]]);

        $stockAfterOrder = $redis->get('product:1:available_stock');

        $key = 'idempotent-guzzle-001';

        // 3. First webhook
        $client->post('/api/webhook/payment-success', [
            'headers' => ['Idempotency-Key' => $key],
            'json'    => ['hold_id' => $holdId, 'payment_intent_id' => 'pi_idempotent']
        ]);

        $stockAfterFirst = $redis->get('product:1:available_stock');

        // 4. Second webhook — same key
        $client->post('/api/webhook/payment-success', [
            'headers' => ['Idempotency-Key' => $key],
            'json'    => ['hold_id' => $holdId, 'payment_intent_id' => 'pi_idempotent']
        ]);

        $this->assertEquals($stockAfterFirst, $redis->get('product:1:available_stock'));
        $redis->close();
    }
}
