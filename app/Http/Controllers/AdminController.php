<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FocusLog;

class AdminController extends Controller
{
    public function dashboard()
    {
        $logs = FocusLog::with('student')->orderBy('session_time', 'desc')->get();
        return view('admin.dashboard', compact('logs'));
    }
}
