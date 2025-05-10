<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingMessage;
use App\Models\FocusLog;
use App\Models\User;
use App\Models\Message;
use App\Models\Participant;
use App\Events\MeetingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;

class MeetingController extends Controller
{
    protected $pythonProcess = null;

    public function index(): View
    {
        $user = Auth::user();
        
        if ($user->role === 'teacher') {
            $meetings = Meeting::where('teacher_id', $user->id)->paginate(10);
        } else {
            $meetings = Meeting::whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->paginate(10);
        }
        
        return view('meetings.index', compact('meetings'));
    }

    public function create(): View
    {
        return view('meetings.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $meeting = Meeting::create([
            'teacher_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'scheduled'
        ]);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Meeting created successfully.');
    }

    public function show(Meeting $meeting): View
    {
        return view('meetings.show', compact('meeting'));
    }

    public function edit(Meeting $meeting): View
    {
        return view('meetings.edit', compact('meeting'));
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $meeting->update($validated);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Meeting updated successfully.');
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        $meeting->delete();
        return redirect()->route('meetings.index')
            ->with('success', 'Meeting deleted successfully.');
    }

    public function join(Meeting $meeting): View|JsonResponse|RedirectResponse
    {
        /** @var User|null */
        $user = Auth::user();
        if (!$user || !$meeting->canBeJoinedBy($user)) {
            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'You are not allowed to join this meeting.'
                ], 403);
            }
            return redirect()->back()->with('error', 'You are not allowed to join this meeting.');
        }

        // Record the join in meeting_participants
        $meeting->participants()->firstOrCreate([
            'user_id' => Auth::id()
        ]);

        // If it's an AJAX request, return JSON response
        if (request()->wantsJson()) {
            return response()->json([
                'message' => 'Successfully joined the meeting',
                'meeting' => $meeting
            ]);
        }

        // For regular requests, render the view
        return view('student.camera', [
            'meeting' => $meeting
        ]);
    }

    public function processJoin(Meeting $meeting): JsonResponse
    {
        /** @var User|null */
        $user = Auth::user();
        if (!$user || !$meeting->canBeJoinedBy($user)) {
            return response()->json([
                'message' => 'You are not allowed to join this meeting.'
            ], 403);
        }

        $participant = $meeting->participants()->firstOrCreate([
            'user_id' => Auth::id()
        ]);

        return response()->json([
            'message' => 'Successfully joined the meeting',
            'meeting' => $meeting,
            'participant' => $participant
        ]);
    }

    public function leave(Meeting $meeting): RedirectResponse
    {
        // Update participant record
        $participant = Participant::where('user_id', Auth::id())
            ->where('meeting_id', $meeting->id)
            ->first();

        if ($participant) {
            $participant->update(['left_at' => now()]);
        }

        broadcast(new MeetingEvent($meeting->id, 'user-left', [
            'userId' => Auth::id()
        ]));

        return redirect()->back()->with('success', 'Left meeting successfully.');
    }

    public function start(Meeting $meeting): RedirectResponse
    {
        $meeting->update(['status' => 'active']);
        return redirect()->back()->with('success', 'Meeting started successfully.');
    }

    public function end(Meeting $meeting): RedirectResponse
    {
        /** @var User|null */
        $user = Auth::user();
        if (!$user || !$meeting->canBeEndedBy($user)) {
            return redirect()->back()->with('error', 'You are not allowed to end this meeting.');
        }

        try {
            // Calculate and save final focus metrics for all participants
            $participants = $meeting->participants()
                ->with(['focusLogs' => function($query) use ($meeting) {
                    $query->where('meeting_id', $meeting->id);
                }])
                ->get();

            $meetingFocusStats = [
                'average' => 0,
                'highest' => 0,
                'lowest' => 100,
                'total_logs' => 0
            ];

            foreach ($participants as $participant) {
                try {
                    if ($participant->focusLogs->isNotEmpty()) {
                        // Calculate participant's focus metrics
                        $focusLogs = $participant->focusLogs;
                        $averageFocus = round($focusLogs->avg('focus_level'), 1);
                        $highestFocus = round($focusLogs->max('focus_level'), 1);
                        $lowestFocus = round($focusLogs->min('focus_level'), 1);
                        $totalLogs = $focusLogs->count();

                        // Update participant metrics
                        $participant->update([
                            'average_focus' => $averageFocus,
                            'highest_focus' => $highestFocus,
                            'lowest_focus' => $lowestFocus,
                            'total_focus_logs' => $totalLogs,
                            'left_at' => now()
                        ]);

                        // Update meeting stats
                        $meetingFocusStats['total_logs'] += $totalLogs;
                        $meetingFocusStats['average'] += ($averageFocus * $totalLogs); // Weighted average
                        $meetingFocusStats['highest'] = max($meetingFocusStats['highest'], $highestFocus);
                        $meetingFocusStats['lowest'] = min($meetingFocusStats['lowest'], $lowestFocus);
                    } else {
                        // No focus logs for this participant
                        $participant->update([
                            'average_focus' => 0,
                            'highest_focus' => 0,
                            'lowest_focus' => 0,
                            'total_focus_logs' => 0,
                            'left_at' => now()
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error("Error updating participant {$participant->id}: " . $e->getMessage());
                }
            }

            // Calculate final meeting average
            if ($meetingFocusStats['total_logs'] > 0) {
                $meetingFocusStats['average'] = round($meetingFocusStats['average'] / $meetingFocusStats['total_logs'], 1);
            }

            // Update meeting status and focus stats
            $meeting->update([
                'status' => 'completed',
                'end_time' => now(),
                'average_focus' => $meetingFocusStats['average'],
                'highest_focus' => $meetingFocusStats['highest'],
                'lowest_focus' => $meetingFocusStats['lowest'],
                'total_focus_logs' => $meetingFocusStats['total_logs']
            ]);

            try {
                // Broadcast meeting end event
                broadcast(new MeetingEvent(
                    $meeting->id,
                    'meeting-ended',
                    ['meetingId' => $meeting->id]
                ))->toOthers();
            } catch (\Exception $e) {
                \Log::error("Broadcasting error in meeting {$meeting->id}: " . $e->getMessage());
                // Continue execution even if broadcasting fails
            }

            return redirect()->route('teacher.meeting.summary', $meeting)
                ->with('success', 'Meeting ended successfully. View the summary below.');

        } catch (\Exception $e) {
            \Log::error("Error ending meeting {$meeting->id}: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'An error occurred while ending the meeting. Please try again.');
        }
    }

    public function events(Meeting $meeting)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        $lastEventId = request()->header('Last-Event-ID', 0);

        $callback = function () use ($meeting, $lastEventId) {
            // Send initial participants list
            $participants = $meeting->participants()
                ->whereNull('left_at')
                ->with('user')
                ->get()
                ->map(function ($participant) {
                    return [
                        'userId' => $participant->user_id,
                        'userName' => $participant->user->name,
                        'userRole' => $participant->user->role
                    ];
                });

            echo "event: participants\n";
            echo "data: " . json_encode($participants) . "\n\n";

            // Send recent messages
            $messages = $meeting->messages()
                ->where('id', '>', $lastEventId)
                ->with('user')
                ->get()
                ->map(function ($message) {
                    return [
                        'userId' => $message->user_id,
                        'userName' => $message->user->name,
                        'content' => $message->content,
                        'time' => $message->created_at->toTimeString()
                    ];
                });

            foreach ($messages as $message) {
                echo "event: chat-message\n";
                echo "data: " . json_encode($message) . "\n\n";
            }

            // Keep connection alive
            while (true) {
                if (connection_aborted()) {
                    break;
                }

                echo ": keepalive\n\n";
                ob_flush();
                flush();
                sleep(1);
            }
        };

        return $response->setCallback($callback);
    }

    public function getAnalytics(Meeting $meeting): JsonResponse
    {
        try {
            // Get active participants (those who haven't left)
            $activeParticipants = $meeting->participants()
                ->whereNull('left_at')
                ->with(['user', 'focusLogs' => function($query) {
                    $query->latest()->take(10);
                }])
                ->get();

            // Calculate average focus for all active participants
            $participantCount = $activeParticipants->count();
            $focusDistribution = ['high' => 0, 'medium' => 0, 'low' => 0, 'total' => $participantCount];
            $totalFocus = 0.0;

            $students = $activeParticipants->map(function($participant) use (&$totalFocus) {
                $recentFocusLogs = $participant->focusLogs;
                $focusLevel = $recentFocusLogs->avg('focus_level') ?? 0;
                $lastUpdate = $recentFocusLogs->first()?->created_at?->diffForHumans() ?? 'Never';
                $isActive = $recentFocusLogs->first()?->created_at->gt(now()->subMinutes(5)) ?? false;

                // Add to total focus for average calculation
                $totalFocus += $focusLevel;

                return [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                    'focusLevel' => round($focusLevel, 2),
                    'isActive' => $isActive,
                    'activeTime' => $participant->created_at->diffForHumans(null, true),
                    'lastUpdate' => $lastUpdate
                ];
            });

            // Calculate focus distribution
            foreach ($students as $student) {
                if ($student['focusLevel'] >= 70) {
                    $focusDistribution['high']++;
                } elseif ($student['focusLevel'] >= 40) {
                    $focusDistribution['medium']++;
                } else {
                    $focusDistribution['low']++;
                }
            }

            $averageFocus = $participantCount > 0 ? round($totalFocus / $participantCount, 2) : 0;

            return response()->json([
                'averageFocus' => $averageFocus,
                'activeStudents' => $participantCount,
                'focusDistribution' => $focusDistribution,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getAnalytics: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch analytics'], 500);
        }
    }

    public function processFrame(Request $request, Meeting $meeting)
    {
        try {
            $request->validate([
                'frame' => 'required|file|max:10240', // 10MB max
                'userId' => 'required|integer'
            ]);

            $frame = $request->file('frame');
            $framePath = $frame->store('frames', 'public');
            $fullPath = storage_path('app/public/' . $framePath);

            // Send frame to Python service using cURL
            $curl = curl_init();
            $postData = [
                'frame' => new \CURLFile($fullPath),
                'user_id' => $request->userId
            ];

            curl_setopt_array($curl, [
                CURLOPT_URL => 'http://127.0.0.1:5000/process-frame',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode !== 200) {
                throw new \Exception('Python service returned error: ' . $response);
            }

            $result = json_decode($response, true);

            if (!$result || !isset($result['focusScore'])) {
                throw new \Exception('Invalid focus score data');
            }

            $focusScore = $result['focusScore'];
            $sessionTime = now()->diffInSeconds($meeting->start_time);

            // Create focus log
            $meeting->focusLogs()->create([
                'user_id' => $request->userId,
                'focus_level' => $focusScore,
                'session_time' => $sessionTime
            ]);

            // Broadcast focus update
            broadcast(new MeetingEvent($meeting->id, 'focus-update', [
                'userId' => $request->userId,
                'focusScore' => $focusScore
            ]))->toOthers();

            // Clean up the frame file
            Storage::disk('public')->delete($framePath);

            return response()->json([
                'success' => true,
                'focusScore' => $focusScore
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing frame: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to process frame: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendMessage(Request $request, Meeting $meeting)
    {
        $request->validate([
            'userId' => 'required|exists:users,id',
            'userName' => 'required|string',
            'content' => 'required|string'
        ]);

        try {
            $message = new MeetingMessage([
                'user_id' => $request->userId,
                'meeting_id' => $meeting->id,
                'content' => $request->content
            ]);

            $meeting->messages()->save($message);

            // Broadcast the message
            broadcast(new MeetingEvent($meeting->id, 'chat-message', [
                'userId' => $request->userId,
                'userName' => $request->userName,
                'content' => $request->content,
                'timestamp' => now()
            ]))->toOthers();

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    public function toggleMedia(Request $request, Meeting $meeting)
    {
        $request->validate([
            'userId' => 'required|exists:users,id',
            'type' => 'required|in:video,audio,screen',
            'enabled' => 'required|boolean'
        ]);

        try {
            // Broadcast media update
            broadcast(new MeetingEvent($meeting->id, 'media-update', [
                'userId' => $request->userId,
                'type' => $request->type,
                'enabled' => $request->enabled
            ]))->toOthers();

            return response()->json([
                'success' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling media: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle media'
            ], 500);
        }
    }

    public function toggleChat(Request $request, Meeting $meeting)
    {
        try {
            $request->validate([
                'userId' => 'required|exists:users,id',
                'enabled' => 'required|boolean'
            ]);

            // Broadcast chat visibility toggle event
            broadcast(new MeetingEvent($meeting->id, 'chat-toggled', [
                'userId' => $request->userId,
                'enabled' => $request->enabled
            ]))->toOthers();

            return response()->json([
                'success' => true,
                'chatEnabled' => $request->enabled
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle chat'
            ], 500);
        }
    }

    protected function startPythonProcess()
    {
        try {
            $pythonPath = config('app.python_path', 'python');
            $scriptPath = base_path('python_model/app.py');
            
            if (!file_exists($scriptPath)) {
                Log::error('Python script not found at: ' . $scriptPath);
                throw new \Exception('Python script not found');
            }

            $this->pythonProcess = new Process([$pythonPath, $scriptPath]);
            $this->pythonProcess->setTimeout(3600); // 1 hour timeout
            $this->pythonProcess->start();
            
            if (!$this->pythonProcess->isRunning()) {
                Log::error('Failed to start Python process: ' . $this->pythonProcess->getErrorOutput());
                throw new ProcessFailedException($this->pythonProcess);
            }

            Log::info('Python process started successfully');
        } catch (\Exception $e) {
            Log::error('Error starting Python process: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Return a JSON list of meetings for API consumption
     */
    public function indexApi(): JsonResponse
    {
        $user = Auth::user();
        
        // Get meetings based on user role
        if ($user->role === 'teacher') {
            $meetings = Meeting::where('teacher_id', $user->id)
                ->select('id', 'title', 'start_time', 'end_time', 'status')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $meetings = Meeting::whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->select('id', 'title', 'start_time', 'end_time', 'status')
            ->orderBy('created_at', 'desc')
            ->get();
        }
        
        return response()->json($meetings);
    }
} 