<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $validated = $request->validated();
        $order = $orderService->create($validated['customer_email'], $validated['items']);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
