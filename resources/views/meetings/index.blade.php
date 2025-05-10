@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Enhanced Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-800 dark:to-purple-900 rounded-2xl p-6 mb-8 shadow-lg">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div class="mb-4 md:mb-0">
                        <h1 class="text-3xl font-bold text-white mb-2">Meetings</h1>
                        <p class="text-indigo-100 dark:text-indigo-200">Manage and join your virtual meetings</p>
                    </div>
                    @if(auth()->user()->role === 'teacher')
                        <a href="{{ route('meetings.create') }}" class="bg-gray text-indigo-600 hover:bg-indigo-50 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 px-5 py-2.5 rounded-lg font-medium shadow-sm transition-all duration-200 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create New Meeting
                        </a>
                    @endif
                </div>
            </div>

            <!-- Meetings List -->
            @if($meetings->isEmpty())
                <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-sm p-8 text-center border border-gray-200 dark:border-gray-700">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-full mb-4">
                        <svg class="w-8 h-8 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">No Meetings Found</h3>
                    @if(auth()->user()->role === 'teacher')
                        <p class="text-gray-500 dark:text-gray-400 mb-6">Create a new meeting to get started with your virtual classroom.</p>
                        <a href="{{ route('meetings.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Your First Meeting
                        </a>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 mb-6">You haven't been invited to any meetings yet. Check back later or contact your teacher.</p>
                    @endif
                </div>
            @else
                <!-- Card-based layout for meetings -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($meetings as $meeting)
                        <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden border border-gray-200 dark:border-gray-700">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $meeting->title }}</h3>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full 
                                        {{ $meeting->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 
                                           ($meeting->status === 'scheduled' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' : 
                                           'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                        {{ ucfirst($meeting->status) }}
                                    </span>
                                </div>
                                
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ Str::limit($meeting->description, 100) }}</p>
                                
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    <svg class="w-4 h-4 mr-1.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>{{ $meeting->start_time->format('M d, Y h:i A') }}</span>
                                </div>
                                
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-6">
                                    <svg class="w-4 h-4 mr-1.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span>{{ $meeting->participants->whereNull('left_at')->count() }} / {{ $meeting->participants->count() }} participants</span>
                                </div>
                                
                                <div class="flex flex-wrap gap-2">
                                    @if(auth()->user()->role === 'teacher')
                                        @if($meeting->status === 'scheduled')
                                            <form action="{{ route('meetings.start', $meeting) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Start
                                                </button>
                                            </form>
                                        @elseif($meeting->status === 'active')
                                            <form action="{{ route('meetings.end', $meeting) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                                                    </svg>
                                                    End
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('meetings.edit', $meeting) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit
                                        </a>
                                        <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors" onclick="return confirm('Are you sure you want to delete this meeting?')">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    @else
                                        @if($meeting->status === 'active')
                                            <a href="{{ route('meetings.join', $meeting) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                                Join Meeting
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Enhanced Pagination -->
            @if($meetings->hasPages())
                <div class="mt-8">
                    {{ $meetings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Add smooth animations to cards
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.shadow-sm');
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