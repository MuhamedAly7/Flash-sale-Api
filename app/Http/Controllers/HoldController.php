<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\HoldRequest;
use App\Services\HoldService;

class HoldController extends Controller
{
    public function hold(HoldRequest $request, HoldService $holdService)
    {
        $holdId = $holdService->hold(
            productId: $request->validated('product_id'),
            quantity: $request->validated('quantity')
        );

        if (!$holdId) {
            return ApiResponse::error('Not enough stock', [], 409);
        }

        return ApiResponse::success('Stock held successfully', [
            'hold_id' => $holdId,
            'expires_in_seconds' => 120
        ]);
    }
}
