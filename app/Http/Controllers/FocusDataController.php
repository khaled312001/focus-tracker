<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FocusData;
use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class FocusDataController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'meetingId' => 'required|exists:meetings,id',
            'userId' => 'required|exists:users,id',
            'userName' => 'required|string',
            'focusScore' => 'required|numeric|min:0|max:100',
            'faceDetected' => 'required|boolean',
            'totalFocusTime' => 'required|integer|min:0'
        ]);

        // Update or create focus data record
        FocusData::updateOrCreate(
            [
                'meeting_id' => $validated['meetingId'],
                'user_id' => $validated['userId'],
            ],
            [
                'user_name' => $validated['userName'],
                'focus_score' => $validated['focusScore'],
                'face_detected' => $validated['faceDetected'],
                'total_focus_time' => $validated['totalFocusTime'],
                'last_update' => Carbon::now(),
            ]
        );

        return response()->json(['message' => 'Focus data stored successfully']);
    }

    public function getMeetingData(Meeting $meeting): JsonResponse
    {
        // Get focus data for active students (last update within 10 seconds)
        $focusData = FocusData::where('meeting_id', $meeting->id)
            ->where('last_update', '>=', Carbon::now()->subSeconds(10))
            ->get();

        return response()->json($focusData);
    }

    public function index()
    {
        return view('focus-data.index');
    }
}
