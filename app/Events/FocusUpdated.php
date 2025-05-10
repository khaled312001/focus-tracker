<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FocusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $meetingId;
    public $studentId;
    public $focusLevel;
    public $studentName;
    public $timestamp;

    public function __construct($meetingId, $studentId, $focusLevel, $studentName)
    {
        $this->meetingId = $meetingId;
        $this->studentId = $studentId;
        $this->focusLevel = $focusLevel;
        $this->studentName = $studentName;
        $this->timestamp = now();
    }

    public function broadcastOn()
    {
        return new PresenceChannel('meeting.' . $this->meetingId);
    }

    public function broadcastAs()
    {
        return 'focus.updated';
    }

    public function broadcastWith()
    {
        return [
            'student_id' => $this->studentId,
            'focus_level' => $this->focusLevel,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
} 