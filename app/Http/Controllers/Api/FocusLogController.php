<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FocusLog;
use App\Models\Meeting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FocusLogController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'focus_level' => 'required|numeric|min:0|max:100',
        ]);

        $focusLog = FocusLog::create([
            'student_id' => Auth::id(),
            'meeting_id' => $request->meeting_id,
            'focus_level' => $request->focus_level,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $focusLog,
        ]);
    }

    public function updateFocus(Request $request, Meeting $meeting)
    {
        $request->validate([
            'focus_level' => 'required|numeric|min:0|max:100',
        ]);

        $focusLog = FocusLog::create([
            'student_id' => Auth::id(),
            'meeting_id' => $meeting->id,
            'focus_level' => $request->focus_level,
        ]);

        // Calculate average focus for the last 5 minutes
        $averageFocus = FocusLog::where('meeting_id', $meeting->id)
            ->where('student_id', Auth::id())
            ->where('created_at', '>=', now()->subMinutes(5))
            ->avg('focus_level');

        return response()->json([
            'status' => 'success',
            'data' => [
                'focus_log' => $focusLog,
                'average_focus' => round($averageFocus, 2),
            ],
        ]);
    }

    public function getAnalytics(Meeting $meeting)
    {
        try {
            // Get overall meeting analytics
            $analytics = FocusLog::where('meeting_id', $meeting->id)
                ->select(
                    DB::raw('COALESCE(AVG(focus_level), 0) as average_focus'),
                    DB::raw('COUNT(DISTINCT student_id) as total_students')
                )
                ->first();

            // Ensure we have valid data
            if (!$analytics) {
                $analytics = (object)[
                    'average_focus' => 0,
                    'total_students' => 0
                ];
            }

            // Get focus distribution
            $distribution = [
                'high' => FocusLog::where('meeting_id', $meeting->id)
                    ->where('focus_level', '>=', 70)
                    ->count(),
                'medium' => FocusLog::where('meeting_id', $meeting->id)
                    ->whereBetween('focus_level', [40, 69])
                    ->count(),
                'low' => FocusLog::where('meeting_id', $meeting->id)
                    ->where('focus_level', '<', 40)
                    ->count(),
            ];

            // Get per-student analytics
            $studentAnalytics = FocusLog::where('meeting_id', $meeting->id)
                ->join('users', 'focus_logs.student_id', '=', 'users.id')
                ->select(
                    'users.name',
                    'users.id as user_id',
                    DB::raw('COALESCE(AVG(focus_level), 0) as average_focus'),
                    DB::raw('MAX(focus_logs.created_at) as last_update')
                )
                ->groupBy('users.id', 'users.name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'overall' => $analytics,
                    'distribution' => $distribution,
                    'students' => $studentAnalytics,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getAnalytics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching analytics data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStudentStatus(Request $request, Meeting $meeting)
    {
        $request->validate([
            'status' => 'required|string|in:joined,left',
            'student_id' => 'required|exists:users,id',
        ]);

        // Log student status
        $status = [
            'meeting_id' => $meeting->id,
            'user_id' => $request->student_id,
            'status' => $request->status,
            'timestamp' => now(),
        ];

        // You might want to store this in a separate table
        // For now, we'll just return the status
        return response()->json([
            'status' => 'success',
            'data' => $status,
        ]);
    }
}
