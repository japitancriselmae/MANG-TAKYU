<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CookingBatchNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $batchId,
        public string $status,
        public int $quantity,
        public int $waitingOrderCount,
        public array $waitingOrderIds
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $state = $this->status === 'ready'
            ? 'is ready for service'
            : 'is being cooked';
        $waitingOrders = $this->waitingOrderCount === 1
            ? '1 fresh-chicken order is waiting'
            : $this->waitingOrderCount.' fresh-chicken orders are waiting';
        $orderNumbers = collect($this->waitingOrderIds)
            ->take(5)
            ->map(fn (int $orderId): string => '#'.$orderId)
            ->implode(', ');
        $additionalOrders = $this->waitingOrderCount > 5
            ? ' and '.($this->waitingOrderCount - 5).' more'
            : '';

        return [
            'batch_id' => $this->batchId,
            'food_type' => 'chicken',
            'status' => $this->status,
            'quantity' => $this->quantity,
            'waiting_order_count' => $this->waitingOrderCount,
            'waiting_order_ids' => $this->waitingOrderIds,
            'message' => 'Fresh chicken batch ('.$this->quantity.' pcs) '.$state.'. '.$waitingOrders
                .' ('.$orderNumbers.$additionalOrders.').',
        ];
    }
}
