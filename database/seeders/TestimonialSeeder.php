<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Testimonial;

class TestimonialSeeder extends Seeder
{
    public function run()
    {
        $testimonials = [
            [
                'name' => 'Sarah Johnson',
                'role' => 'High School Teacher',
                'content' => 'Focus Tracker has revolutionized how I monitor my students\' engagement. The real-time analytics are invaluable!',
                'avatar' => 'https://randomuser.me/api/portraits/women/79.jpg',
                'type' => 'teacher'
            ],
            [
                'name' => 'Michael Chen',
                'role' => 'University Student',
                'content' => 'The focus tracking feature has helped me understand my learning patterns and improve my study habits significantly.',
                'avatar' => 'https://randomuser.me/api/portraits/men/32.jpg',
                'type' => 'student'
            ],
            [
                'name' => 'David Rodriguez',
                'role' => 'Online Course Instructor',
                'content' => 'As an online instructor, this tool has been a game-changer. I can now identify which students need more attention during virtual classes.',
                'avatar' => 'https://randomuser.me/api/portraits/men/45.jpg',
                'type' => 'teacher'
            ],
            [
                'name' => 'Emily Taylor',
                'role' => 'College Student',
                'content' => 'I was struggling with online learning until I found Focus Tracker. Now I can see my progress and stay motivated!',
                'avatar' => 'https://randomuser.me/api/portraits/women/22.jpg',
                'type' => 'student'
            ],
            [
                'name' => 'Dr. James Wilson',
                'role' => 'Educational Researcher',
                'content' => 'The data-driven insights provided by Focus Tracker have helped us understand student engagement patterns in unprecedented ways.',
                'avatar' => 'https://randomuser.me/api/portraits/men/67.jpg',
                'type' => 'researcher'
            ],
            [
                'name' => 'Lisa Anderson',
                'role' => 'Parent',
                'content' => 'As a parent, I love being able to track my child\'s progress and help them stay focused during online classes.',
                'avatar' => 'https://randomuser.me/api/portraits/women/44.jpg',
                'type' => 'parent'
            ]
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }
    }
} 