<?php

namespace Database\Seeders;

use App\Models\FocusLog;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FocusLogSeeder extends Seeder
{
    public function run(): void
    {
        // Get completed meetings
        $completedMeetings = Meeting::where('status', 'completed')->get();
        
        foreach ($completedMeetings as $meeting) {
            // Get participants for this meeting
            $participants = DB::table('participants')
                ->where('meeting_id', $meeting->id)
                ->get();
            
            foreach ($participants as $participant) {
                // Create focus logs for every 5 minutes of the meeting
                $currentTime = Carbon::parse($participant->joined_at);
                $endTime = $participant->left_at ? Carbon::parse($participant->left_at) : Carbon::parse($meeting->end_time);
                
                while ($currentTime < $endTime) {
                    FocusLog::create([
                        'student_id' => $participant->user_id,
                        'meeting_id' => $meeting->id,
                        'focus_level' => rand(60, 100), // Random focus level between 60-100
                        'duration' => 300, // 5 minutes in seconds
                        'created_at' => $currentTime,
                        'updated_at' => $currentTime,
                    ]);
                    
                    $currentTime = $currentTime->addMinutes(5);
                }
            }
        }
        
        // Add focus logs for active meetings
        $activeMeetings = Meeting::where('status', 'active')->get();
        
        foreach ($activeMeetings as $meeting) {
            $participants = DB::table('participants')
                ->where('meeting_id', $meeting->id)
                ->where('is_present', true)
                ->get();
            
            foreach ($participants as $participant) {
                // Create focus logs for the last 30 minutes
                $currentTime = Carbon::now()->subMinutes(30);
                $endTime = Carbon::now();
                
                while ($currentTime < $endTime) {
                    FocusLog::create([
                        'student_id' => $participant->user_id,
                        'meeting_id' => $meeting->id,
                        'focus_level' => rand(70, 100), // Random focus level between 70-100
                        'duration' => 300, // 5 minutes in seconds
                        'created_at' => $currentTime,
                        'updated_at' => $currentTime,
                    ]);
                    
                    $currentTime = $currentTime->addMinutes(5);
                }
            }
        }
    }
} 