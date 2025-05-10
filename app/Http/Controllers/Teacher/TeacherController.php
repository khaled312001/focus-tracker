<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\User;
use App\Models\FocusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $activeMeetings = Meeting::where('teacher_id', $user->id)
            ->where('status', 'active')
            ->with('students')
            ->get();
        
        $totalMeetings = Meeting::where('teacher_id', $user->id)->count();
        $activeStudents = User::where('role', 'student')
            ->whereHas('studentMeetings', function($query) use ($user) {
                $query->where('teacher_id', $user->id)
                      ->where('status', 'active');
            })->count();
        
        $averageFocus = FocusLog::whereHas('meeting', function($query) use ($user) {
            $query->where('teacher_id', $user->id);
        })->avg('focus_level') ?? 0;
        
        // Get focus level distribution for pie chart
        $focusDistribution = FocusLog::whereHas('meeting', function($query) use ($user) {
            $query->where('teacher_id', $user->id);
        })
        ->select(
            DB::raw('CASE 
                WHEN focus_level >= 80 THEN "High Focus (80-100%)"
                WHEN focus_level >= 60 THEN "Good Focus (60-79%)"
                WHEN focus_level >= 40 THEN "Moderate Focus (40-59%)"
                WHEN focus_level >= 20 THEN "Low Focus (20-39%)"
                ELSE "Very Low Focus (0-19%)"
            END as focus_category'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('focus_category')
        ->orderBy(DB::raw('MIN(focus_level)'), 'desc')
        ->get();
        
        // Prepare data for charts
        $focusData = [];
        $focusLabels = [];
        $focusDistributionData = [];
        $focusDistributionLabels = [];
        
        // Get focus data for the last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $focusLabels[] = $date->format('M d');
            
            $avgFocus = FocusLog::whereHas('meeting', function($query) use ($user) {
                $query->where('teacher_id', $user->id);
            })
            ->whereDate('created_at', $date)
            ->avg('focus_level') ?? 0;
            
            $focusData[] = round($avgFocus, 1);
        }
        
        // Prepare data for focus distribution pie chart
        $defaultCategories = [
            'High Focus (80-100%)' => 0,
            'Good Focus (60-79%)' => 0,
            'Moderate Focus (40-59%)' => 0,
            'Low Focus (20-39%)' => 0,
            'Very Low Focus (0-19%)' => 0
        ];
        
        // Initialize with default values
        $focusDistributionLabels = array_keys($defaultCategories);
        $focusDistributionData = array_values($defaultCategories);
        
        // Update with actual data if available
        if ($focusDistribution->isNotEmpty()) {
            $focusDistributionLabels = [];
            $focusDistributionData = [];
            
            foreach ($focusDistribution as $item) {
                $focusDistributionLabels[] = $item->focus_category;
                $focusDistributionData[] = $item->count;
            }
        }
        
        // Get student performance data
        $performanceData = [];
        $performanceLabels = [];
        
        // Get all students with their focus logs and meetings
        $students = User::where('role', 'student')
            ->whereHas('studentMeetings', function($query) use ($user) {
                $query->where('teacher_id', $user->id);
            })
            ->with(['focusLogs' => function($query) use ($user) {
                $query->whereHas('meeting', function($q) use ($user) {
                    $q->where('teacher_id', $user->id);
                });
            }, 'studentMeetings' => function($query) use ($user) {
                $query->where('teacher_id', $user->id);
            }])
            ->get();
        
        foreach ($students as $student) {
            $performanceLabels[] = $student->name;
            $avgFocus = $student->focusLogs->avg('focus_level') ?? 0;
            $performanceData[] = round($avgFocus, 1);
        }
        
        return view('teacher.dashboard', compact(
            'user',
            'activeMeetings',
            'totalMeetings',
            'activeStudents',
            'averageFocus',
            'focusData',
            'focusLabels',
            'performanceData',
            'performanceLabels',
            'students'
        ));
    }

    public function joinMeeting(Meeting $meeting)
    {
        if ($meeting->status !== 'active') {
            // Start the meeting if it's not active
            $meeting->update(['status' => 'active']);
        }

        // Load necessary relationships
        $meeting->load(['teacher', 'students']);

        // Create or update participant record for the teacher
        $meeting->participants()->updateOrCreate(
            [
                'user_id' => Auth::id(),
                'meeting_id' => $meeting->id
            ],
            [
                'joined_at' => now()
            ]
        );

        // Pass meeting data to the view
        return view('teacher.meeting', [
            'meeting' => $meeting,
            'user' => Auth::user()
        ]);
    }

    public function meetingDetails(Meeting $meeting)
    {
        $students = $meeting->students()->with('focusStats')->get();
        $focusLogs = FocusLog::where('meeting_id', $meeting->id)
            ->with('student')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalStudents = $students->count();
        $presentStudents = $students->where('pivot.is_present', true)->count();
        $averageFocus = $focusLogs->avg('focus_level') ?? 0;

        return view('teacher.meeting-details', compact(
            'meeting',
            'students',
            'focusLogs',
            'totalStudents',
            'presentStudents',
            'averageFocus'
        ));
    }

    public function meetingSummary(Meeting $meeting)
    {
        // Verify the teacher owns this meeting
        if ($meeting->teacher_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Get all participants (including teacher) who participated in the meeting
        $participants = $meeting->participants()
            ->with(['user', 'focusLogs' => function($query) use ($meeting) {
                $query->where('meeting_id', $meeting->id);
            }])
            ->get();

        // Calculate focus statistics
        $allFocusLogs = $participants->flatMap->focusLogs;
        $focusStats = [
            'average' => $allFocusLogs->isNotEmpty() ? round($allFocusLogs->avg('focus_level'), 1) : 0,
            'highest' => $allFocusLogs->isNotEmpty() ? round($allFocusLogs->max('focus_level'), 1) : 0,
            'lowest' => $allFocusLogs->isNotEmpty() ? round($allFocusLogs->min('focus_level'), 1) : 0,
            'total_logs' => $allFocusLogs->count(),
        ];

        // Calculate duration
        $duration = $meeting->end_time ? $meeting->end_time->diffInMinutes($meeting->start_time) : 0;

        // Get focus distribution
        $focusDistribution = $allFocusLogs
            ->groupBy(function($log) {
                if ($log->focus_level >= 80) return 'High (80-100%)';
                if ($log->focus_level >= 60) return 'Good (60-79%)';
                if ($log->focus_level >= 40) return 'Moderate (40-59%)';
                if ($log->focus_level >= 20) return 'Low (20-39%)';
                return 'Very Low (0-19%)';
            })
            ->map->count();

        return view('teacher.meeting-summary', compact(
            'meeting',
            'participants',
            'focusStats',
            'duration',
            'focusDistribution'
        ));
    }
} 