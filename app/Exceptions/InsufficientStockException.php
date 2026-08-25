<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly Product $product,
        public readonly int $requestedQuantity,
    ) {
        parent::__construct("Insufficient stock for {$product->name}.");
    }

    public function render(): mixed
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'items' => [[
                    'product_id' => $this->product->id,
                    'requested_quantity' => $this->requestedQuantity,
                    'available_quantity' => $this->product->stock_quantity,
                ]],
            ],
        ], 422);
    }
}
