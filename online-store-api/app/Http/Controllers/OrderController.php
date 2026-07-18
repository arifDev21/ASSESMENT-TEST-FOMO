<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\OrderServiceInterface;

class OrderController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $productRepo,
        protected OrderServiceInterface $orderService
    ) {}

    public function products()
    {
        return response()->json([
            'success' => true,
            'data' => $this->productRepo->all()
        ]);
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->placeOrder($request->input('items'));

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => $order->load('orderItems.product')
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while placing the order.'
            ], 500);
        }
    }
}
