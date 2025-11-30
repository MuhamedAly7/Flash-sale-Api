<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\OrderRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function paymentSuccess(OrderRequest $request)
    {
        $holdId = $request->validated('hold_id');
        $paymentIntentId = $request->validated('payment_intent_id');

        if (!$holdId || !$paymentIntentId) {
            return ApiResponse::error('Missing hold_id or payment_intent_id', [], 400);
        }

        try {
            $this->orderService->confirmPayment($holdId, $paymentIntentId);
            return ApiResponse::success('Payment confirmed. Stock permanently reserved.', [
                'hold_id' => $holdId,
                'status' => 'paid'
            ]);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], $e->getCode() ?: 409);
        }
    }
}
