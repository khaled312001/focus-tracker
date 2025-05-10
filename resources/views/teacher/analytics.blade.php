@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Focus Analytics</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Detailed analysis of student focus across all meetings</p>
            </div>

            <!-- Focus Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Meetings</h3>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ $meetings->count() }}</p>
                </div>

                <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Average Focus</h3>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                        {{ number_format($focusStats->avg('avg_focus'), 1) }}%
                    </p>
                </div>

                <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Students</h3>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                        {{ $meetings->sum('students.count') }}
                    </p>
                </div>
            </div>

            <!-- Detailed Analytics -->
            <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Meeting Analytics</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Meeting</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Students</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Average Focus</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Focus Distribution</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($meetings as $meeting)
                                @php
                                    $focusLevels = $meeting->focusLogs->groupBy(function($log) {
                                        return floor($log->focus_level / 20) * 20;
                                    });
                                    $totalFocusLogs = max($meeting->focusLogs->count(), 1);
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $meeting->title }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $meeting->created_at->format('M d, Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $meeting->students->count() }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ number_format($meeting->focusLogs->avg('focus_level') ?? 0, 1) }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-1">
                                            @foreach([0, 20, 40, 60, 80] as $level)
                                                @php
                                                    $levelCount = $focusLevels[$level]->count() ?? 0;
                                                    $percentage = round(($levelCount / $totalFocusLogs) * 100, 1);
                                                @endphp
                                                <div class="flex-1 h-4 bg-gray-200 dark:bg-gray-700 rounded">
                                                    <div class="h-full bg-blue-600 rounded" @style(['width' => $percentage . '%'])></div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Focus Trends Chart -->
            <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6 mt-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Focus Trends</h2>
                <div class="h-96">
                    <canvas id="focusTrendsChart" 
                        data-focus-stats="{{ json_encode([
                            'labels' => $focusStats->pluck('date'),
                            'data' => $focusStats->pluck('avg_focus')
                        ]) }}"
                    ></canvas>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/focus-analytics.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('focusTrendsChart');
        const chartData = JSON.parse(canvas.dataset.focusStats);
        initializeFocusChart(chartData.labels, chartData.data);
    });
</script>
@endpush
@endsection
