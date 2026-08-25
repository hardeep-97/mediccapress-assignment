<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Log;

class LogOrderReceipt implements ShouldQueueAfterCommit
{
    public int $tries = 3;

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing('items.product');
        $itemLines = $order->items
            ->map(fn ($item) => sprintf(
                '- %s x %d @ $%s = $%s',
                $item->product->name,
                $item->quantity,
                $item->unit_price,
                $item->subtotal,
            ))
            ->implode(PHP_EOL);

        $receipt = implode(PHP_EOL, [
            "Subject: Order Receipt #{$order->id}",
            "To: {$order->customer_email}",
            '',
            'Thank you for your order.',
            '',
            'Items:',
            $itemLines,
            '',
            "Total: \${$order->total_amount}",
            "Status: {$order->status}",
        ]);

        Log::info($receipt);
    }

    public function backoff(): array
    {
        return [5, 30, 60];
    }
}
