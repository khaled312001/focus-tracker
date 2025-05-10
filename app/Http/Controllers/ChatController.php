<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Meeting $meeting)
    {
        $messages = $meeting->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'userId' => $message->user_id,
                    'userName' => $message->user->name,
                    'timestamp' => $message->created_at->format('H:i')
                ];
            });

        return response()->json($messages);
    }

    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'userId' => 'required|exists:users,id'
        ]);

        $message = $meeting->messages()->create([
            'content' => $validated['content'],
            'user_id' => $validated['userId']
        ]);

        return response()->json([
            'id' => $message->id,
            'content' => $message->content,
            'userId' => $message->user_id,
            'userName' => $message->user->name,
            'timestamp' => $message->created_at->format('H:i')
        ]);
    }
} 