<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FocusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'meeting_id',
        'focus_level',
        'duration',
    ];

    protected $casts = [
        'focus_level' => 'float',
        'duration' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
} 