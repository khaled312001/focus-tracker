@extends('layouts.app')

@section('title', 'Focus Statistics')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Focus Statistics</h1>
            <p class="text-gray-600 dark:text-gray-400">Track and analyze your focus performance across all meetings</p>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Average Focus -->
            <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Average Focus</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($averageFocus, 1) }}%</p>
                    </div>
                </div>
            </div>

            <!-- Total Meetings -->
            <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Meetings</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $focusLogs->unique('meeting_id')->count() }}</p>
                    </div>
                </div>
            </div>

            <!-- Focus Minutes -->
            <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Focus Time</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($focusLogs->count() * 5 / 60, 1) }}h</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Focus Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Focus Level Distribution -->
            <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Focus Level Distribution</h2>
                <div class="space-y-4">
                    @foreach($focusDistribution as $level => $count)
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $level }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $count }} logs</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full {{ str_contains($level, 'High') ? 'bg-green-600' : (str_contains($level, 'Good') ? 'bg-blue-600' : (str_contains($level, 'Moderate') ? 'bg-yellow-600' : (str_contains($level, 'Low') ? 'bg-orange-600' : 'bg-red-600'))) }}"
                                     style="width: {{ ($count / $focusLogs->count()) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Focus Trend -->
            <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Focus Trend (Last 7 Days)</h2>
                <div class="h-64">
                    <canvas id="focusTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Focus Logs -->
        <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Focus Logs</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Meeting</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Focus Level</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($focusLogs->take(10) as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->meeting->title }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $log->created_at->format('M j, Y H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-2">
                                            <div class="h-2.5 rounded-full {{ $log->focus_level >= 80 ? 'bg-green-600' : ($log->focus_level >= 60 ? 'bg-blue-600' : ($log->focus_level >= 40 ? 'bg-yellow-600' : ($log->focus_level >= 20 ? 'bg-orange-600' : 'bg-red-600'))) }}"
                                                 style="width: {{ $log->focus_level }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ number_format($log->focus_level, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('focusTrendChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($focusTrend->toArray())) !!},
            datasets: [{
                label: 'Average Focus Level',
                data: {!! json_encode(array_values($focusTrend->toArray())) !!},
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection 