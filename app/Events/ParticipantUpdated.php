<?php

namespace App\Events;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $meeting;
    public $participant;

    /**
     * Create a new event instance.
     */
    public function __construct(Meeting $meeting, MeetingParticipant $participant)
    {
        $this->meeting = $meeting;
        $this->participant = $participant;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('meeting.' . $this->meeting->id),
        ];
    }
} 