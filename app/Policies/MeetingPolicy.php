<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeetingPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->teacher_id;
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->teacher_id;
    }
} 