<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FocusLogController;
use App\Http\Controllers\Teacher\TeacherController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\BroadcastingController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FocusController;
use App\Http\Controllers\FocusDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

Route::get('/', [WelcomeController::class, 'index']);

// Temporary route to check user role
Route::get('/check-role', function () {
    if (Auth::check()) {
        return 'You are logged in as: ' . Auth::user()->role;
    }
    return 'You are not logged in';
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Broadcasting routes
    Route::post('/broadcasting/{event}', [BroadcastingController::class, 'handleEvent'])->name('broadcasting.event');
    Route::get('/broadcasting/events', [BroadcastingController::class, 'getEvents'])->name('broadcasting.events');

    // Teacher routes
    Route::middleware(['auth', 'role:teacher'])->group(function () {
        Route::get('/teacher/dashboard', [TeacherController::class, 'dashboard'])->name('teacher.dashboard');
        Route::get('/teacher/analytics', [TeacherController::class, 'analytics'])->name('teacher.analytics');
        Route::get('/teacher/meeting/{meeting}', [TeacherController::class, 'meetingDetails'])->name('teacher.meeting');
        Route::get('/teacher/join-meeting/{meeting}', [TeacherController::class, 'joinMeeting'])->name('teacher.join-meeting');
        Route::get('/teacher/reports', [TeacherController::class, 'reports'])->name('teacher.reports');
        Route::get('/teacher/meeting/{meeting}/summary', [TeacherController::class, 'meetingSummary'])->name('teacher.meeting.summary');
        Route::get('/teacher/meeting/{meeting}/export', [TeacherController::class, 'exportMeetingSummary'])->name('teacher.meeting.export');
        
        // Meeting routes for teachers
        Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
        Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
        Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
        Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
        Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
        Route::post('/meetings/{meeting}/end', [MeetingController::class, 'end'])->name('meetings.end');
        Route::get('/teacher/meetings/{meeting}/join', [TeacherController::class, 'joinMeeting'])->name('teacher.meetings.join');
    });

    // Student routes
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
        Route::get('/student/meetings', [StudentController::class, 'meetings'])->name('student.meetings');
        Route::get('/student/camera', [StudentController::class, 'camera'])->name('student.camera');
        Route::get('/student/meetings/{meeting}/join', [StudentController::class, 'joinMeeting'])->name('student.meetings.join');
        Route::post('/meetings/{meeting}/focus', [FocusController::class, 'update'])->name('meetings.focus.update');
        Route::get('/student/focus-stats', [StudentController::class, 'focusStats'])->name('student.focus-stats');
        Route::get('/student/settings', [StudentController::class, 'settings'])->name('student.settings');
        Route::post('/meetings/{meeting}/leave', [StudentController::class, 'leaveMeeting'])->name('meetings.leave');
        
        // Save current student info for Python tracker
        Route::post('/student/save-current', function (Request $request) {
            $data = [
                'meeting_id' => $request->meeting_id,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name
            ];
            
            Storage::put('framework/sessions/current_student.json', json_encode($data));
            return response()->json(['success' => true]);
        })->name('student.save-current');
    });

    // Shared meeting routes (accessible by both teachers and students)
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    Route::post('/meetings/{meeting}/start', [MeetingController::class, 'start'])->name('meetings.start');

    // Focus log API endpoints
    Route::middleware(['web', 'api'])->group(function () {
        Route::post('/api/focus-logs', [FocusLogController::class, 'store']);
        Route::post('/api/meetings/{meeting}/focus', [FocusLogController::class, 'updateFocus'])->name('api.meetings.focus');
        Route::get('/api/meetings/{meeting}/analytics', [FocusLogController::class, 'getAnalytics'])->name('api.meetings.analytics');
        Route::post('/api/meetings/{meeting}/student-status', [FocusLogController::class, 'updateStudentStatus'])->name('api.meetings.student-status');
    });

    Route::post('/session/store-focus', [App\Http\Controllers\StudentController::class, 'storeFocus'])
        ->name('session.store-focus');

    Route::post('/broadcast-focus', [FocusController::class, 'broadcast'])
        ->name('focus.broadcast')
        ->middleware(['auth']);

    // Meeting routes
    Route::middleware(['auth'])->group(function () {
        // Student meeting routes
        Route::get('/student/meetings/{meeting}/join', [MeetingController::class, 'join'])
            ->name('student.meetings.join');
        
        Route::post('/student/meetings/{meeting}/join', [MeetingController::class, 'processJoin'])
            ->name('student.meetings.process-join');
            
        Route::post('/meetings/{meeting}/leave', [MeetingController::class, 'leave'])
            ->name('meetings.leave');
    });

    // Focus data routes
    Route::middleware(['auth'])->group(function () {
        Route::post('/focus-data/store', [FocusDataController::class, 'store'])
            ->name('focus-data.store');
        
        Route::get('/focus-data/{meeting}', [FocusDataController::class, 'getMeetingData'])
            ->name('focus-data.meeting');
    });

    // Focus data display route
    Route::get('/focus-data', [App\Http\Controllers\FocusDataController::class, 'index'])->name('focus-data.index');
});

// Health check endpoint
Route::get('/api/health', function() {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'environment' => app()->environment()
    ]);
});

require __DIR__.'/auth.php';
