@extends('layouts.app')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 py-8">
    <div class="container mx-auto px-4">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Focus Data</h1>
            <p class="text-gray-600 dark:text-gray-400">View and analyze focus data from the database</p>
        </div>
        
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8 border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filters</h2>
            
            <form id="filter-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Meeting selection -->
                <div>
                    <label for="meeting-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meeting</label>
                    <select id="meeting-select" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="">Loading meetings...</option>
                    </select>
                </div>
                
                <!-- Student selection -->
                <div>
                    <label for="student-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student</label>
                    <select id="student-select" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="">All Students</option>
                    </select>
                </div>
                
                <!-- Time range selection -->
                <div>
                    <label for="time-range" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time Range</label>
                    <select id="time-range" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="last_hour">Last Hour</option>
                        <option value="last_30_minutes">Last 30 Minutes</option>
                        <option value="last_15_minutes">Last 15 Minutes</option>
                        <option value="last_5_minutes">Last 5 Minutes</option>
                    </select>
                </div>
                
                <!-- Focus level range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Focus Level</label>
                    <div class="flex space-x-2 items-center">
                        <input id="min-focus" type="number" min="0" max="100" placeholder="Min" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <span class="text-gray-500 dark:text-gray-400">-</span>
                        <input id="max-focus" type="number" min="0" max="100" placeholder="Max" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>
                </div>
                
                <div class="md:col-span-2 lg:col-span-4 flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Statistics -->
        <div id="focus-statistics" class="mb-8">
            <!-- Statistics will be rendered here -->
            <div class="flex justify-center items-center h-32 bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-200 dark:border-gray-700">
                <div class="animate-pulse flex space-x-4">
                    <div class="flex-1 space-y-4 py-1">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="space-y-2">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data table -->
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table id="focus-data-table" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Focus Level</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex justify-center items-center h-16">
                                    <div class="animate-pulse flex space-x-4">
                                        <div class="flex-1 space-y-2 py-1">
                                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mx-auto"></div>
                                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2 mx-auto"></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import { fetchMeetingFocusData, renderFocusDataTable, renderFocusStatistics } from '/js/focus-data.js';
    
    // DOM elements
    const filterForm = document.getElementById('filter-form');
    const meetingSelect = document.getElementById('meeting-select');
    const studentSelect = document.getElementById('student-select');
    const timeRangeSelect = document.getElementById('time-range');
    const minFocusInput = document.getElementById('min-focus');
    const maxFocusInput = document.getElementById('max-focus');
    const focusDataTable = document.getElementById('focus-data-table');
    const focusStatistics = document.getElementById('focus-statistics');
    
    // Current meeting ID
    let currentMeetingId = null;
    
    // Load meetings
    async function loadMeetings() {
        try {
            const response = await fetch('/api/meetings', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const meetings = await response.json();
            
            // Clear existing options
            meetingSelect.innerHTML = '';
            
            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select a Meeting';
            meetingSelect.appendChild(defaultOption);
            
            // Add meeting options
            meetings.forEach(meeting => {
                const option = document.createElement('option');
                option.value = meeting.id;
                option.textContent = meeting.title;
                meetingSelect.appendChild(option);
            });
            
            // If there's a meeting in the URL, select it
            const urlParams = new URLSearchParams(window.location.search);
            const meetingId = urlParams.get('meeting_id');
            
            if (meetingId) {
                meetingSelect.value = meetingId;
                loadMeetingData(meetingId);
                loadStudents(meetingId);
            }
        } catch (error) {
            console.error('Error loading meetings:', error);
            meetingSelect.innerHTML = '<option value="">Error loading meetings</option>';
        }
    }
    
    // Load students for a meeting
    async function loadStudents(meetingId) {
        try {
            const response = await fetch(`/api/meetings/${meetingId}/students`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const students = await response.json();
            
            // Clear existing options
            studentSelect.innerHTML = '';
            
            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'All Students';
            studentSelect.appendChild(defaultOption);
            
            // Add student options
            students.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = student.name;
                studentSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading students:', error);
            studentSelect.innerHTML = '<option value="">Error loading students</option>';
        }
    }
    
    // Load meeting focus data
    async function loadMeetingData(meetingId, options = {}) {
        try {
            const data = await fetchMeetingFocusData(meetingId, options);
            
            // Render data
            renderFocusDataTable(focusDataTable, data);
            renderFocusStatistics(focusStatistics, data);
            
            // Update URL
            const urlParams = new URLSearchParams();
            urlParams.set('meeting_id', meetingId);
            
            if (options.studentId) {
                urlParams.set('student_id', options.studentId);
            }
            
            if (options.timeRange) {
                urlParams.set('time_range', options.timeRange);
            }
            
            if (options.minFocusLevel) {
                urlParams.set('min_focus', options.minFocusLevel);
            }
            
            if (options.maxFocusLevel) {
                urlParams.set('max_focus', options.maxFocusLevel);
            }
            
            history.replaceState(null, '', `?${urlParams.toString()}`);
        } catch (error) {
            console.error('Error loading meeting data:', error);
            
            // Show error in table
            focusDataTable.innerHTML = `
                <tbody>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-red-500">
                            Error loading focus data: ${error.message}
                        </td>
                    </tr>
                </tbody>
            `;
            
            // Clear statistics
            focusStatistics.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-red-200 dark:border-red-900">
                    <h3 class="text-lg font-semibold text-red-500 mb-2">Error Loading Statistics</h3>
                    <p class="text-gray-600 dark:text-gray-400">${error.message}</p>
                </div>
            `;
        }
    }
    
    // Handle filter form submission
    filterForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        const meetingId = meetingSelect.value;
        
        if (!meetingId) {
            alert('Please select a meeting');
            return;
        }
        
        // Get filter values
        const options = {
            studentId: studentSelect.value,
            timeRange: timeRangeSelect.value,
            minFocusLevel: minFocusInput.value,
            maxFocusLevel: maxFocusInput.value
        };
        
        // Load data with filters
        loadMeetingData(meetingId, options);
    });
    
    // Handle meeting selection change
    meetingSelect.addEventListener('change', () => {
        const meetingId = meetingSelect.value;
        
        if (meetingId) {
            loadStudents(meetingId);
            currentMeetingId = meetingId;
        } else {
            studentSelect.innerHTML = '<option value="">All Students</option>';
            currentMeetingId = null;
        }
    });
    
    // Initialize
    document.addEventListener('DOMContentLoaded', loadMeetings);
    
    // Handle pagination clicks
    document.addEventListener('click', (event) => {
        if (event.target.matches('button[data-page]') && currentMeetingId) {
            const page = event.target.dataset.page;
            
            // Get current filters
            const options = {
                studentId: studentSelect.value,
                timeRange: timeRangeSelect.value,
                minFocusLevel: minFocusInput.value,
                maxFocusLevel: maxFocusInput.value,
                page: page
            };
            
            // Load data with pagination
            loadMeetingData(currentMeetingId, options);
        }
    });
</script>
@endpush 