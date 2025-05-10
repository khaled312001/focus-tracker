<?php

namespace App\Http\Controllers;

use App\Models\FocusLog;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class FocusLogController extends Controller
{
    public function __construct()
    {
        // Exempt the API routes from CSRF protection, but keep it for web routes
        $this->middleware('web')->except(['store', 'storeDirectly']);
        
        // Apply API middleware to API routes
        $this->middleware('api')->only(['store', 'storeDirectly']);
    }

    public function store(Request $request)
    {
        // Log request details for debugging
        LaravelLog::info('Focus log storage request', [
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'data' => $request->all()
        ]);

        try {
            // Get request data - handle both JSON and form data
            $meetingId = $request->input('meetingId', $request->input('meeting_id'));
            $studentId = $request->input('userId', $request->input('user_id', $request->input('studentId', $request->input('student_id'))));
            $focusLevel = $request->input('focusScore', $request->input('focus_score', $request->input('focusLevel', $request->input('focus_level', 0))));
            $timestamp = $request->input('timestamp');

            // Validate required fields
            if (empty($meetingId)) {
                return response()->json(['error' => 'Missing meeting ID'], 422);
            }

            if (empty($studentId)) {
                return response()->json(['error' => 'Missing student ID'], 422);
            }

            if ($focusLevel === null) {
                return response()->json(['error' => 'Missing focus level'], 422);
            }

            // Create and save focus log
            $focusLog = new FocusLog();
            $focusLog->meeting_id = $meetingId;
            $focusLog->student_id = $studentId;
            $focusLog->focus_level = $focusLevel;
            
            if (!empty($timestamp)) {
                try {
                    $focusLog->created_at = Carbon::parse($timestamp);
                } catch (\Exception $e) {
                    LaravelLog::warning('Invalid timestamp provided, using current time');
                }
            }
            
            $focusLog->save();
            
            // Log success
            LaravelLog::info('Focus data stored successfully', [
                'id' => $focusLog->id,
                'meeting_id' => $meetingId,
                'student_id' => $studentId,
                'focus_level' => $focusLevel
            ]);
            
            // Try to broadcast the update
            try {
                $this->broadcastFocusUpdate($meetingId, $studentId, $focusLevel, $timestamp);
            } catch (\Exception $e) {
                LaravelLog::warning('Failed to broadcast focus update: ' . $e->getMessage());
            }
            
            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Focus data stored successfully',
                'data' => [
                    'id' => $focusLog->id,
                    'focus_level' => $focusLog->focus_level,
                    'timestamp' => $focusLog->created_at
                ]
            ], 200);
        } catch (\Exception $e) {
            // Log the error with full details
            LaravelLog::error('Error storing focus data: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to store focus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to return consistent JSON responses
     */
    protected function jsonResponse($message, $status = 200, $type = 'success', $data = [])
    {
        $response = [
            'status' => $type,
            'message' => $message
        ];
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $status)
            ->header('Content-Type', 'application/json');
    }

    public function getLatest($student_id)
    {
        try {
            $latestLog = FocusLog::where('student_id', $student_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$latestLog) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'focus_level' => 0,
                        'timestamp' => now()
                    ]
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'focus_level' => $latestLog->focus_level,
                    'timestamp' => $latestLog->created_at
                ]
            ]);
        } catch (\Exception $e) {
            // Log error for debugging
            LaravelLog::error('Error retrieving latest focus data: ' . $e->getMessage(), [
                'exception' => $e,
                'student_id' => $student_id
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch focus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all focus data for a specific meeting
     * 
     * @param Meeting $meeting
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMeetingFocusData(Meeting $meeting, Request $request)
    {
        try {
            // Start with the base query
            $query = FocusLog::where('meeting_id', $meeting->id);

            // Apply filters if provided
            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            if ($request->has('time_range')) {
                $timeRange = $request->time_range;
                if ($timeRange === 'last_5_minutes') {
                    $query->where('created_at', '>=', Carbon::now()->subMinutes(5));
                } elseif ($timeRange === 'last_15_minutes') {
                    $query->where('created_at', '>=', Carbon::now()->subMinutes(15));
                } elseif ($timeRange === 'last_30_minutes') {
                    $query->where('created_at', '>=', Carbon::now()->subMinutes(30));
                } elseif ($timeRange === 'last_hour') {
                    $query->where('created_at', '>=', Carbon::now()->subHour());
                } elseif ($timeRange === 'today') {
                    $query->whereDate('created_at', Carbon::today());
                }
            }

            if ($request->has('min_focus_level')) {
                $query->where('focus_level', '>=', $request->min_focus_level);
            }

            if ($request->has('max_focus_level')) {
                $query->where('focus_level', '<=', $request->max_focus_level);
            }

            // Order by timestamp
            $query->orderBy('created_at', $request->input('order', 'desc'));

            // Add pagination if needed
            $perPage = $request->input('per_page', 100);
            $focusLogs = $query->paginate($perPage);

            // Calculate aggregated data
            $aggregatedData = $query->select(
                'student_id',
                DB::raw('AVG(focus_level) as average_focus'),
                DB::raw('MIN(focus_level) as min_focus'),
                DB::raw('MAX(focus_level) as max_focus'),
                DB::raw('COUNT(*) as data_points')
            )
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

            // Get student information for each focus log
            $studentIds = $focusLogs->pluck('student_id')->unique();
            $students = User::whereIn('id', $studentIds)->get(['id', 'name'])->keyBy('id');

            // Return response
            return response()->json([
                'status' => 'success',
                'meeting' => [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                ],
                'data' => $focusLogs->map(function ($log) use ($students) {
                    return [
                        'id' => $log->id,
                        'student_id' => $log->student_id,
                        'student_name' => $students[$log->student_id]->name ?? 'Unknown Student',
                        'focus_level' => $log->focus_level,
                        'timestamp' => $log->created_at,
                    ];
                }),
                'aggregated_data' => $aggregatedData,
                'pagination' => [
                    'total' => $focusLogs->total(),
                    'per_page' => $focusLogs->perPage(),
                    'current_page' => $focusLogs->currentPage(),
                    'last_page' => $focusLogs->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            LaravelLog::error('Error retrieving meeting focus data: ' . $e->getMessage(), [
                'exception' => $e,
                'meeting_id' => $meeting->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving meeting focus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student focus history with stats
     * 
     * @param User $student
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudentFocusHistory(User $student, Request $request)
    {
        try {
            // Start with the base query
            $query = FocusLog::where('student_id', $student->id);

            // Filter by meeting if provided
            if ($request->has('meeting_id')) {
                $query->where('meeting_id', $request->meeting_id);
            }

            // Apply time range filter if provided
            if ($request->has('start_date')) {
                $query->where('created_at', '>=', Carbon::parse($request->start_date));
            }

            if ($request->has('end_date')) {
                $query->where('created_at', '<=', Carbon::parse($request->end_date));
            }

            // Order by timestamp
            $query->orderBy('created_at', $request->input('order', 'desc'));

            // Add pagination
            $perPage = $request->input('per_page', 100);
            $focusLogs = $query->paginate($perPage);

            // Calculate statistics for this student
            $statistics = [
                'average_focus' => $query->avg('focus_level') ?? 0,
                'total_sessions' => $query->distinct('meeting_id')->count(),
                'total_logs' => $query->count(),
                'focus_distribution' => [
                    'high' => $query->where('focus_level', '>=', 80)->count(),
                    'good' => $query->where('focus_level', '>=', 60)->where('focus_level', '<', 80)->count(),
                    'moderate' => $query->where('focus_level', '>=', 40)->where('focus_level', '<', 60)->count(),
                    'low' => $query->where('focus_level', '>=', 20)->where('focus_level', '<', 40)->count(),
                    'very_low' => $query->where('focus_level', '<', 20)->count(),
                ],
                'meetings' => $query->select('meeting_id', DB::raw('AVG(focus_level) as avg_focus'))
                    ->groupBy('meeting_id')
                    ->get()
                    ->keyBy('meeting_id'),
            ];

            // Get meeting information for each focus log
            $meetingIds = $focusLogs->pluck('meeting_id')->unique();
            $meetings = Meeting::whereIn('id', $meetingIds)->get(['id', 'title'])->keyBy('id');

            // Return response
        return response()->json([
            'status' => 'success',
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                ],
                'data' => $focusLogs->map(function ($log) use ($meetings) {
                    return [
                        'id' => $log->id,
                        'meeting_id' => $log->meeting_id,
                        'meeting_title' => $meetings[$log->meeting_id]->title ?? 'Unknown Meeting',
                        'focus_level' => $log->focus_level,
                        'timestamp' => $log->created_at,
                    ];
                }),
                'statistics' => $statistics,
                'pagination' => [
                    'total' => $focusLogs->total(),
                    'per_page' => $focusLogs->perPage(),
                    'current_page' => $focusLogs->currentPage(),
                    'last_page' => $focusLogs->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            LaravelLog::error('Error retrieving student focus history: ' . $e->getMessage(), [
                'exception' => $e,
                'student_id' => $student->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving student focus history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store focus data directly without JSON processing - directly to database
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeDirectly(Request $request)
    {
        // Log minimal info to avoid log bloat
        LaravelLog::info('Direct focus log request', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type')
        ]);

        try {
            // Get data from form fields with fallbacks
            $meetingId = $request->input('meeting_id', $request->input('meetingId'));
            $studentId = $request->input('student_id', $request->input('studentId'));
            $focusLevel = $request->input('focus_level', $request->input('focusLevel', $request->input('focusScore')));
            $timestamp = $request->input('timestamp');

            // Simple validation - be more forgiving
            if (empty($meetingId)) {
                return response('Missing meeting_id', 422);
            }
            
            if (empty($studentId)) {
                return response('Missing student_id', 422);
            }
            
            if (!isset($focusLevel)) {
                return response('Missing focus_level', 422);
            }

            // Create focus log record directly
            $focusLog = new FocusLog();
            $focusLog->meeting_id = $meetingId;
            $focusLog->student_id = $studentId;
            $focusLog->focus_level = $focusLevel;
            
            if (!empty($timestamp)) {
                try {
                    $focusLog->created_at = $timestamp;
                } catch (\Exception $e) {
                    // Invalid timestamp, just use current time
                    LaravelLog::warning('Invalid timestamp provided, using current time');
                }
            }
            
            $focusLog->save();

            // Broadcast update to WebSocket
            $this->broadcastFocusUpdate($meetingId, $studentId, $focusLevel, $timestamp);

            // Return a simple text response - no JSON
            return response('Focus data stored successfully', 200)
                ->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            // Log the error
            LaravelLog::error('Error storing focus data: ' . $e->getMessage());

            // Return a simple text error response - no JSON
            return response('Error storing focus data: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain');
        }
    }

    /**
     * Broadcast focus update to connected clients via WebSocket
     */
    private function broadcastFocusUpdate($meetingId, $studentId, $focusLevel, $timestamp = null)
    {
        try {
            // Get student name - don't cause DB query if unnecessary
            $studentName = null;
            try {
                $student = User::find($studentId);
                $studentName = $student ? $student->name : 'Unknown Student';
            } catch (\Exception $e) {
                LaravelLog::warning('Failed to get student name: ' . $e->getMessage());
                $studentName = 'Unknown Student';
            }
            
            // Get WebSocket server address
            $socketHost = env('WEBSOCKET_HOST', '127.0.0.1');
            $socketPort = env('WEBSOCKET_PORT', 6001);
            
            // Use HTTP client with timeout
            $response = \Illuminate\Support\Facades\Http::timeout(3)->post("http://{$socketHost}:{$socketPort}/broadcast-focus", [
                'meetingId' => $meetingId,
                'studentId' => $studentId,
                'userName' => $studentName,
                'focusScore' => $focusLevel,
                'timestamp' => $timestamp ?? now()->toISOString()
            ]);
            
            if ($response->successful()) {
                return true;
            } else {
                LaravelLog::warning('Failed to broadcast focus update: ' . $response->status());
                return false;
            }
        } catch (\Exception $e) {
            // Don't let broadcast failures affect the main flow
            LaravelLog::warning('Error broadcasting focus update: ' . $e->getMessage());
            return false;
        }
    }
} 