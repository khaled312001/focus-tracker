<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'status',
        'average_focus',
        'highest_focus',
        'lowest_focus',
        'total_focus_logs'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'participants', 'meeting_id', 'user_id')
            ->withPivot('is_present')
            ->withTimestamps();
    }

    public function focusLogs(): HasMany
    {
        return $this->hasMany(FocusLog::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Check if a user can join this meeting
     */
    public function canBeJoinedBy(User $user): bool
    {
        // Teachers can only join if they created the meeting
        if ($user->role === 'teacher') {
            return $this->teacher_id === $user->id;
        }

        // Students can join if the meeting is active
        if ($user->role === 'student') {
            return $this->status === 'active';
        }

        return false;
    }

    /**
     * Check if a user can end this meeting
     */
    public function canBeEndedBy(User $user): bool
    {
        return $user->role === 'teacher' && $this->teacher_id === $user->id;
    }
} 