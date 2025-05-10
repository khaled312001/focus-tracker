@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-800 dark:to-purple-900 rounded-2xl p-8 mb-8 shadow-lg backdrop-blur-sm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-3xl font-bold text-white mb-2">Welcome Back, {{ Auth::user()->name }}!</h1>
                    <p class="text-indigo-100 dark:text-indigo-200">Track your focus and stay engaged in your learning journey</p>
                </div>
                <div class="flex items-center bg-gray-800/10 rounded-xl p-4 backdrop-blur-sm border border-white/20">
                    <div class="mr-4 text-right">
                        <p class="text-sm font-medium text-indigo-100 dark:text-indigo-200">Active Meetings</p>
                        <p class="text-2xl font-bold text-white">{{ $activeMeetings->count() }}</p>
                    </div>
                    <div class="p-3 bg-gray-800 /20 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="{{ route('student.meetings') }}" 
               class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:scale-105 group border border-gray-200 dark:border-gray-700/50">
                <div class="flex items-center">
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800/50 transition-colors">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">All Meetings</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">View your meeting schedule</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('student.focus-stats') }}" 
               class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:scale-105 group border border-gray-200 dark:border-gray-700/50">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-lg group-hover:bg-purple-200 dark:group-hover:bg-purple-800/50 transition-colors">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Focus Stats</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">View your focus analytics</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('student.settings') }}" 
               class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:scale-105 group border border-gray-200 dark:border-gray-700/50">
                <div class="flex items-center">
                    <div class="p-3 bg-pink-100 dark:bg-pink-900/50 rounded-lg group-hover:bg-pink-200 dark:group-hover:bg-pink-800/50 transition-colors">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Settings</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Customize your preferences</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Active Meetings Section -->
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Active Meetings</h2>
            @if($activeMeetings->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($activeMeetings as $meeting)
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-200 dark:border-gray-700/50">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $meeting->title }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Started {{ $meeting->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 rounded-full">Live</span>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $meeting->description }}</p>
                                <a href="{{ route('student.meetings.join', $meeting) }}" 
                                   class="block w-full bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-600 text-white text-center px-4 py-2 rounded-lg transition-colors">
                                    Join Now
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl shadow-sm p-6 text-center border border-gray-200 dark:border-gray-700/50">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No Active Meetings</h3>
                    <p class="text-gray-600 dark:text-gray-400">Check back later for upcoming meetings</p>
                </div>
            @endif
        </div>

        <!-- Upcoming Meetings Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Upcoming Meetings</h2>
                <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300 rounded-full text-sm font-medium">
                    {{ $upcomingMeetings->count() }} Scheduled
                </span>
            </div>
            @if($upcomingMeetings->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($upcomingMeetings as $meeting)
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700/50">
                            <div class="p-6">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ $meeting->title }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $meeting->description }}</p>
                                <div class="space-y-3">
                                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 mr-2 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ $meeting->start_time->format('M d, Y h:i A') }}</span>
                                    </div>
                                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 mr-2 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ $meeting->start_time->diffInHours($meeting->end_time) }} hours</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl shadow-sm p-6 text-center border border-gray-200 dark:border-gray-700/50">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No Upcoming Meetings</h3>
                    <p class="text-gray-600 dark:text-gray-400">You're all caught up!</p>
                </div>
            @endif
        </div>

        <!-- Past Meetings Section -->
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Past Meetings</h2>
                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded-full text-sm font-medium">
                    {{ $pastMeetings->count() }} Completed
                </span>
            </div>
            @if($pastMeetings->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($pastMeetings as $meeting)
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700/50">
                            <div class="p-6">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ $meeting->title }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $meeting->description }}</p>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Focus Score</span>
                                        <div class="flex items-center">
                                            <div class="w-32 h-2 bg-gray-200 dark:bg-gray-700 rounded-full mr-2 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500 ease-out
                                                    {{ $meeting->averageFocus >= 80 ? 'bg-green-500 dark:bg-green-400' : 
                                                       ($meeting->averageFocus >= 60 ? 'bg-teal-500 dark:bg-teal-400' : 
                                                       ($meeting->averageFocus >= 40 ? 'bg-yellow-500 dark:bg-yellow-400' : 
                                                       ($meeting->averageFocus >= 20 ? 'bg-orange-500 dark:bg-orange-400' : 'bg-red-500 dark:bg-red-400'))) }}"
                                                     style="width: {{ $meeting->averageFocus }}%">
                                                </div>
                                            </div>
                                            <span class="font-semibold 
                                                {{ $meeting->averageFocus >= 80 ? 'text-green-600 dark:text-green-400' : 
                                                   ($meeting->averageFocus >= 60 ? 'text-teal-600 dark:text-teal-400' : 
                                                   ($meeting->averageFocus >= 40 ? 'text-yellow-600 dark:text-yellow-400' : 
                                                   ($meeting->averageFocus >= 20 ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400'))) }}">
                                                {{ round($meeting->averageFocus) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl shadow-sm p-6 text-center border border-gray-200 dark:border-gray-700/50">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No Past Meetings</h3>
                    <p class="text-gray-600 dark:text-gray-400">Your meeting history will appear here</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth animations to progress bars
    const progressBars = document.querySelectorAll('[style*="width"]');
    progressBars.forEach(bar => {
        bar.style.transition = 'width 1s ease-out';
        // Trigger animation
        setTimeout(() => {
            bar.style.width = bar.dataset.width;
        }, 100);
    });

    // Add hover effects to cards
    const cards = document.querySelectorAll('.hover\\:shadow-md');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-2px)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endpush

@endsection 