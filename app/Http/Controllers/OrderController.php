<?php

namespace App\Http\Controllers;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\ProductRepositoryInterface;
use App\Helpers\ApiResponse;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\HoldService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            \Log::error('Order creation failed', ['error' => $e->getMessage()]);
            return ApiResponse::error('Internal server error', [], 500);
        }
    }
}
