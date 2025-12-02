<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\WebhookPaymentSuccessRequest;
use App\Services\OrderService;
use Illuminate\Support\Facades\Cache;

class WebhookController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function paymentSuccess(WebhookPaymentSuccessRequest $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return ApiResponse::error('Idempotency-Key header is required');
        }

        // PREVENT DUPLICATE PROCESSING - FOREVER
        $cacheKey = "webhook:processed:{$idempotencyKey}";

        if (Cache::has($cacheKey)) {
            return ApiResponse::success('Already processed', [
                'status' => 'already_processed'
            ]);
        }

        try {
            $this->orderService->confirmPayment($request->hold_id, $request->payment_intent_id);
            Cache::forever($cacheKey, true);
            return ApiResponse::success('Payment confirmed. Stock permanently reserved.', [
                'hold_id' => $request->hold_id,
                'status' => 'paid'
            ]);
        } catch (\DomainException $e) {
            // Allow retry — do NOT cache
            return ApiResponse::error($e->getMessage(), [], $e->getCode() ?: 409);
        } catch (\Throwable $e) {
            // Still allow retry
            return ApiResponse::error('Internal error — retrying', [], 500);
        }
    }
}
