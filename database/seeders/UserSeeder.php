<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create teachers
        $teachers = [
            [
                'name' => 'John Smith',
                'email' => 'teacher@example.com',
                'password' => Hash::make('password'),
                'role' => 'teacher',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@example.com',
                'password' => Hash::make('password'),
                'role' => 'teacher',
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@example.com',
                'password' => Hash::make('password'),
                'role' => 'teacher',
            ],
        ];

        foreach ($teachers as $teacher) {
            User::create($teacher);
        }

        // Create students
        $students = [
            [
                'name' => 'Jane Student',
                'email' => 'student@example.com',
                'password' => Hash::make('password'),
                'role' => 'student',
            ],
            [
                'name' => 'Emily Wilson',
                'email' => 'emily.wilson@example.com',
                'password' => Hash::make('password'),
                'role' => 'student',
            ],
            [
                'name' => 'David Lee',
                'email' => 'david.lee@example.com',
                'password' => Hash::make('password'),
                'role' => 'student',
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa.anderson@example.com',
                'password' => Hash::make('password'),
                'role' => 'student',
            ],
            [
                'name' => 'James Taylor',
                'email' => 'james.taylor@example.com',
                'password' => Hash::make('password'),
                'role' => 'student',
            ],
        ];

        foreach ($students as $student) {
            User::create($student);
        }
    }
} 