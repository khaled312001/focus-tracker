<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FocusLog;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class FetchFocusData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'focus:fetch {--interval=60 : Seconds between fetches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch focus data from the database and emit to WebSocket';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');
        
        $this->info("Starting focus data fetcher (interval: {$interval}s)");
        
        while (true) {
            $this->fetchAndEmitFocusData();
            sleep($interval);
        }
    }
    
    /**
     * Fetch focus data from database and emit to WebSocket
     */
    protected function fetchAndEmitFocusData()
    {
        // Get active meetings (those with recent focus data)
        $activeMeetings = Meeting::whereIn('id', function($query) {
            $query->select('meeting_id')
                ->from('focus_logs')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->groupBy('meeting_id');
        })->get();
        
        $this->info("Active meetings found: " . $activeMeetings->count());
        
        foreach ($activeMeetings as $meeting) {
            try {
                $this->info("Processing meeting: {$meeting->id} ({$meeting->title})");
                
                // Get recent focus data for this meeting
                $focusData = FocusLog::where('meeting_id', $meeting->id)
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                $this->info("Focus data points: " . $focusData->count());
                
                // Get unique students in this data
                $studentIds = $focusData->pluck('student_id')->unique();
                $students = User::whereIn('id', $studentIds)->get(['id', 'name'])->keyBy('id');
                
                // Process each student's latest focus data
                foreach ($studentIds as $studentId) {
                    $latestData = $focusData->where('student_id', $studentId)->first();
                    
                    if (!$latestData || !$students->has($studentId)) {
                        continue;
                    }
                    
                    $student = $students[$studentId];
                    
                    // Send to WebSocket
                    $this->sendToWebSocket($meeting->id, $studentId, $student->name, $latestData->focus_level);
                }
                
                $this->info("Completed processing meeting: {$meeting->id}");
            } catch (\Exception $e) {
                $this->error("Error processing meeting {$meeting->id}: " . $e->getMessage());
                Log::error("Error in FetchFocusData command: " . $e->getMessage(), [
                    'meeting_id' => $meeting->id,
                    'exception' => $e
                ]);
            }
        }
    }
    
    /**
     * Send focus data to WebSocket server
     */
    protected function sendToWebSocket($meetingId, $studentId, $studentName, $focusLevel)
    {
        try {
            // Get WebSocket server address from config
            $socketHost = env('WEBSOCKET_HOST', '127.0.0.1');
            $socketPort = env('WEBSOCKET_PORT', 6001);
            
            $this->info("Sending focus data to WebSocket: Meeting {$meetingId}, Student {$studentId} ({$studentName}), Focus: {$focusLevel}");
            
            // Send via HTTP to the WebSocket server
            $url = "http://{$socketHost}:{$socketPort}/emit";
            
            $response = Http::post($url, [
                'event' => 'focus-data-from-db',
                'room' => "meeting-{$meetingId}",
                'data' => [
                    'meetingId' => $meetingId,
                    'studentId' => $studentId,
                    'userName' => $studentName,
                    'focusScore' => $focusLevel,
                    'timestamp' => now()->toISOString(),
                    'source' => 'database'
                ]
            ]);
            
            if ($response->successful()) {
                $this->info("Successfully sent focus data to WebSocket");
            } else {
                $this->warn("Failed to send focus data to WebSocket: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("Error sending focus data to WebSocket: " . $e->getMessage());
            Log::error("WebSocket communication error: " . $e->getMessage());
        }
    }
}
