@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Focus Tracking</h1>
            
            <!-- Hidden inputs for data -->
            @if($meeting)
                <input type="hidden" id="meeting-id" value="{{ $meeting->id }}">
            @endif
            <input type="hidden" id="user-id" value="{{ auth()->id() }}">
            <input type="hidden" id="user-name" value="{{ auth()->user()->name }}">
            <meta name="csrf-token" content="{{ csrf_token() }}">

            <!-- Video container -->
            <div class="relative mb-6">
                <video id="video" class="w-full rounded-lg" autoplay playsinline></video>
                <canvas id="canvas" class="hidden"></canvas>
            </div>

            <!-- Focus Status -->
            <div class="mb-6">
                @if(!$meeting)
                    <div class="p-4 rounded-lg bg-yellow-100 text-yellow-800">
                        No active meeting. Please join a meeting to start focus tracking.
                        <a href="{{ route('student.meetings') }}" class="underline">View Available Meetings</a>
                    </div>
                @else
                    <div id="status-message" class="p-4 rounded-lg bg-blue-100 text-blue-800">
                        Initializing camera and connection...
                    </div>
                @endif
            </div>

            <!-- Focus Metrics -->
            @if($meeting)
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h2 class="text-lg font-semibold mb-2">Current Focus</h2>
                        <div id="focus-score" class="text-3xl font-bold text-blue-600">0%</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h2 class="text-lg font-semibold mb-2">Session Duration</h2>
                        <div id="session-time" class="text-3xl font-bold text-green-600">00:00</div>
                    </div>
                </div>

                <!-- Meeting Info -->
                <div class="mt-6 bg-gray-50 rounded-lg p-4">
                    <h2 class="text-lg font-semibold mb-2">Meeting Information</h2>
                    <p><span class="font-medium">Title:</span> {{ $meeting->title }}</p>
                    <p><span class="font-medium">Teacher:</span> {{ $meeting->teacher->name }}</p>
                    <p><span class="font-medium">Status:</span> {{ ucfirst($meeting->status) }}</p>
                </div>

                <!-- Leave Meeting Button -->
                <div class="mt-6">
                    <form action="{{ route('meetings.leave', $meeting) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Leave Meeting
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

@if($meeting)
    @push('scripts')
    <script src="{{ asset('js/focus-tracking.js') }}"></script>
    @endpush
@endif
@endsection 