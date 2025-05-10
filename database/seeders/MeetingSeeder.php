<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        // Get all teachers
        $teachers = User::where('role', 'teacher')->get();
        
        foreach ($teachers as $teacher) {
            // Create active meetings
            Meeting::create([
                'title' => 'Mathematics 101',
                'description' => 'Introduction to Basic Algebra',
                'teacher_id' => $teacher->id,
                'status' => 'active',
                'start_time' => Carbon::now(),
                'end_time' => Carbon::now()->addHours(2),
            ]);

            Meeting::create([
                'title' => 'Physics Fundamentals',
                'description' => 'Understanding Motion and Forces',
                'teacher_id' => $teacher->id,
                'status' => 'active',
                'start_time' => Carbon::now()->addHours(3),
                'end_time' => Carbon::now()->addHours(5),
            ]);

            // Create scheduled meetings
            Meeting::create([
                'title' => 'Chemistry Basics',
                'description' => 'Introduction to Chemical Reactions',
                'teacher_id' => $teacher->id,
                'status' => 'scheduled',
                'start_time' => Carbon::tomorrow(),
                'end_time' => Carbon::tomorrow()->addHours(2),
            ]);

            Meeting::create([
                'title' => 'Biology 101',
                'description' => 'Cell Structure and Function',
                'teacher_id' => $teacher->id,
                'status' => 'scheduled',
                'start_time' => Carbon::tomorrow()->addHours(3),
                'end_time' => Carbon::tomorrow()->addHours(5),
            ]);

            // Create completed meetings
            Meeting::create([
                'title' => 'Computer Science Basics',
                'description' => 'Introduction to Programming',
                'teacher_id' => $teacher->id,
                'status' => 'completed',
                'start_time' => Carbon::yesterday(),
                'end_time' => Carbon::yesterday()->addHours(2),
            ]);

            Meeting::create([
                'title' => 'Literature Analysis',
                'description' => 'Understanding Shakespeare',
                'teacher_id' => $teacher->id,
                'status' => 'completed',
                'start_time' => Carbon::yesterday()->subHours(3),
                'end_time' => Carbon::yesterday()->subHour(),
            ]);
        }
    }
} 