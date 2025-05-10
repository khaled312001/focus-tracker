<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    protected $fillable = [
        'user_id',
        'meeting_id',
        'joined_at',
        'left_at',
        'average_focus',
        'highest_focus',
        'lowest_focus',
        'total_focus_logs'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'average_focus' => 'float',
        'is_present' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function focusLogs()
    {
        return $this->hasMany(FocusLog::class, 'student_id', 'user_id')
            ->where('meeting_id', $this->meeting_id);
    }
} 