<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create an order for a given product with race condition protection.
     *
     * Uses a database transaction with a pessimistic lock (lockForUpdate)
     * to ensure that concurrent requests never cause stock to go negative.
     *
     * @throws Exception When the product is out of stock.
     */
    public function createOrder(int $productId, int $quantity): Order
    {
        return DB::transaction(function () use ($productId, $quantity) {
            // Acquire a row-level lock so concurrent transactions must wait.
            $product = Product::lockForUpdate()->findOrFail($productId);

            if ($product->stock < $quantity) {
                throw new Exception('Out of stock');
            }

            // Decrement stock atomically inside the transaction.
            $product->stock -= $quantity;
            $product->save();

            $order = Order::create();

            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'quantity'   => $quantity,
            ]);

            return $order;
        });
    }
}