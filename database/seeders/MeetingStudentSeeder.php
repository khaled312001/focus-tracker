<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeetingStudentSeeder extends Seeder
{
    public function run(): void
    {
        // Get all meetings
        $meetings = Meeting::all();
        
        // Get student users
        $students = User::where('role', 'student')->get();
        
        // Add students to meetings
        foreach ($meetings as $meeting) {
            // Add 1-3 random students to each meeting
            $randomStudents = $students->random(rand(1, 3));
            
            foreach ($randomStudents as $student) {
                DB::table('meeting_student')->insert([
                    'meeting_id' => $meeting->id,
                    'student_id' => $student->id,
                    'is_present' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
} 