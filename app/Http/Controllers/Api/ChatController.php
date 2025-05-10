<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function getMessages(Meeting $meeting)
    {
        // Check if user is authorized to view messages
        if (!$meeting->participants()->where('user_id', Auth::id())->exists() &&
            $meeting->teacher_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $meeting->messages()
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->get();

        // Log the messages for debugging
        Log::info('Messages for meeting ' . $meeting->id . ':', ['messages' => $messages->toArray()]);

        $formattedMessages = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'message' => $message->message,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name
                ],
                'created_at' => $message->created_at->format('Y-m-d H:i:s')
            ];
        });

        // Return an empty array if there are no messages
        return response()->json($formattedMessages->isEmpty() ? [] : $formattedMessages);
    }

    public function sendMessage(Request $request, Meeting $meeting)
    {
        // Check if user is authorized to send messages
        if (!$meeting->participants()->where('user_id', Auth::id())->exists() &&
            $meeting->teacher_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $message = $meeting->messages()->create([
            'user_id' => Auth::id(),
            'message' => $request->message
        ]);

        $message->load('user:id,name');

        return response()->json([
            'id' => $message->id,
            'message' => $message->message,
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name
            ],
            'created_at' => $message->created_at->format('Y-m-d H:i:s')
        ]);
    }
} 