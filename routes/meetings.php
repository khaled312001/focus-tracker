<?php

use App\Http\Controllers\MeetingController;
use App\Http\Controllers\Teacher\TeacherController;
use App\Http\Controllers\StudentController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

// Meetings routes (accessible by both teachers and students)
Route::middleware(['auth'])->group(function () {
    // Basic meeting routes
    Route::resource('meetings', MeetingController::class)->except(['create', 'store', 'edit', 'update', 'destroy']);
    
    // Teacher-only meeting routes
    Route::middleware(CheckRole::class . ':teacher')->group(function () {
        Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
        Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
        Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
        Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
        Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
        Route::post('/meetings/{meeting}/start', [MeetingController::class, 'start'])->name('meetings.start');
        Route::post('/meetings/{meeting}/end', [MeetingController::class, 'end'])->name('meetings.end');
        
        // Teacher meeting controls
        Route::get('/teacher/meetings/{meeting}/join', [TeacherController::class, 'joinMeeting'])->name('teacher.meetings.join');
        Route::post('/meetings/{meeting}/end', [TeacherController::class, 'endMeeting'])->name('teacher.meetings.end');
        Route::post('/meetings/{meeting}/participants/update', [TeacherController::class, 'updateParticipant'])->name('teacher.meetings.participants.update');
        Route::post('/meetings/{meeting}/participants/remove', [TeacherController::class, 'removeParticipant'])->name('teacher.meetings.participants.remove');
        Route::post('/meetings/{meeting}/toggle-video', [TeacherController::class, 'toggleVideo'])->name('teacher.meetings.toggle-video');
        Route::post('/meetings/{meeting}/toggle-audio', [TeacherController::class, 'toggleAudio'])->name('teacher.meetings.toggle-audio');
        Route::post('/meetings/{meeting}/toggle-screen-share', [TeacherController::class, 'toggleScreenShare'])->name('teacher.meetings.toggle-screen-share');
    });

    // Student meeting routes
    Route::middleware(CheckRole::class . ':student')->group(function () {
        Route::get('/meetings/{meeting}/join', [MeetingController::class, 'join'])->name('meetings.join');
        Route::post('/meetings/{meeting}/leave', [StudentController::class, 'leaveMeeting'])->name('meetings.leave');
        Route::post('/meetings/{meeting}/toggle-video', [StudentController::class, 'toggleVideo'])->name('meetings.toggle-video');
        Route::post('/meetings/{meeting}/toggle-audio', [StudentController::class, 'toggleAudio'])->name('meetings.toggle-audio');
        Route::post('/meetings/{meeting}/toggle-chat', [StudentController::class, 'toggleChat'])->name('meetings.toggle-chat');
        Route::post('/meetings/{meeting}/send-message', [StudentController::class, 'sendMessage'])->name('meetings.send-message');
    });

    // Real-time features
    Route::get('/meetings/{meeting}/events', [MeetingController::class, 'events'])->name('meetings.events');
    Route::post('/meetings/{meeting}/frame', [MeetingController::class, 'processFrame'])->name('meetings.frame');
    Route::post('/meetings/{meeting}/chat', [MeetingController::class, 'sendMessage'])->name('meetings.chat');
}); 