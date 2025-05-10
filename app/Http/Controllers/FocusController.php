<?php

namespace App\Http\Controllers;

use App\Events\FocusUpdated;
use App\Models\FocusLog;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FocusController extends Controller
{
    public function update(Request $request, Meeting $meeting)
    {
        $focusLevel = $request->input('focus_level');
        $student = Auth::user();

        // Log the focus level
        FocusLog::create([
            'meeting_id' => $meeting->id,
            'student_id' => $student->id,
            'focus_level' => $focusLevel,
        ]);

        // Broadcast the focus update
        broadcast(new FocusUpdated(
            $meeting->id,
            $student->id,
            $focusLevel,
            $student->name
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    public function getMetrics(Meeting $meeting)
    {
        // Get all focus logs for the meeting
        $focusLogs = FocusLog::where('meeting_id', $meeting->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate average focus level
        $averageFocus = $focusLogs->avg('focus_level');

        // Group focus logs by student
        $studentMetrics = $focusLogs->groupBy('student_id')
            ->map(function ($logs) {
                return [
                    'average_focus' => $logs->avg('focus_level'),
                    'total_logs' => $logs->count(),
                    'last_update' => $logs->max('created_at'),
                ];
            });

        return response()->json([
            'meeting_id' => $meeting->id,
            'average_focus' => $averageFocus,
            'student_metrics' => $studentMetrics,
            'total_logs' => $focusLogs->count(),
            'last_update' => $focusLogs->max('created_at'),
        ]);
    }

    public function getStudentMetrics(Meeting $meeting, $student)
    {
        // Get focus logs for specific student in the meeting
        $focusLogs = FocusLog::where('meeting_id', $meeting->id)
            ->where('student_id', $student)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate metrics
        $metrics = [
            'student_id' => $student,
            'meeting_id' => $meeting->id,
            'average_focus' => $focusLogs->avg('focus_level'),
            'total_logs' => $focusLogs->count(),
            'last_update' => $focusLogs->max('created_at'),
            'focus_history' => $focusLogs->map(function ($log) {
                return [
                    'focus_level' => $log->focus_level,
                    'timestamp' => $log->created_at,
                ];
            }),
        ];

        return response()->json($metrics);
    }
} 