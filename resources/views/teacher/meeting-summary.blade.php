@extends('layouts.app')

@section('title', 'Meeting Summary')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">{{ $meeting->title }} - Meeting Summary</h1>
        <p class="text-gray-600 mt-2">Duration: {{ $duration }} minutes</p>
    </div>

    <!-- Focus Statistics -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Focus Statistics</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-sm text-blue-600">Average Focus</p>
                <p class="text-2xl font-bold text-blue-700">{{ number_format($focusStats['average'], 1) }}%</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <p class="text-sm text-green-600">Highest Focus</p>
                <p class="text-2xl font-bold text-green-700">{{ number_format($focusStats['highest'], 1) }}%</p>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <p class="text-sm text-yellow-600">Lowest Focus</p>
                <p class="text-2xl font-bold text-yellow-700">{{ number_format($focusStats['lowest'], 1) }}%</p>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <p class="text-sm text-purple-600">Total Logs</p>
                <p class="text-2xl font-bold text-purple-700">{{ $focusStats['total_logs'] }}</p>
            </div>
        </div>
    </div>

    <!-- Focus Distribution -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Focus Distribution</h2>
        <div class="space-y-4">
            @foreach($focusDistribution as $category => $count)
            <div class="flex items-center">
                <div class="w-32 text-sm text-gray-600">{{ $category }}</div>
                <div class="flex-1">
                    <div class="h-4 bg-gray-200 rounded-full">
                        <div class="h-4 bg-blue-500 rounded-full" style="width: {{ ($count / $focusStats['total_logs']) * 100 }}%"></div>
                    </div>
                </div>
                <div class="w-16 text-right text-sm text-gray-600">{{ $count }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Student Performance -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Participant Performance</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Focus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Highest Focus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Focus Logs</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($participants as $participant)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $participant->user->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $participant->user->role === 'teacher' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' }}">
                                    {{ ucfirst($participant->user->role) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                @if($participant->focusLogs->isNotEmpty())
                                    {{ number_format($participant->focusLogs->avg('focus_level'), 1) }}%
                                @else
                                    0.0%
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                @if($participant->focusLogs->isNotEmpty())
                                    {{ number_format($participant->focusLogs->max('focus_level'), 1) }}%
                                @else
                                    0.0%
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $participant->focusLogs->count() }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection 