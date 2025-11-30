<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\OrderRequest;
use App\Services\OrderService;

class OrderController extends Controller
{
    protected OrderService $orderService;
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function order(OrderRequest $request)
    {
        try {
            $result = $this->orderService->createFromHold($request->hold_id, $request->all());

            return ApiResponse::success(
                $result->message,
                [
                    'order_id'   => $result->order->id,
                    'status'     => $result->order->status,
                    'amount'     => $result->order->total_price,
                    'currency'   => 'USD',
                    'pay_with'   => "payment_intent_id: {$result->order->payment_intent_id}"
                ],
                $result->status
            );
        } catch (\DomainException $e) {
            $code = $e->getCode() ?: 400;
            return ApiResponse::error($e->getMessage(), [], $code);
        } catch (\Exception $e) {
            return ApiResponse::error('Internal server error', [], 500);
        }
    }
}
