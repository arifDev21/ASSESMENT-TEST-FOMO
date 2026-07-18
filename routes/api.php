<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OrderController;

Route::get('/products', [OrderController::class, 'products']);
Route::post('/orders', [OrderController::class, 'store']);
