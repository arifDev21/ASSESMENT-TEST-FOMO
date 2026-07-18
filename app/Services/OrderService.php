<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\OrderServiceInterface;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        protected ProductRepositoryInterface $productRepo,
        protected OrderRepositoryInterface $orderRepo
    ) {}

    public function placeOrder(array $items): Order
    {
        return DB::transaction(function () use ($items) {
            $totalAmount = 0.0;
            $itemsToCreate = [];

            // Sort to prevent deadlocks
            usort($items, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];

                $product = $this->productRepo->findAndLock($productId);

                if (!$product || $product->stock < $quantity) {
                    $name = $product ? $product->name : "Unknown product";
                    throw new \RuntimeException("Product '{$name}' is out of stock.");
                }

                $product->decrement('stock', $quantity);
                $totalAmount += $product->price * $quantity;

                $itemsToCreate[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->price,
                ];
            }

            return $this->orderRepo->createOrderWithItems($totalAmount, $itemsToCreate);
        });
    }
}
