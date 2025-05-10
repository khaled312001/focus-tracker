use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FocusTrackingController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\FocusLogController;
use App\Http\Controllers\FocusMetricsController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FocusController;

// API routes that need to work without CSRF
Route::middleware('api')->group(function () {
    // Focus logs storage endpoints
    Route::post('/focus-logs', [FocusLogController::class, 'store'])->withoutMiddleware(['web']);
    Route::post('/focus-logs-direct', [FocusLogController::class, 'storeDirectly'])->withoutMiddleware(['web']);
    
    // Focus metrics endpoints
    Route::get('/focus-metrics/{meetingId}', [FocusMetricsController::class, 'show'])->withoutMiddleware(['web']);
    Route::get('/focus-metrics/summary/{meetingId}', [FocusMetricsController::class, 'summary'])->withoutMiddleware(['web']);
    Route::get('/focus-metrics/{meeting}/student/{student}', [FocusController::class, 'getStudentMetrics'])->withoutMiddleware(['web']);
    
    // Add a route that matches the existing API call
    Route::get('/focus-metrics/{meetingId}', [FocusMetricsController::class, 'show'])->withoutMiddleware(['web']);
});

// Protected API routes (require web middleware)
Route::middleware('web')->group(function () {
    // Focus tracking endpoints
    Route::post('/focus-tracking/start', [FocusTrackingController::class, 'startTracking']);
    Route::post('/focus-tracking/stop', [FocusTrackingController::class, 'stopTracking']);
    
    // Focus logs retrieval endpoints
    Route::get('/focus-logs/meeting/{meeting}', [FocusLogController::class, 'getMeetingFocusData']);
    Route::get('/focus-logs/student/{student}', [FocusLogController::class, 'getStudentFocusHistory']);
    Route::get('/focus-logs/{student_id}/latest', [FocusLogController::class, 'getLatest']);
    
    // Meeting management endpoints
    Route::post('/meetings/{meeting}/end', [MeetingController::class, 'endMeeting']);
    Route::post('/meetings/{meeting}/analytics', [MeetingController::class, 'saveAnalytics']);
    Route::get('/meetings/{meeting}/analytics', [MeetingController::class, 'getAnalytics']);
    Route::get('/meetings', [MeetingController::class, 'indexApi']);

    // Meeting API routes
    Route::get('/meetings/{meeting}/students', function (App\Models\Meeting $meeting) {
        $students = $meeting->students()->get(['id', 'name']);
        return response()->json($students);
    });
});

// Health check endpoint (no auth required)
Route::get('/health', function() {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'environment' => app()->environment()
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    // Other authenticated routes can go here
}); 