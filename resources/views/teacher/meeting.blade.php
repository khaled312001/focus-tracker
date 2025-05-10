@extends('layouts.app')

@section('title', 'Teacher Meeting Room')

@section('content')
<div class="min-h-screen bg-gray-900 flex flex-col">
    <!-- Hidden inputs for JavaScript -->
    <input type="hidden" id="meeting-id" value="{{ $meeting->id }}">
    <input type="hidden" id="teacher-id" value="{{ auth()->id() }}">
    <input type="hidden" id="teacher-name" value="{{ auth()->user()->name }}">
    <input type="hidden" id="user-role" value="teacher">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Meeting content -->
    <div class="flex-1 flex flex-col">
        <!-- Meeting header -->
        <div class="bg-gray-800 text-white p-4 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-semibold">{{ $meeting->title }}</h1>
                <span class="bg-green-500 px-2 py-1 rounded text-sm">Live</span>
                <span class="text-sm">Meeting ID: {{ $meeting->id }}</span>
            </div>
            <button id="end-meeting" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded">End Meeting</button>
        </div>

        <!-- Meeting content -->
        <div class="flex-1 p-4">
            <!-- Students list and stats -->
            <div class="grid grid-cols-4 gap-4">
                <!-- Students list -->
                <div class="col-span-3 bg-gray-800 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-white text-lg font-semibold">Students</h2>
                        <div class="flex space-x-4">
                            <button id="sort-name" class="text-gray-300 hover:text-white px-3 py-1 rounded transition-colors duration-200">
                                <span class="mr-1">↕</span> Sort by Name
                            </button>
                            <button id="sort-focus" class="text-gray-300 hover:text-white px-3 py-1 rounded transition-colors duration-200">
                                <span class="mr-1">↕</span> Sort by Focus
                            </button>
                        </div>
                    </div>
                    <div id="students-list" class="grid grid-cols-2 gap-4">
                        <!-- Students will be dynamically added here -->
                        <div class="text-center py-8 text-gray-500">
                            <p>No students have joined yet</p>
                            <p class="text-sm mt-2">Share the meeting ID with your students to get started</p>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="space-y-4">
                    <!-- Class overview -->
                    <div class="bg-gray-800 rounded-lg p-4">
                        <h2 class="text-white text-lg font-semibold mb-4">Class Overview</h2>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="bg-gray-700 p-4 rounded">
                                <div class="text-gray-400 text-sm">Average Focus</div>
                                <div id="average-focus" class="text-3xl font-bold text-white">0%</div>
                            </div>
                            <div class="bg-gray-700 p-4 rounded">
                                <div class="text-gray-400 text-sm">Active Students</div>
                                <div class="flex items-baseline">
                                    <span id="active-students" class="text-3xl font-bold text-white">0</span>
                                    <span class="text-gray-400 text-sm ml-2">online</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Focus distribution -->
                    <div class="bg-gray-800 rounded-lg p-4">
                        <h2 class="text-white text-lg font-semibold mb-4">Focus Distribution</h2>
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">High Focus (80-100%)</span>
                                    <span id="high-focus-count" class="text-white font-medium">0</span>
                                </div>
                                <div class="bg-gray-700 rounded-full h-2.5">
                                    <div id="high-focus-bar" class="bg-green-500 rounded-full h-2.5 transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">Medium Focus (50-79%)</span>
                                    <span id="medium-focus-count" class="text-white font-medium">0</span>
                                </div>
                                <div class="bg-gray-700 rounded-full h-2.5">
                                    <div id="medium-focus-bar" class="bg-yellow-500 rounded-full h-2.5 transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">Low Focus (0-49%)</span>
                                    <span id="low-focus-count" class="text-white font-medium">0</span>
                                </div>
                                <div class="bg-gray-700 rounded-full h-2.5">
                                    <div id="low-focus-bar" class="bg-red-500 rounded-full h-2.5 transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Card Template -->
<template id="student-template">
    <div class="bg-gray-700 rounded-lg p-4 student-card hover:bg-gray-600 transition-colors duration-200" data-student-id="">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center">
                    <span class="student-initial text-xl text-white"></span>
                </div>
                <div>
                    <h3 class="text-white font-medium student-name"></h3>
                    <div class="flex items-center space-x-2">
                        <div class="status-indicator w-2 h-2 rounded-full"></div>
                        <span class="text-sm text-gray-400 attention-status">Joining...</span>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold focus-score"></div>
                <div class="text-sm text-gray-400">Focus Score</div>
            </div>
        </div>
        <div class="space-y-3">
            <div class="w-full">
                <div class="bg-gray-600 rounded-full h-2">
                    <div class="focus-bar h-2 rounded-full transition-all duration-300"></div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-gray-400">Session Time</div>
                    <div class="text-white total-time">00:00</div>
                </div>
                <div>
                    <div class="text-gray-400">Focus Time</div>
                    <div class="text-white focus-time">00:00</div>
                </div>
            </div>
        </div>
    </div>
</template>

@push('styles')
<style>
    .highlight-update {
        animation: highlight 1s ease-out;
    }
    
    @keyframes highlight {
        0% {
            background-color: rgba(59, 130, 246, 0.2);
        }
        100% {
            background-color: transparent;
        }
    }
    
    .status-indicator {
        transition: background-color 0.3s ease;
    }
    
    .status-indicator.active {
        background-color: #10B981;
    }
    
    .status-indicator.inactive {
        background-color: #EF4444;
    }
    
    .focus-bar {
        transition: width 0.3s ease, background-color 0.3s ease;
    }
    
    .focus-bar.high {
        background-color: #10B981;
    }
    
    .focus-bar.medium {
        background-color: #F59E0B;
    }
    
    .focus-bar.low {
        background-color: #EF4444;
    }
    
    .focus-score.high {
        color: #10B981;
    }
    
    .focus-score.medium {
        color: #F59E0B;
    }
    
    .focus-score.low {
        color: #EF4444;
    }
</style>
@endpush

@push('scripts')
<!-- Load required scripts -->
@vite(['resources/js/app.js'])

<script>
    // Initialize meeting data
    window.meetingData = {!! json_encode([
        'meetingId' => $meeting->id,
        'userId' => auth()->id(),
        'userName' => auth()->user()->name,
        'userRole' => 'teacher'
    ]) !!};
</script>

<!-- Initialize after scripts are loaded -->
<script type="module">
    import { TeacherHandler } from '{{ Vite::asset("resources/js/meeting/teacher-handler.js") }}';
    import { WebSocketManager } from '{{ Vite::asset("resources/js/websocket.js") }}';

    // Wait for DOM to be loaded
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            // Initialize WebSocket Manager
            if (!window.wsManager) {
                window.wsManager = new WebSocketManager();
            }

            // Initialize teacher handler
            window.teacherHandler = new TeacherHandler(
                window.meetingData.meetingId,
                window.meetingData.userId,
                window.meetingData.userName
            );
        } catch (error) {
            console.error('[Teacher] Failed to initialize:', error);
            const errorMessage = document.createElement('div');
            errorMessage.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded fixed top-4 right-4';
            errorMessage.textContent = 'Failed to initialize meeting. Please refresh the page.';
            document.body.appendChild(errorMessage);
        }
    });
</script>
@endpush

@endsection 