<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use Redis;
use Tests\TestCase;

class ProductRealTimeStockTest extends TestCase
{
    public function test_product_shows_real_time_stock(): void
    {
        $redis = new Redis();
        $redis->connect('redis', 6379);
        $redis->flushall();

        $client = new Client(['base_uri' => 'http://web']);

        $client->post('/api/api/hold', ['json' => ['product_id' => 1, 'quantity' => 35]]);

        $response = $client->get('/api/products/1');
        $data = json_decode($response->getBody())->data;

        $this->assertEquals(65, $data->available_stock);

        $redis->close();
    }
}
