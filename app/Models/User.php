<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the meetings where the user is the teacher
     */
    public function teacherMeetings()
    {
        return $this->hasMany(Meeting::class, 'teacher_id');
    }

    /**
     * Get the meetings where the user is a student
     */
    public function studentMeetings()
    {
        return $this->belongsToMany(Meeting::class, 'participants', 'user_id', 'meeting_id')
            ->withPivot('is_present')
            ->withTimestamps();
    }

    /**
     * Get all meetings for the user (both as teacher and student)
     */
    public function meetings()
    {
        $teacherMeetings = $this->teacherMeetings()->getQuery();
        $studentMeetings = $this->studentMeetings()->getQuery();
        
        return $teacherMeetings->union($studentMeetings);
    }

    /**
     * Check if the user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Get the focus logs for the user
     */
    public function focusLogs()
    {
        return $this->hasMany(FocusLog::class, 'student_id');
    }

    /**
     * Get the focus statistics for the user
     */
    public function focusStats()
    {
        return $this->hasOne(FocusStats::class, 'student_id');
    }
}
