<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WelcomeController extends Controller
{
    public function index()
    {
        try {
            // Check if testimonials table exists
            if (Schema::hasTable('testimonials')) {
                $testimonials = Testimonial::all();
            } else {
                $testimonials = collect(); // Return empty collection if table doesn't exist
            }
        } catch (\Exception $e) {
            $testimonials = collect(); // Return empty collection on error
        }

        return view('welcome', compact('testimonials'));
    }
} 