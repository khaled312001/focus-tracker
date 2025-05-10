<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FocusData extends Model
{
    protected $fillable = [
        'meeting_id',
        'user_id',
        'user_name',
        'focus_score',
        'face_detected',
        'total_focus_time',
        'last_update',
    ];

    protected $casts = [
        'focus_score' => 'float',
        'face_detected' => 'boolean',
        'total_focus_time' => 'integer',
        'last_update' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
