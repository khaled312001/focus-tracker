@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Student Reports</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Comprehensive analysis of student performance and focus metrics</p>
            </div>

            <!-- Student Overview -->
            <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Student Overview</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($students as $student)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $student->name }}</h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">ID: {{ $student->id }}</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300">Average Focus</span>
                                <span class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                    {{ number_format($student->focusStats->avg('focus_level'), 1) }}%
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300">Meetings Attended</span>
                                <span class="text-lg font-semibold text-green-600 dark:text-green-400">
                                    {{ $student->meetings->count() }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300">Total Focus Time</span>
                                <span class="text-lg font-semibold text-purple-600 dark:text-purple-400">
                                    {{ floor($student->focusStats->sum('duration') / 60) }}h {{ $student->focusStats->sum('duration') % 60 }}m
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Focus Distribution Chart -->
            <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Focus Distribution</h2>
                <div class="h-96">
                    <canvas id="focusDistributionChart" 
                            data-distribution="{{ json_encode($focusDistribution) }}"
                            data-labels="{{ json_encode(['0-20%', '21-40%', '41-60%', '61-80%', '81-100%']) }}"></canvas>
                </div>
            </div>

            <!-- Student Performance Chart -->
            <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Student Performance</h2>
                <div class="h-96">
                    <canvas id="studentPerformanceChart" 
                            data-names="{{ json_encode($students->pluck('name')) }}"
                            data-levels="{{ json_encode($students->map(fn($student) => $student->focusStats->avg('focus_level'))) }}"></canvas>
                </div>
            </div>

            <!-- Detailed Focus Logs -->
            <div class="bg-gray dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Focus Logs</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Meeting</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Focus Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($focusLogs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->student->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $log->meeting->title }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $log->created_at->format('M d, Y H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ number_format($log->focus_level, 1) }}%</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ floor($log->duration / 60) }}m {{ $log->duration % 60 }}s</div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Focus Distribution Chart
    const focusCanvas = document.getElementById('focusDistributionChart');
    const focusCtx = focusCanvas.getContext('2d');
    const focusDistributionData = JSON.parse(focusCanvas.dataset.distribution);
    const focusLabels = JSON.parse(focusCanvas.dataset.labels);
    
    new Chart(focusCtx, {
        type: 'pie',
        data: {
            labels: focusLabels,
            datasets: [{
                data: focusDistributionData,
                backgroundColor: [
                    'rgb(239, 68, 68)',
                    'rgb(234, 179, 8)',
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)',
                    'rgb(16, 185, 129)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Student Performance Chart
    const performanceCanvas = document.getElementById('studentPerformanceChart');
    const performanceCtx = performanceCanvas.getContext('2d');
    const studentNames = JSON.parse(performanceCanvas.dataset.names);
    const studentFocusLevels = JSON.parse(performanceCanvas.dataset.levels);
    
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: studentNames,
            datasets: [{
                label: 'Average Focus',
                data: studentFocusLevels,
                backgroundColor: 'rgb(59, 130, 246)',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Focus Level (%)'
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection 