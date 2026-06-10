<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products',  [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::post('/orders',   [OrderController::class, 'store']);