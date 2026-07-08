<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationTriggered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $id,
        public readonly string $type,
        public readonly string $message,
        public readonly string $url,
        public readonly string $createdAt,
        public readonly ?string $userName = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-alerts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'message'    => $this->message,
            'url'        => $this->url,
            'created_at' => $this->createdAt,
            'user_name'  => $this->userName,
        ];
    }
}