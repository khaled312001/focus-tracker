<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FocusLog;
use App\Models\Meeting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FocusMetricsController extends Controller
{
    public function show($meetingId)
    {
        try {
            // Get the latest focus logs for each student in the meeting
            $focusData = FocusLog::where('meeting_id', $meetingId)
                ->select('student_id', 
                        DB::raw('MAX(created_at) as last_update'),
                        DB::raw('AVG(focus_level) as average_focus'))
                ->groupBy('student_id')
                ->get();

            // Get student names from the users table
            $students = [];
            foreach ($focusData as $data) {
                $student = DB::table('users')
                    ->where('id', $data->student_id)
                    ->first();

                if ($student) {
                    $students[] = [
                        'student_id' => $data->student_id,
                        'student_name' => $student->name,
                        'focus_level' => round($data->average_focus, 2),
                        'last_update' => $data->last_update
                    ];
                }
            }

            // Calculate overall metrics
            $overallStats = [
                'averageFocus' => $students ? collect($students)->avg('focus_level') : 0,
                'activeStudents' => count($students),
                'distribution' => [
                    'high' => collect($students)->filter(fn($s) => $s['focus_level'] >= 70)->count(),
                    'medium' => collect($students)->filter(fn($s) => $s['focus_level'] >= 40 && $s['focus_level'] < 70)->count(),
                    'low' => collect($students)->filter(fn($s) => $s['focus_level'] < 40)->count(),
                ]
            ];

            return response()->json($students);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch focus metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function summary($meetingId)
    {
        try {
            // Get focus data for the last hour
            $lastHour = Carbon::now()->subHour();
            
            $focusData = FocusLog::where('meeting_id', $meetingId)
                ->where('created_at', '>=', $lastHour)
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:00") as time_interval'),
                    DB::raw('AVG(focus_level) as average_focus'),
                    DB::raw('COUNT(DISTINCT student_id) as student_count')
                )
                ->groupBy('time_interval')
                ->orderBy('time_interval', 'asc')
                ->get();

            return response()->json([
                'timeIntervals' => $focusData->pluck('time_interval'),
                'averageFocus' => $focusData->pluck('average_focus'),
                'studentCounts' => $focusData->pluck('student_count')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch focus summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 