<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'endpoints' => [
            'GET /api/products',
            'POST /api/orders',
        ],
    ]);
});
