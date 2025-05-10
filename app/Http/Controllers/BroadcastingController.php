<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BroadcastingController extends Controller
{
    /**
     * Handle broadcasting events.
     */
    public function handleEvent(Request $request, $event)
    {
        $data = $request->all();
        
        // Log the event
        Log::info("Broadcasting event: {$event}", $data);
        
        // Store the event in the cache for polling
        $events = Cache::get('broadcasting_events', []);
        $events[] = [
            'id' => count($events) + 1,
            'name' => $event,
            'data' => $data,
            'timestamp' => now()->timestamp
        ];
        Cache::put('broadcasting_events', $events, now()->addMinutes(5));
        
        // Broadcast the event using the correct method
        Broadcast::to('meeting.' . ($data['meetingId'] ?? 'default'))->emit($event, $data);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Get broadcasting events.
     */
    public function getEvents(Request $request)
    {
        $lastId = $request->input('last_id', 0);
        $events = Cache::get('broadcasting_events', []);
        
        // Filter events by ID
        $events = array_filter($events, function ($event) use ($lastId) {
            return $event['id'] > $lastId;
        });
        
        return response()->json(['events' => array_values($events)]);
    }
} 