<?php

namespace App\Services;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\ProductRepositoryInterface;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected HoldService $holdService;
    protected ProductRepositoryInterface $productRepository;
    protected OrderRepositoryInterface $orderRepository;

    public function __construct(HoldService $holdService, ProductRepositoryInterface $productRepository, OrderRepositoryInterface $orderRepository)
    {
        $this->holdService = $holdService;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
    }

    public function createFromHold(string $holdId, array $paymentData): object
    {
        return DB::transaction(function () use ($holdId, $paymentData) {
            $holdData = $this->holdService->getHoldData($holdId);
            if (!$holdData) {
                throw new \DomainException('Hold not found or expired', 410);
            }

            if ($this->orderRepository->existsByHoldId($holdId)) {
                throw new \DomainException('Order already created for this hold', 409);
            }

            $productId = $this->extractProductId($holdData['stock_key']);
            $product = $this->productRepository->find($productId);

            if (!$product) {
                throw new \DomainException('Product not found', 404);
            }

            $order = $this->orderRepository->create([
                'product_id'        => $product->id,
                'quantity'          => $holdData['quantity'],
                'unit_price'        => $product->price,
                'total_price'       => $product->price * $holdData['quantity'],
                'hold_id'           => $holdId,
                'status'            => 'pending',
                'payment_intent_id' => $paymentData['payment_intent_id'],
                'payment_data'      => $paymentData,
            ]);

            return (object) [
                'order' => $order,
                'message' => 'Order created successfully',
                'status' => 201
            ];
        });
    }

    public function confirmPayment(string $holdId, string $paymentIntentId): void
    {
        $order = $this->orderRepository->findByHoldId($holdId);

        if (!$order || $order->status !== 'pending') {
            throw new \DomainException('Order not found or already processed', 410);
        }

        if ($order->payment_intent_id !== $paymentIntentId) {
            throw new \DomainException('Payment intent mismatch', 400);
        }

        // before any transaction
        $holdExists = $this->holdService->holdExists($holdId);

        if (!$holdExists) {
            $this->markOrderAsExpired($order);
            throw new \DomainException('Hold expired — order canceled', 410);
        }

        DB::transaction(function () use ($order, $holdId) {
            $this->holdService->commit($holdId);

            $affected = Product::where('id', $order->product_id)
                ->where('available_stock', '>=', $order->quantity)
                ->decrement('available_stock', $order->quantity);

            if ($affected === 0) {
                throw new \DomainException('Stock inconsistency', 500);
            }

            $order->update(['status' => 'paid']);
        });
    }

    private function markOrderAsExpired(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->status = 'failed';
            $order->save();
        });
    }

    private function extractProductId(string $stockKey): int
    {
        return (int) explode(':', $stockKey)[1];
    }
}
