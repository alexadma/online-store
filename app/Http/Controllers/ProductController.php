<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Return a list of all products.
     */
    public function index(): JsonResponse
    {
        return response()->json(Product::all());
    }

    /**
     * Create a new product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'id'    => $product->id,
            'name'  => $product->name,
            'stock' => $product->stock,
        ], 201);
    }
}