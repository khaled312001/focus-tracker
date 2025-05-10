@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-gray dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Meeting Details</h2>
                    <a href="{{ route('teacher.dashboard') }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">Back to Dashboard</a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <h3 class="text-lg font-medium mb-2">Meeting Information</h3>
                        <p class="text-gray-600 dark:text-gray-400">Title: {{ $meeting->title }}</p>
                        <p class="text-gray-600 dark:text-gray-400">Status: <span class="font-medium {{ $meeting->status === 'active' ? 'text-green-600' : 'text-gray-600' }}">{{ ucfirst($meeting->status) }}</span></p>
                        <p class="text-gray-600 dark:text-gray-400">Created: {{ $meeting->created_at->format('M d, Y H:i') }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <h3 class="text-lg font-medium mb-2">Attendance</h3>
                        <p class="text-gray-600 dark:text-gray-400">Total Students: {{ $totalStudents }}</p>
                        <p class="text-gray-600 dark:text-gray-400">Present: {{ $presentStudents }}</p>
                        <p class="text-gray-600 dark:text-gray-400">Absent: {{ $totalStudents - $presentStudents }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <h3 class="text-lg font-medium mb-2">Focus Statistics</h3>
                        <p class="text-gray-600 dark:text-gray-400">Average Focus: {{ number_format($averageFocus, 1) }}%</p>
                        <p class="text-gray-600 dark:text-gray-400">Total Focus Logs: {{ $meeting->focusLogs->count() }}</p>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-lg font-medium mb-4">Students</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Average Focus</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($meeting->students as $student)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $student->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $student->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $student->pivot->is_present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $student->pivot->is_present ? 'Present' : 'Absent' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        {{ number_format($meeting->focusLogs->where('student_id', $student->id)->avg('focus_level') ?? 0, 1) }}%
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium mb-4">Focus Logs</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Focus Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($meeting->focusLogs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $log->student->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format($log->focus_level, 1) }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 