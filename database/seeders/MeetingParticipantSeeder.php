<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\User;
use App\Models\MeetingParticipant;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MeetingParticipantSeeder extends Seeder
{
    public function run(): void
    {
        // Get all meetings
        $meetings = Meeting::all();
        
        // Get student users
        $students = User::where('role', 'student')->get();
        
        foreach ($meetings as $meeting) {
            // For active meetings
            if ($meeting->status === 'active') {
                // Add 2-4 random students as active participants
                $randomStudents = $students->random(rand(2, 4));
                foreach ($randomStudents as $student) {
                    DB::table('participants')->insert([
                        'user_id' => $student->id,
                        'meeting_id' => $meeting->id,
                        'joined_at' => Carbon::now()->subMinutes(rand(5, 30)),
                        'is_present' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            
            // For completed meetings
            elseif ($meeting->status === 'completed') {
                // Add 3-5 students with completed attendance
                $randomStudents = $students->random(rand(3, 5));
                foreach ($randomStudents as $student) {
                    DB::table('participants')->insert([
                        'user_id' => $student->id,
                        'meeting_id' => $meeting->id,
                        'joined_at' => $meeting->start_time->addMinutes(rand(0, 10)),
                        'left_at' => $meeting->end_time->subMinutes(rand(0, 10)),
                        'is_present' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            
            // For scheduled meetings
            else {
                // Add 2-3 students as pre-registered
                $randomStudents = $students->random(rand(2, 3));
                foreach ($randomStudents as $student) {
                    DB::table('participants')->insert([
                        'user_id' => $student->id,
                        'meeting_id' => $meeting->id,
                        'joined_at' => Carbon::now(),
                        'is_present' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
} 