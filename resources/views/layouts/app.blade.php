<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @auth
    data-meeting-info="{{ json_encode([
        'userId' => auth()->id(),
        'userName' => auth()->user()->name,
        'userRole' => auth()->user()->role,
        'meetingId' => isset($meeting) ? $meeting->id : null,
        'meetingTitle' => isset($meeting) ? $meeting->title : '',
        'meetingStatus' => isset($meeting) ? $meeting->status : '',
        'isTeacher' => auth()->user()->role === 'teacher'
    ]) }}"
    @endauth
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        @auth
            <meta name="user-id" content="{{ auth()->id() }}">
            <meta name="user-name" content="{{ auth()->user()->name }}">
            <meta name="user-role" content="{{ auth()->user()->role }}">
            
            @if(isset($meeting))
                <meta name="meeting-id" content="{{ $meeting->id }}">
                <meta name="meeting-title" content="{{ $meeting->title }}">
                <meta name="meeting-status" content="{{ $meeting->status }}">
                @if(auth()->user()->role === 'teacher')
                    <meta name="is-teacher" content="true">
                @endif
            @endif
        @endauth

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts and Styles -->
        @vite([
            'resources/css/app.css',
            'resources/css/dashboard.css',
            'resources/css/welcome.css',
            'resources/js/app.js',
            'resources/js/websocket.js',
            'resources/js/join.js'
        ])
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        
        <!-- ApexCharts -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

        <!-- Additional page-specific styles -->
        @stack('styles')
        
        @yield('styles')

        <!-- Meeting Data Script -->
        <script type="text/javascript">
            (function() {
                try {
                    const meetingInfo = document.documentElement.dataset.meetingInfo;
                    if (meetingInfo) {
                        window.meetingData = JSON.parse(meetingInfo);
                        Object.freeze(window.meetingData);
                    }
                } catch (error) {
                    console.error('Error initializing meeting data:', error);
                }
            })();
        </script>
    </head>
    <body class="bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-gradient-to-r from-indigo-600 to-blue-500 shadow-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ url('/') }}" class="flex items-center">
                                <svg class="h-8 w-8 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-2xl font-bold text-white">Focus Tracker</span>
                            </a>
                        </div>
                        @auth
                            <div class="hidden sm:ml-6 sm:flex sm:space-x-4">
                                @if(auth()->user()->role === 'teacher')
                                    <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-indigo-700 transition-colors duration-150">
                                        <svg class="h-5 w-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Dashboard
                                    </a>
                                    <a href="{{ route('meetings.index') }}" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-indigo-700 transition-colors duration-150">
                                        <svg class="h-5 w-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        Meetings
                                    </a>
                                @else
                                    <a href="{{ route('student.dashboard') }}" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-indigo-700 transition-colors duration-150">
                                        <svg class="h-5 w-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Dashboard
                                    </a>
                                    <a href="{{ route('student.meetings') }}" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-indigo-700 transition-colors duration-150">
                                        <svg class="h-5 w-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        My Meetings
                                    </a>
                                @endif
                            </div>
                        @endauth
                    </div>
                    <div class="flex items-center">
                        @auth
                            <div class="ml-3 relative">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-indigo-700 transition-colors duration-150">
                                        <svg class="h-5 w-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-indigo-700 transition-colors duration-150">
                                <svg class="h-5 w-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                Login
                            </a>
                            <a href="{{ route('register') }}" class="ml-4 inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-indigo-700 transition-colors duration-150">
                                <svg class="h-5 w-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                Register
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            @yield('content')
        </main>

        <!-- Scripts -->
        @stack('scripts')
    </body>
</html>
