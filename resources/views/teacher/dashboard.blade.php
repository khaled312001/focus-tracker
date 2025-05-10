@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 overflow-hidden shadow-lg rounded-xl mb-8">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Welcome, {{ $user->name }}!</h2>
                            <p class="text-gray-600 dark:text-gray-400">Your active meetings: <span class="font-semibold">{{ $activeMeetings->count() }}</span></p>
                        </div>
                        <a href="{{ route('meetings.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create Meeting
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Active Students Card -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-gray-800 dark:to-gray-700 overflow-hidden shadow-lg rounded-xl transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-500 bg-opacity-20">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Active Students</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $activeStudents }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Meetings Card -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-gray-800 dark:to-gray-700 overflow-hidden shadow-lg rounded-xl transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-500 bg-opacity-20">
                                <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-green-600 dark:text-green-400">Total Meetings</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalMeetings }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Focus Card -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-gray-800 dark:to-gray-700 overflow-hidden shadow-lg rounded-xl transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-500 bg-opacity-20">
                                <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Average Focus</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($averageFocus, 1) }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Meetings -->
            <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 overflow-hidden shadow-lg rounded-xl mb-8">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Active Meetings</h3>
                        <a href="{{ route('meetings.create') }}" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            New Meeting
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Students</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Start Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($activeMeetings as $meeting)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $meeting->title }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $meeting->students->count() }} students</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $meeting->created_at->format('M d, Y H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('teacher.meeting', $meeting) }}" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                                                    View Meeting
                                                </a>
                                                <a href="{{ route('teacher.meetings.join', $meeting) }}" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-150">
                                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    Join Meeting
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No active meetings</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Focus Statistics -->
            <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 overflow-hidden shadow-lg rounded-xl">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Focus Statistics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Focus Level Chart -->
                        <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Average Focus Level</h4>
                                <div class="flex items-center space-x-2">
                                    <button class="focus-time-btn active" data-time="week">Week</button>
                                    <button class="focus-time-btn" data-time="month">Month</button>
                                </div>
                            </div>
                            <div class="relative h-64">
                                <canvas id="focusChart"></canvas>
                            </div>
                        </div>

                        <!-- Student Performance Chart -->
                        <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Student Performance</h4>
                                <div class="flex items-center space-x-2">
                                    <button class="performance-type-btn active" data-type="focus">Focus</button>
                                    <button class="performance-type-btn" data-type="attendance">Attendance</button>
                                </div>
                            </div>
                            <div class="relative h-64">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Comparison Form -->
                    <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Student Comparison</h4>
                            <div class="flex items-center space-x-2">
                                <button class="comparison-type-btn active" data-type="focus">Focus</button>
                                <button class="comparison-type-btn" data-type="attendance">Attendance</button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Average Focus</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Meetings Attended</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Trend</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-gray dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($students as $student)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $student->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <div class="flex items-center">
                                                    <div class="w-16 bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mr-2">
                                                        @php
                                                            $focusPercentage = $student->focusLogs->avg('focus_level') ?? 0;
                                                        @endphp
                                                        <div class="bg-blue-600 h-2.5 rounded-full focus-progress" data-focus="{{ $focusPercentage }}"></div>
                                                    </div>
                                                    <span>{{ number_format($focusPercentage, 1) }}%</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $student->studentMeetings->count() }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @php
                                                    $recentFocus = $student->focusLogs->where('created_at', '>=', now()->subDays(7))->avg('focus_level') ?? 0;
                                                    $previousFocus = $student->focusLogs->where('created_at', '<', now()->subDays(7))->avg('focus_level') ?? 0;
                                                    $trend = $recentFocus - $previousFocus;
                                                @endphp
                                                
                                                @if($trend > 0)
                                                    <span class="text-green-500 flex items-center">
                                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                                        </svg>
                                                        Improving
                                                    </span>
                                                @elseif($trend < 0)
                                                    <span class="text-red-500 flex items-center">
                                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"/>
                                                        </svg>
                                                        Declining
                                                    </span>
                                                @else
                                                    <span class="text-gray-500 flex items-center">
                                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/>
                                                        </svg>
                                                        Stable
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No student data available</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    @vite(['resources/css/dashboard.css'])
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const isDarkMode = document.documentElement.classList.contains('dark');
        const textColor = isDarkMode ? '#9CA3AF' : '#6B7280';
        const gridColor = isDarkMode ? 'rgba(75, 85, 99, 0.2)' : 'rgba(156, 163, 175, 0.2)';

        // Get data from PHP variables
        const focusData = JSON.parse('{!! json_encode($focusData) !!}');
        const focusLabels = JSON.parse('{!! json_encode($focusLabels) !!}');
        const performanceData = JSON.parse('{!! json_encode($performanceData) !!}');
        const performanceLabels = JSON.parse('{!! json_encode($performanceLabels) !!}');
        
        // Focus Level Chart
        const focusCtx = document.getElementById('focusChart').getContext('2d');
        const focusChart = new Chart(focusCtx, {
            type: 'line',
            data: {
                labels: focusLabels,
                datasets: [{
                    label: 'Focus Level',
                    data: focusData,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3B82F6',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: isDarkMode ? '#374151' : '#374151',
                        titleColor: isDarkMode ? '#F3F4F6' : '#111827',
                        bodyColor: isDarkMode ? '#D1D5DB' : '#4B5563',
                        borderColor: isDarkMode ? '#4B5563' : '#E5E7EB',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `Focus Level: ${context.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // Student Performance Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: performanceLabels,
                datasets: [{
                    label: 'Average Focus',
                    data: performanceData,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: isDarkMode ? '#374151' : '#374151',
                        titleColor: isDarkMode ? '#F3F4F6' : '#111827',
                        bodyColor: isDarkMode ? '#D1D5DB' : '#4B5563',
                        borderColor: isDarkMode ? '#4B5563' : '#E5E7EB',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `Average Focus: ${context.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        // Handle time period buttons
        document.querySelectorAll('.focus-time-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.focus-time-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // Here you would update the chart data based on the selected time period
            });
        });

        // Handle performance type buttons
        document.querySelectorAll('.performance-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.performance-type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // Here you would update the chart data based on the selected performance type
            });
        });
        
        // Handle comparison type buttons
        document.querySelectorAll('.comparison-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.comparison-type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // Here you would update the comparison data based on the selected type
            });
        });

        // Set progress bar widths
        document.querySelectorAll('.focus-progress').forEach(function(progressBar) {
            const focusPercentage = progressBar.getAttribute('data-focus');
            progressBar.style.width = focusPercentage + '%';
        });
    });
    </script>
    @endpush
@endsection 