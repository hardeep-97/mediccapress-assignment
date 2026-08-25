<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(string $customerEmail, array $requestedItems): Order
    {
        $order = DB::transaction(function () use ($customerEmail, $requestedItems) {
            $itemsByProductId = collect($requestedItems)->keyBy('product_id');
            $products = $this->lockProducts($itemsByProductId);

            $this->ensureEveryProductExists($itemsByProductId, $products);
            $this->ensureSufficientStock($itemsByProductId, $products);

            $totalInCents = $products->sum(function (Product $product) use ($itemsByProductId) {
                return $this->priceToCents($product->price) * $itemsByProductId[$product->id]['quantity'];
            });

            $order = Order::query()->create([
                'customer_email' => $customerEmail,
                'total_amount' => $this->centsToPrice($totalInCents),
                'status' => Order::STATUS_PENDING,
            ]);

            foreach ($products as $product) {
                $quantity = $itemsByProductId[$product->id]['quantity'];
                $subtotalInCents = $this->priceToCents($product->price) * $quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $this->centsToPrice($subtotalInCents),
                ]);

                $product->decrement('stock_quantity', $quantity);
            }

            return $order->load('items.product');
        }, 3);

        OrderPlaced::dispatch($order);

        return $order;
    }

    private function lockProducts(Collection $itemsByProductId): Collection
    {
        return Product::query()
            ->whereKey($itemsByProductId->keys())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function ensureEveryProductExists(Collection $itemsByProductId, Collection $products): void
    {
        $missingIds = $itemsByProductId->keys()->diff($products->keys());

        if ($missingIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => ['One or more selected products are no longer available.'],
            ]);
        }
    }

    private function ensureSufficientStock(Collection $itemsByProductId, Collection $products): void
    {
        foreach ($products as $product) {
            $quantity = $itemsByProductId[$product->id]['quantity'];

            if ($quantity > $product->stock_quantity) {
                throw new InsufficientStockException($product, $quantity);
            }
        }
    }

    private function priceToCents(string $price): int
    {
        [$whole, $fraction] = array_pad(explode('.', $price, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function centsToPrice(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
