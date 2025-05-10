<?php

namespace App\Http\Controllers;

use App\Events\MeetingEvent;
use App\Models\Meeting;
use App\Models\FocusLog;
use App\Models\Participant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Show the student dashboard.
     */
    public function dashboard(): View
    {
        $user = Auth::user();

        // Get active meetings
        $activeMeetings = Meeting::whereHas('students', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'active')->get();

        // Get upcoming meetings (scheduled but not started)
        $upcomingMeetings = Meeting::whereHas('students', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'scheduled')
          ->where('start_time', '>', Carbon::now())
          ->orderBy('start_time', 'asc')
          ->get();

        // Get past meetings with average focus scores
        $pastMeetings = Meeting::whereHas('students', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'ended')
          ->orderBy('end_time', 'desc')
          ->limit(6)
          ->get();

        // Calculate average focus for each past meeting
        foreach ($pastMeetings as $meeting) {
            $meeting->averageFocus = FocusLog::where('meeting_id', $meeting->id)
                ->where('user_id', $user->id)
                ->avg('focus_level') ?? 0;
        }

        return view('student.dashboard', compact('activeMeetings', 'upcomingMeetings', 'pastMeetings'));
    }

    public function joinMeeting(Meeting $meeting): View|RedirectResponse
    {
        if ($meeting->status !== 'active') {
            return redirect()->route('student.meetings')
                ->with('error', 'This meeting is not currently active.');
        }

        $user = Auth::user();

        // Create or update participant record
        $meeting->participants()->updateOrCreate(
            [
                'user_id' => $user->id,
                'meeting_id' => $meeting->id
            ],
            [
                'joined_at' => now()
            ]
        );

        // Launch the Python focus tracker script
        try {
            $pythonScriptPath = base_path('python_model/test_focus.py');
            $pythonExecutable = 'python';

            // Ensure the script path exists
            if (!file_exists($pythonScriptPath)) {
                Log::error("Python script not found at: " . $pythonScriptPath);
                return redirect()->back()->with('error', 'Focus tracker component is missing.');
            }

            // Save current session data for Python script
            $sessionData = [
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'timestamp' => time(),
                'session_id' => session()->getId()
            ];
            
            $sessionFile = storage_path('app/focus_session.json');
            file_put_contents($sessionFile, json_encode($sessionData));

            // Build the command - now without arguments as Python will read from file
            $command = sprintf(
                'start "Python Focus Tracker" cmd /c "%s "%s""',
                $pythonExecutable,
                $pythonScriptPath
            );

            // Execute the command
            pclose(popen($command, 'r'));

            Log::info("Launched Python focus tracker", [
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'session_file' => $sessionFile
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to launch Python focus tracker", [
                'error' => $e->getMessage(),
                'meeting_id' => $meeting->id,
                'user_id' => $user->id
            ]);
            return redirect()->back()->with('error', 'An error occurred while launching the focus tracker.');
        }

        // Return the meeting view
        return view('student.meeting', [
            'meeting' => $meeting,
            'user' => $user
        ]);
    }

    public function leaveMeeting(Meeting $meeting): RedirectResponse
    {
        $participant = $meeting->participants()
            ->where('user_id', Auth::id())
            ->first();

        if ($participant) {
            $participant->update(['left_at' => now()]);
        }

        return redirect()->route('student.dashboard')
            ->with('success', 'You have left the meeting.');
    }

    /**
     * Show the student's meetings.
     */
    public function meetings(): View
    {
        $user = Auth::user();
        
        // Get meetings the student is enrolled in
        $meetings = Meeting::whereHas('students', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->orderBy('created_at', 'desc')->get();

        // Get available meetings (active meetings that the student is not enrolled in)
        $availableMeetings = Meeting::whereDoesntHave('students', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'active')
          ->orderBy('start_time', 'asc')
          ->get();

        return view('student.meetings', compact('meetings', 'availableMeetings'));
    }

    /**
     * Open camera for focus tracking.
     */
    public function openCamera(Meeting $meeting = null): View|RedirectResponse
    {
        if (!$meeting) {
            return view('student.camera');
        }

        // Check if student is part of this meeting
        $user = Auth::user();
        $isPresent = $meeting->students()
            ->where('user_id', $user->id)
            ->where('is_present', true)
            ->exists();

        if (!$isPresent) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You are not part of this meeting.');
        }

        return view('student.camera', compact('meeting'));
    }

    public function storeFocus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'meetingId' => 'required|exists:meetings,id',
            'focusScore' => 'required|numeric|min:0|max:100',
            'sessionTime' => 'required|integer|min:0'
        ]);

        FocusLog::create([
            'user_id' => Auth::id(),
            'meeting_id' => $validated['meetingId'],
            'focus_level' => $validated['focusScore'],
            'session_time' => $validated['sessionTime']
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Show the camera test view.
     */
    public function camera(Meeting $meeting = null)
    {
        if (!$meeting) {
            return view('student.camera', ['meeting' => null]);
        }

        // Check if student is part of this meeting
        $user = Auth::user();
        $isPresent = $meeting->participants()
            ->where('user_id', $user->id)
            ->where('is_present', true)
            ->exists();

        if (!$isPresent) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You are not part of this meeting.');
        }

        return view('student.camera', compact('meeting'));
    }

    public function toggleVideo(Request $request, Meeting $meeting)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        // Broadcast video toggle event
        broadcast(new MeetingEvent($meeting->id, 'video_toggled', [
            'student_id' => Auth::id(),
            'enabled' => $request->enabled,
        ]))->toOthers();

        return response()->json(['success' => true]);
    }

    public function toggleAudio(Request $request, Meeting $meeting)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        // Broadcast audio toggle event
        broadcast(new MeetingEvent($meeting->id, 'audio_toggled', [
            'student_id' => Auth::id(),
            'enabled' => $request->enabled,
        ]))->toOthers();

        return response()->json(['success' => true]);
    }

    public function toggleChat(Request $request, Meeting $meeting)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        // Broadcast chat visibility toggle event
        broadcast(new MeetingEvent($meeting->id, 'chat_toggled', [
            'student_id' => Auth::id(),
            'enabled' => $request->enabled,
        ]))->toOthers();

        return response()->json([
            'success' => true,
            'chatEnabled' => $request->enabled
        ]);
    }

    public function sendMessage(Request $request, Meeting $meeting)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        // Broadcast chat message event
        broadcast(new MeetingEvent($meeting->id, 'chat_message', [
            'student_id' => Auth::id(),
            'student_name' => Auth::user()->name,
            'message' => $request->message,
        ]))->toOthers();

        return response()->json(['success' => true]);
    }

    public function focusStats(): View
    {
        $user = Auth::user();
        $focusLogs = FocusLog::where('student_id', $user->id)
            ->with('meeting')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate average focus level
        $averageFocus = $focusLogs->avg('focus_level') ?? 0;

        // Get focus level distribution
        $focusDistribution = $focusLogs->groupBy(function($log) {
            if ($log->focus_level >= 80) return 'High Focus (80-100%)';
            if ($log->focus_level >= 60) return 'Good Focus (60-79%)';
            if ($log->focus_level >= 40) return 'Moderate Focus (40-59%)';
            if ($log->focus_level >= 20) return 'Low Focus (20-39%)';
            return 'Very Low Focus (0-19%)';
        })->map->count();

        // Get focus trend data (last 7 days)
        $focusTrend = $focusLogs->groupBy(function($log) {
            return $log->created_at->format('Y-m-d');
        })->map(function($logs) {
            return round($logs->avg('focus_level'), 2);
        })->take(7);

        return view('student.focus-stats', compact('focusLogs', 'averageFocus', 'focusDistribution', 'focusTrend'));
    }

    public function settings(): View
    {
        $user = Auth::user();
        return view('student.settings', compact('user'));
    }
} 