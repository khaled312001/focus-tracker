@extends('layouts.app')

@section('title', 'Meeting Room')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-semibold">Meeting Room: {{ $meeting->title }}</h2>
                        <div class="flex items-center space-x-4">
                            <span id="focus-status" class="hidden">Focused</span>
                            <div class="hidden">
                             <span class="text-sm">Focus Level:</span>
                                <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div id="focus-bar" class="h-full bg-green-500 transition-all duration-300" style="width: 0%"></div>
                                </div>
                                <span id="focus-value" class="text-sm font-medium">0%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Main Video Feed -->
                        <div class="md:col-span-2">
                            <div class="relative aspect-video bg-black rounded-lg overflow-hidden">
                                <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                                <div class="absolute bottom-4 left-4 flex space-x-2">
                                    <button id="toggleVideo" class="p-2 rounded-full bg-gray-800 text-white hover:bg-gray-700 transition-colors">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <button id="toggleAudio" class="p-2 rounded-full bg-gray-800 text-white hover:bg-gray-700 transition-colors">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                        </svg>
                                    </button>
                                    <button id="toggleScreen" class="p-2 rounded-full bg-gray-800 text-white hover:bg-gray-700 transition-colors">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Participants and Chat -->
                        <div class="space-y-4">
                            <!-- Participants List -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-medium mb-3">Participants</h3>
                                <div id="participants-list" class="space-y-2">
                                    <!-- Participants will be dynamically added here -->
                                </div>
                            </div>

                            <!-- Chat Section -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-medium mb-3">Chat</h3>
                                <div id="chat-messages" class="h-64 overflow-y-auto mb-4 space-y-2">
                                    <!-- Chat messages will be dynamically added here -->
                                </div>
                                <div class="flex space-x-2">
                                    <input type="text" id="chat-input" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" placeholder="Type a message...">
                                    <button id="send-message" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        Send
                                    </button>
                                </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const meetingRoom = new MeetingRoom();
        meetingRoom.initialize();
    });
</script>
@endpush
@endsection 