<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * Attempt to place an order during a flash sale.
     *
     * Returns a success or failure JSON response; never throws to the client.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $this->orderService->createOrder(
                productId: $request->integer('product_id'),
                quantity:  $request->integer('quantity'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Order created',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}