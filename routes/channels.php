<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Meeting;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Meeting presence channel
Broadcast::channel('presence-meeting.{meetingId}', function ($user, $meetingId) {
    if ($user->role === 'teacher') {
        return ['id' => $user->id, 'name' => $user->name, 'role' => 'teacher'];
    }

    $meeting = Meeting::find($meetingId);
    if (!$meeting) return false;

    // Check if student is enrolled in the meeting
    if ($meeting->students()->where('user_id', $user->id)->exists()) {
        return ['id' => $user->id, 'name' => $user->name, 'role' => 'student'];
    }

    return false;
});

// Private teacher channel
Broadcast::channel('private-teacher.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id && $user->role === 'teacher';
});

// Private student channel
Broadcast::channel('private-student.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id && $user->role === 'student';
}); 