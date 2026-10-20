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
        public array $waitingOrderIds,
        public string $startedAt,
        public int $durationMinutes
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

        return [
            'batch_id' => $this->batchId,
            'food_type' => 'chicken',
            'status' => $this->status,
            'quantity' => $this->quantity,
            'waiting_order_count' => $this->waitingOrderCount,
            'waiting_order_ids' => $this->waitingOrderIds,
            'started_at' => $this->startedAt,
            'duration_minutes' => $this->durationMinutes,
            'message' => 'Fresh chicken batch ('.$this->quantity.' pcs) '.$state.'.',
        ];
    }
}
