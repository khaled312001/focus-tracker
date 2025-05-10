<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $meetingId;
    public $type;
    public $data;
    public $socket = null;

    /**
     * Create a new event instance.
     */
    public function __construct($meetingId, $type, $data, $socket = null)
    {
        $this->meetingId = $meetingId;
        $this->type = $type;
        $this->data = $data;
        $this->socket = $socket;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('meeting.' . $this->meetingId);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith()
    {
        return [
            'type' => $this->type,
            'data' => array_merge($this->data, [
                'timestamp' => now()->toISOString(),
                'socket' => $this->socket
            ])
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'meeting.event';
    }
} 