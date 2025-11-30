<?php

namespace App\Services;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\ProductRepositoryInterface;
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
        DB::transaction(function () use ($holdId, $paymentIntentId) {
            $order = $this->orderRepository->findByHoldId($holdId);

            if (!$order || $order->status !== 'pending') {
                throw new \DomainException('Invalid or already processed order', 410);
            }

            if ($order->payment_intent_id !== $paymentIntentId) {
                throw new \DomainException('Payment intent mismatch', 400);
            }

            $this->holdService->commit($holdId);

            $productId = $order->product_id;
            $quantity = $order->quantity;

            $affected = Product::where('id', $productId)
                ->where('stock', '>=', $quantity)
                ->decrement('stock', $quantity);

            if ($affected === 0) {
                throw new \DomainException('Insufficient stock to complete the order', 409);
            }

            $order->update([
                'status' => 'paid'
            ]);
        });
    }

    private function extractProductId(string $stockKey): int
    {
        return (int) explode(':', $stockKey)[1];
    }
}
