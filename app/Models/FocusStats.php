<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FocusStats extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'average_focus',
        'highest_focus',
        'lowest_focus',
        'total_logs',
        'last_updated'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'average_focus' => 'float',
        'highest_focus' => 'float',
        'lowest_focus' => 'float',
        'total_logs' => 'integer',
        'last_updated' => 'datetime'
    ];

    /**
     * Get the student that owns the focus stats.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
} 