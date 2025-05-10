<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\FocusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FocusTrackingController extends Controller
{
    public function startTracking(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'student_id' => 'required|exists:users,id'
        ]);

        try {
            // Start Python focus tracking process
            $response = Http::post('http://localhost:5000/start-session', [
                'student_id' => $request->student_id,
                'meeting_id' => $request->meeting_id
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Focus tracking started successfully'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start focus tracking'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Focus tracking start error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function stopTracking(Request $request)
    {
        try {
            $response = Http::post('http://localhost:5000/stop-session');

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Focus tracking stopped successfully'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to stop focus tracking'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Focus tracking stop error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function getMeetingMetrics($meetingId)
    {
        try {
            $meeting = Meeting::findOrFail($meetingId);
            
            $participants = $meeting->students()->with(['focusLogs' => function ($query) use ($meeting) {
                $query->where('meeting_id', $meeting->id)->latest();
            }])->get()->map(function ($student) {
                $latestLog = $student->focusLogs->first();
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'average_focus' => $latestLog ? $latestLog->focus_level : 0,
                    'last_update' => $latestLog ? $latestLog->created_at : null
                ];
            });

            // Calculate focus distribution
            $distribution = [
                'high' => $participants->filter(fn($p) => $p['average_focus'] >= 80)->count(),
                'medium' => $participants->filter(fn($p) => $p['average_focus'] >= 40 && $p['average_focus'] < 80)->count(),
                'low' => $participants->filter(fn($p) => $p['average_focus'] < 40)->count()
            ];

            return response()->json([
                'meeting_id' => $meeting->id,
                'students' => $participants,
                'averageFocus' => $participants->avg('average_focus'),
                'activeStudents' => $participants->count(),
                'distribution' => $distribution
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting meeting metrics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get meeting metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeFocusLog(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'meeting_id' => 'required|exists:meetings,id',
            'focus_level' => 'required|numeric|min:0|max:100',
            'duration' => 'required|integer|min:0'
        ]);

        try {
            FocusLog::create([
                'student_id' => $request->student_id,
                'meeting_id' => $request->meeting_id,
                'focus_level' => $request->focus_level,
                'duration' => $request->duration
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Focus log stored successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Focus log storage error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to store focus log'
            ], 500);
        }
    }
} 