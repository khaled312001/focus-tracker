<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'meeting_id',
        'is_video_enabled',
        'is_audio_enabled',
        'joined_at',
        'left_at'
    ];

    protected $casts = [
        'is_video_enabled' => 'boolean',
        'is_audio_enabled' => 'boolean',
        'joined_at' => 'datetime',
        'left_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
} 