@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700/50">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $meeting->title }}</h1>
                <div class="flex space-x-4">
                    <button id="end-meeting" class="px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-800/50 text-red-800 dark:text-red-300 rounded-lg transition-colors">
                        End Meeting
                    </button>
                </div>
            </div>

            <!-- Focus Metrics -->
            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Class Focus Metrics</h2>
                <div class="flex flex-wrap gap-4">
                    <!-- Average Focus -->
                    <div class="flex-1 min-w-[250px]">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Average Focus</span>
                            <span id="average-focus" class="text-sm font-medium">0%</span>
                        </div>
                        <div class="w-full h-2 bg-gray-300 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div id="average-focus-bar" class="h-full bg-blue-500 transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <!-- Session Duration -->
                    <div class="flex items-center bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400 mr-2">Session Duration:</span>
                        <span id="session-duration" class="text-sm font-medium text-gray-900 dark:text-white">00:00:00</span>
                    </div>
                    
                    <!-- Student Count -->
                    <div class="flex items-center bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400 mr-2">Active Students:</span>
                        <span id="student-count" class="text-sm font-medium text-gray-900 dark:text-white">0</span>
                    </div>
                </div>
            </div>

            <!-- Students Focus Panel -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Student Focus Levels</h2>
                <div id="students-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <!-- Empty State -->
                    <div id="empty-state" class="col-span-full p-6 bg-gray-100 dark:bg-gray-800 rounded-lg text-center">
                        <p class="text-gray-600 dark:text-gray-400">No students have joined this meeting yet.</p>
                    </div>
                    
                    <!-- Students will be added here dynamically -->
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Student History Modal -->
<div id="student-history-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="modal-title text-xl font-semibold text-gray-900 dark:text-white">Student Focus History</h3>
            <button onclick="document.getElementById('student-history-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <!-- Focus history will be loaded here -->
        </div>
        <div class="mt-4 text-right">
            <button onclick="document.getElementById('student-history-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Status indicator */
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    
    /* Animation for changes */
    @keyframes highlight-pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .highlight-change {
        animation: highlight-pulse 0.5s ease-in-out;
    }
    
    /* Focus score with trend indicators */
    .focus-score[data-trend="up"]::after {
        content: " ↑";
        color: rgb(34, 197, 94);
    }
    
    .focus-score[data-trend="down"]::after {
        content: " ↓";
        color: rgb(239, 68, 68);
    }
    
    /* Student card hover effect */
    .student-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .student-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@push('scripts')
<script>
    // Store meeting data needed by the teacher-meeting.js script
    window.meetingData = {
        meetingId: '{{ $meeting->id }}',
        userId: '{{ auth()->id() }}',
        userName: '{{ auth()->user()->name }}'
    };
</script>
<script src="{{ asset('js/teacher-meeting.js') }}"></script>
@endpush

@endsection 