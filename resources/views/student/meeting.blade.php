@extends('layouts.app')

@section('title', 'Student Meeting Room')

@section('content')
<div class="min-h-screen bg-gray-900 flex flex-col">
    <!-- Hidden inputs for JavaScript -->
    <input type="hidden" id="meeting-id" value="{{ $meeting->id }}">
    <input type="hidden" id="user-id" value="{{ auth()->id() }}">
    <input type="hidden" id="user-name" value="{{ auth()->user()->name }}">
    <input type="hidden" id="user-role" value="student">
    <input type="hidden" id="meeting-data" value='{!! json_encode([
        "meetingId" => $meeting->id,
        "userId" => auth()->id(),
        "userName" => auth()->user()->name,
        "userRole" => "student"
    ]) !!}'>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Meeting Header -->
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-white">Meeting #{{ $meeting->id }}</h1>
                    <span class="text-sm text-gray-400">with {{ $meeting->teacher->name }}</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span id="connection-status" class="px-3 py-1 rounded-full text-sm font-medium bg-gray-700 text-gray-200">
                        Connecting...
                    </span>
                    <span id="focus-status" class="px-3 py-1 rounded-full text-sm font-medium bg-gray-700 text-gray-200">
                        Focus: Waiting for Python app...
                    </span>
                    <button id="leave-meeting" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Leave Meeting
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Meeting Content -->
    <div class="flex-1 flex">
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col">
            <div class="flex-1 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Meeting Information -->
                    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                        <h2 class="text-lg font-medium text-white mb-4">Meeting Information</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-400">Meeting ID</label>
                                <p class="mt-1 text-white">{{ $meeting->id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400">Your Name</label>
                                <p class="mt-1 text-white">{{ auth()->user()->name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400">Status</label>
                                <p class="mt-1 text-white">Connected</p>
                            </div>
                        </div>
                    </div>

                    <!-- Focus Tracking -->
                    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                        <h2 class="text-lg font-medium text-white mb-4">Focus Tracking</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-400">Current Focus Level</label>
                                <div class="mt-2">
                                    <div class="relative pt-1">
                                        <div class="flex mb-2 items-center justify-between">
                                            <div>
                                                <span id="current-focus" class="text-xs font-semibold inline-block text-white">
                                                    0%
                                                </span>
                                            </div>
                                        </div>
                                        <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-700">
                                            <div id="focus-bar" style="width:0%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500 transition-all duration-500"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400">Session Duration</label>
                                <p id="session-duration" class="mt-1 text-white">00:00:00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialize meeting data for JavaScript
    window.meetingData = {
        meetingId: parseInt("{{ $meeting->id }}"),
        userId: parseInt("{{ auth()->id() }}"),
        userName: "{{ addslashes(auth()->user()->name) }}",
        userRole: "student"
    };
</script>
<script src="{{ mix('js/meeting-room.js') }}"></script>
@endpush

@endsection 