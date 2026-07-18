<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function products()
    {
        return response()->json([
            'success' => true,
            'data' => Product::all()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'items.required' => 'An Order must consist of at minimum one Order Item.',
            'items.min' => 'An Order must consist of at minimum one Order Item.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($request) {
                $itemsData = $request->input('items');
                $totalAmount = 0.0;
                $orderItemsToCreate = [];

                // Sort to prevent deadlocks
                usort($itemsData, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

                foreach ($itemsData as $itemData) {
                    $productId = $itemData['product_id'];
                    $quantity = $itemData['quantity'];

                    $product = Product::where('id', $productId)->lockForUpdate()->first();

                    if ($product->stock < $quantity) {
                        throw new \RuntimeException("Product '{$product->name}' is out of stock.");
                    }

                    $product->decrement('stock', $quantity);
                    $totalAmount += $product->price * $quantity;

                    $orderItemsToCreate[] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => $product->price,
                    ];
                }

                $order = Order::create([
                    'total_amount' => $totalAmount,
                    'status' => 'completed',
                ]);

                foreach ($orderItemsToCreate as $itemDetail) {
                    $itemDetail['order_id'] = $order->id;
                    OrderItem::create($itemDetail);
                }

                return $order;
            });

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
