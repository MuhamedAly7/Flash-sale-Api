<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class HoldStockConcurrencyTest extends TestCase
{
    public function test_1000_concurrent_hold_requests_only_100_succeed()
    {

    }
}
