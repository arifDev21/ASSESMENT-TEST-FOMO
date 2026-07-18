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
    /**
     * List all products and their current stock levels.
     */
    public function products()
    {
        $products = Product::all();
        return response()->json([
            'success' => true,
            'data' => $products
        ], 200);
    }

    /**
     * Create a new order.
     * Handles race conditions using pessimistic locking (SELECT ... FOR UPDATE).
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming request format
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'items.required' => 'An Order must consist of at minimum one Order Item.',
            'items.min' => 'An Order must consist of at minimum one Order Item.',
            'items.*.product_id.exists' => 'The selected product does not exist.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 2. Wrap database modifications inside a transaction
            $order = DB::transaction(function () use ($request) {
                $itemsData = $request->input('items');
                $totalAmount = 0.0;
                $orderItemsToCreate = [];

                // To prevent deadlocks, sort items by product_id so that multiple concurrent
                // requests always acquire locks on products in the exact same order.
                usort($itemsData, function ($a, $b) {
                    return $a['product_id'] <=> $b['product_id'];
                });

                foreach ($itemsData as $itemData) {
                    $productId = $itemData['product_id'];
                    $quantity = $itemData['quantity'];

                    // 3. Acquire pessimistic lock on the product row (SELECT ... FOR UPDATE)
                    // This blocks other transactions from acquiring locks or modifying this row
                    // until this transaction commits or rolls back.
                    $product = Product::where('id', $productId)
                        ->lockForUpdate()
                        ->first();

                    // 4. Verify inventory level
                    if ($product->stock < $quantity) {
                        throw new \RuntimeException(
                            "Product '{$product->name}' (ID: {$productId}) is out of stock. " .
                            "Requested: {$quantity}, Available: {$product->stock}."
                        );
                    }

                    // 5. Decrement the inventory. This is safe under lock.
                    // This also prevents the stock from ever going negative.
                    $product->decrement('stock', $quantity);

                    // Compute total amount for the order item
                    $lineTotal = $product->price * $quantity;
                    $totalAmount += $lineTotal;

                    // Prep order item database insertion details
                    $orderItemsToCreate[] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => $product->price,
                    ];
                }

                // 6. Create the order record
                $order = Order::create([
                    'total_amount' => $totalAmount,
                    'status' => 'completed',
                ]);

                // 7. Associate and create order items
                foreach ($orderItemsToCreate as $orderItemDetail) {
                    $orderItemDetail['order_id'] = $order->id;
                    OrderItem::create($orderItemDetail);
                }

                return $order;
            });

            // Return success response with order details loaded
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => $order->load('orderItems.product')
            ], 201); // 201 Created
        } catch (\RuntimeException $e) {
            // Out of stock exception caught
            return response()->json([
                'success' => false,
                'message' => 'Unable to process order due to stock constraints.',
                'error' => $e->getMessage()
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            // General system error caught
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while placing the order.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }
}
