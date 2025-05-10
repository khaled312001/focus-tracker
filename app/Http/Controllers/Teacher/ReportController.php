<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\FocusLog;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $students = Student::with(['focusStats', 'meetings'])->get();
        $focusLogs = FocusLog::with(['student', 'meeting'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        // Calculate focus distribution
        $focusDistribution = [
            FocusLog::whereBetween('focus_level', [0, 20])->count(),
            FocusLog::whereBetween('focus_level', [21, 40])->count(),
            FocusLog::whereBetween('focus_level', [41, 60])->count(),
            FocusLog::whereBetween('focus_level', [61, 80])->count(),
            FocusLog::whereBetween('focus_level', [81, 100])->count(),
        ];

        return view('teacher.reports', compact('students', 'focusLogs', 'focusDistribution'));
    }
} 