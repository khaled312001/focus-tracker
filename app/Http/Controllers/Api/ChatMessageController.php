<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatMessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'meeting_id' => 'required|exists:meetings,id'
        ]);

        $message = ChatMessage::create([
            'message' => $request->message,
            'user_id' => Auth::id(),
            'meeting_id' => $request->meeting_id
        ]);

        return response()->json($message->load('user'));
    }

    public function index(Meeting $meeting)
    {
        $messages = ChatMessage::with('user')
            ->where('meeting_id', $meeting->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }
} 