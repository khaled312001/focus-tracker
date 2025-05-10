<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentFocus implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $meetingId;
    public $userId;
    public $userName;
    public $focusScore;
    public $faceDetected;
    public $totalFocusTime;

    public function __construct($data)
    {
        $this->meetingId = $data['meetingId'];
        $this->userId = $data['userId'];
        $this->userName = $data['userName'];
        $this->focusScore = $data['focusScore'];
        $this->faceDetected = $data['faceDetected'];
        $this->totalFocusTime = $data['totalFocusTime'];
    }

    public function broadcastOn()
    {
        return new Channel('meeting.' . $this->meetingId);
    }

    public function broadcastAs()
    {
        return 'student.focus';
    }
} 