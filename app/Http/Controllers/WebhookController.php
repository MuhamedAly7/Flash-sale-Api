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
        try {
            $this->orderService->confirmPayment($request->hold_id, $request->payment_intent_id);
            return ApiResponse::success('Payment confirmed. Stock permanently reserved.', [
                'hold_id' => $request->hold_id,
                'status' => 'paid'
            ]);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], $e->getCode() ?: 409);
        }
    }
}
