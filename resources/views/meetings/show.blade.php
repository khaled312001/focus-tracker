@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8" id="meeting-container" data-meeting-id="{{ $meeting->id }}">
    <div class="max-w-4xl mx-auto">
        <div class="bg-gray rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">{{ $meeting->title }}</h1>
                <div class="flex space-x-4">
                    @if($meeting->status === 'scheduled')
                        <form action="{{ route('meetings.start', $meeting) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                Start Meeting
                            </button>
                        </form>
                    @elseif($meeting->status === 'active')
                        <form action="{{ route('meetings.end', $meeting) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                End Meeting
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('meetings.edit', $meeting) }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Edit Meeting
                    </a>
                    <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" onclick="return confirm('Are you sure you want to delete this meeting?')">
                            Delete Meeting
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Description</h2>
                        <p class="text-gray-600">{{ $meeting->description ?: 'No description provided' }}</p>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Status</h2>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                            @if($meeting->status === 'scheduled') bg-yellow-100 text-yellow-800
                            @elseif($meeting->status === 'active') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($meeting->status) }}
                        </span>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Schedule</h2>
                        <div class="space-y-2">
                            <p class="text-gray-600">
                                <span class="font-medium">Start:</span>
                                {{ $meeting->start_time->format('F j, Y g:i A') }}
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">End:</span>
                                {{ $meeting->end_time->format('F j, Y g:i A') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            @if($meeting->status === 'active')
                <div class="mt-8">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Active Participants</h2>
                    <div id="participants-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Participants will be loaded dynamically via WebSocket -->
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($meeting->status === 'active')
    @push('scripts')
    <script>
        // WebSocket connection for real-time updates
        const ws = new WebSocket('ws://localhost:3001');
        const meetingId = document.getElementById('meeting-container').dataset.meetingId;
        
        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.meetingId == meetingId) {
                updateParticipantsList(data.participants);
            }
        };

        function updateParticipantsList(participants) {
            const container = document.getElementById('participants-list');
            container.innerHTML = participants.map(participant => `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">${participant.userName}</p>
                            <p class="text-sm text-gray-500">${participant.userRole}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium">Focus Score</p>
                            <p class="text-lg ${getFocusScoreColor(participant.focusScore)}">${participant.focusScore}%</p>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        function getFocusScoreColor(score) {
            if (score >= 80) return 'text-green-600';
            if (score >= 60) return 'text-yellow-600';
            return 'text-red-600';
        }
    </script>
    @endpush
@endif
@endsection 