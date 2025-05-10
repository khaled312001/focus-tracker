@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <!-- Meeting Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Meeting #{{ $meetingId }}</h1>
                    <div class="flex items-center space-x-4">
                        <span id="connection-status" class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                            Connecting...
                        </span>
                    </div>
                </div>
            </div>

            <!-- Meeting Content -->
            <div class="p-6">
                <!-- Focus Tracking -->
                <div class="mb-8">
                    <div class="flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-6 w-full md:w-auto">
                        <!-- Focus Score -->
                        <div class="flex items-center bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 w-full md:w-auto">
                            <div class="mr-3">
                                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Focus Score</p>
                                <div class="flex items-center space-x-2">
                                    <div class="w-32 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                        <div id="focus-bar" class="h-full transition-all duration-300 ease-out bg-blue-500" style="width: 0%"></div>
                                    </div>
                                    <span id="focus-score" class="text-sm font-medium text-gray-700 dark:text-gray-300">0%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Indicator -->
                        <div class="flex items-center bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <div class="mr-3">
                                <div id="focus-indicator" class="w-2 h-2 rounded-full bg-gray-400"></div>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</p>
                                <p id="focus-status" class="text-sm font-medium text-gray-700 dark:text-gray-300">Initializing...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Meeting Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Meeting Information</h3>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Meeting ID:</span> {{ $meetingId }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Your Name:</span> {{ $userName }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Duration:</span> <span id="meeting-duration">00:00:00</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Message -->
<div id="status-message" class="fixed top-4 right-4 px-4 py-2 rounded-lg text-white text-sm hidden"></div>

@endsection

@push('scripts')
<script>
    window.meetingData = {
        meetingId: parseInt("{{ $meetingId }}"),
        userId: parseInt("{{ $userId }}"),
        userName: "{{ addslashes($userName) }}",
        userRole: "student"
    };
</script>
<script src="{{ mix('js/meeting-room.js') }}"></script>
@endpush 