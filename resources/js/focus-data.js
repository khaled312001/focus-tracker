/**
 * Focus Data Helper Functions
 * Provides utilities for fetching and displaying focus data from the database
 */

/**
 * Fetch focus data for a meeting
 * 
 * @param {number} meetingId - The meeting ID
 * @param {Object} options - Optional parameters for filtering
 * @returns {Promise} - Promise resolving to the focus data
 */
export async function fetchMeetingFocusData(meetingId, options = {}) {
    // Build query parameters
    const queryParams = new URLSearchParams();
    
    if (options.studentId) {
        queryParams.append('student_id', options.studentId);
    }
    
    if (options.timeRange) {
        queryParams.append('time_range', options.timeRange);
    }
    
    if (options.minFocusLevel) {
        queryParams.append('min_focus_level', options.minFocusLevel);
    }
    
    if (options.maxFocusLevel) {
        queryParams.append('max_focus_level', options.maxFocusLevel);
    }
    
    if (options.perPage) {
        queryParams.append('per_page', options.perPage);
    }
    
    if (options.page) {
        queryParams.append('page', options.page);
    }
    
    if (options.order) {
        queryParams.append('order', options.order);
    }
    
    // Build URL with query parameters
    const url = `/api/focus-logs/meeting/${meetingId}?${queryParams.toString()}`;
    
    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(`HTTP error! status: ${response.status}${errorData.message ? ` - ${errorData.message}` : ''}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error fetching meeting focus data:', error);
        throw error;
    }
}

/**
 * Fetch focus history for a student
 * 
 * @param {number} studentId - The student ID
 * @param {Object} options - Optional parameters for filtering
 * @returns {Promise} - Promise resolving to the student focus history
 */
export async function fetchStudentFocusHistory(studentId, options = {}) {
    // Build query parameters
    const queryParams = new URLSearchParams();
    
    if (options.meetingId) {
        queryParams.append('meeting_id', options.meetingId);
    }
    
    if (options.startDate) {
        queryParams.append('start_date', options.startDate);
    }
    
    if (options.endDate) {
        queryParams.append('end_date', options.endDate);
    }
    
    if (options.perPage) {
        queryParams.append('per_page', options.perPage);
    }
    
    if (options.page) {
        queryParams.append('page', options.page);
    }
    
    if (options.order) {
        queryParams.append('order', options.order);
    }
    
    // Build URL with query parameters
    const url = `/api/focus-logs/student/${studentId}?${queryParams.toString()}`;
    
    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(`HTTP error! status: ${response.status}${errorData.message ? ` - ${errorData.message}` : ''}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error fetching student focus history:', error);
        throw error;
    }
}

/**
 * Fetch the latest focus data for a student
 * 
 * @param {number} studentId - The student ID
 * @returns {Promise} - Promise resolving to the latest focus data
 */
export async function fetchLatestFocusData(studentId) {
    try {
        const response = await fetch(`/api/focus-logs/${studentId}/latest`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(`HTTP error! status: ${response.status}${errorData.message ? ` - ${errorData.message}` : ''}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error fetching latest focus data:', error);
        throw error;
    }
}

/**
 * Render focus data to a table element
 * 
 * @param {HTMLElement} tableElement - The table element to render data to
 * @param {Object} focusData - The focus data object from the API
 */
export function renderFocusDataTable(tableElement, focusData) {
    if (!tableElement || !focusData || !focusData.data) {
        console.error('Invalid parameters for renderFocusDataTable');
        return;
    }
    
    // Clear existing table content
    tableElement.innerHTML = '';
    
    // Create table header
    const thead = document.createElement('thead');
    thead.classList.add('bg-gray-50', 'dark:bg-gray-700');
    
    const headerRow = document.createElement('tr');
    ['Student', 'Focus Level', 'Time'].forEach(text => {
        const th = document.createElement('th');
        th.classList.add('px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', 'text-gray-500', 'dark:text-gray-300', 'uppercase', 'tracking-wider');
        th.textContent = text;
        headerRow.appendChild(th);
    });
    
    thead.appendChild(headerRow);
    tableElement.appendChild(thead);
    
    // Create table body
    const tbody = document.createElement('tbody');
    tbody.classList.add('bg-white', 'dark:bg-gray-800', 'divide-y', 'divide-gray-200', 'dark:divide-gray-700');
    
    // Add data rows
    focusData.data.forEach((log, index) => {
        const row = document.createElement('tr');
        row.classList.add(index % 2 === 0 ? 'bg-white' : 'bg-gray-50', 'dark:bg-gray-800', 'hover:bg-gray-100', 'dark:hover:bg-gray-700');
        
        // Student name cell
        const nameCell = document.createElement('td');
        nameCell.classList.add('px-6', 'py-4', 'whitespace-nowrap');
        nameCell.textContent = log.student_name;
        row.appendChild(nameCell);
        
        // Focus level cell
        const focusCell = document.createElement('td');
        focusCell.classList.add('px-6', 'py-4', 'whitespace-nowrap');
        
        const focusBar = document.createElement('div');
        focusBar.classList.add('flex', 'items-center');
        
        const barContainer = document.createElement('div');
        barContainer.classList.add('w-full', 'h-2', 'bg-gray-200', 'rounded-full');
        
        const barFill = document.createElement('div');
        barFill.classList.add('h-full', 'rounded-full');
        barFill.style.width = `${log.focus_level}%`;
        
        // Set color based on focus level
        if (log.focus_level >= 80) {
            barFill.classList.add('bg-green-500');
        } else if (log.focus_level >= 60) {
            barFill.classList.add('bg-teal-500');
        } else if (log.focus_level >= 40) {
            barFill.classList.add('bg-yellow-500');
        } else if (log.focus_level >= 20) {
            barFill.classList.add('bg-orange-500');
        } else {
            barFill.classList.add('bg-red-500');
        }
        
        barContainer.appendChild(barFill);
        
        const focusText = document.createElement('span');
        focusText.classList.add('ml-2', 'text-sm', 'font-medium', 'text-gray-700', 'dark:text-gray-300');
        focusText.textContent = `${Math.round(log.focus_level)}%`;
        
        focusBar.appendChild(barContainer);
        focusBar.appendChild(focusText);
        focusCell.appendChild(focusBar);
        row.appendChild(focusCell);
        
        // Timestamp cell
        const timeCell = document.createElement('td');
        timeCell.classList.add('px-6', 'py-4', 'whitespace-nowrap', 'text-sm', 'text-gray-500', 'dark:text-gray-400');
        
        const date = new Date(log.timestamp);
        timeCell.textContent = date.toLocaleString();
        
        row.appendChild(timeCell);
        tbody.appendChild(row);
    });
    
    tableElement.appendChild(tbody);
    
    // Add pagination if available
    if (focusData.pagination && focusData.pagination.total > focusData.pagination.per_page) {
        const paginationContainer = document.createElement('div');
        paginationContainer.classList.add('flex', 'justify-between', 'items-center', 'px-4', 'py-3', 'bg-white', 'dark:bg-gray-800', 'border-t', 'border-gray-200', 'dark:border-gray-700', 'sm:px-6');
        
        // Add pagination info
        const info = document.createElement('div');
        info.classList.add('text-sm', 'text-gray-700', 'dark:text-gray-300');
        info.textContent = `Showing ${(focusData.pagination.current_page - 1) * focusData.pagination.per_page + 1} to ${Math.min(focusData.pagination.current_page * focusData.pagination.per_page, focusData.pagination.total)} of ${focusData.pagination.total} results`;
        
        paginationContainer.appendChild(info);
        
        // Add pagination controls
        const controls = document.createElement('div');
        controls.classList.add('flex', 'space-x-2');
        
        // Previous button
        if (focusData.pagination.current_page > 1) {
            const prevButton = document.createElement('button');
            prevButton.classList.add('px-3', 'py-1', 'rounded', 'bg-gray-200', 'dark:bg-gray-600', 'text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-300', 'dark:hover:bg-gray-500');
            prevButton.textContent = 'Previous';
            prevButton.dataset.page = focusData.pagination.current_page - 1;
            controls.appendChild(prevButton);
        }
        
        // Next button
        if (focusData.pagination.current_page < focusData.pagination.last_page) {
            const nextButton = document.createElement('button');
            nextButton.classList.add('px-3', 'py-1', 'rounded', 'bg-gray-200', 'dark:bg-gray-600', 'text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-300', 'dark:hover:bg-gray-500');
            nextButton.textContent = 'Next';
            nextButton.dataset.page = focusData.pagination.current_page + 1;
            controls.appendChild(nextButton);
        }
        
        paginationContainer.appendChild(controls);
        
        // Append pagination to a div after the table
        const paginationWrapper = document.createElement('div');
        paginationWrapper.appendChild(paginationContainer);
        
        tableElement.parentNode.insertBefore(paginationWrapper, tableElement.nextSibling);
    }
}

/**
 * Render focus statistics to a container element
 * 
 * @param {HTMLElement} containerElement - The container element to render stats to
 * @param {Object} focusData - The focus data object from the API
 */
export function renderFocusStatistics(containerElement, focusData) {
    if (!containerElement || !focusData || !focusData.aggregated_data) {
        console.error('Invalid parameters for renderFocusStatistics');
        return;
    }
    
    // Clear existing content
    containerElement.innerHTML = '';
    
    // Create statistics grid
    const statsGrid = document.createElement('div');
    statsGrid.classList.add('grid', 'grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-4', 'gap-4', 'mb-8');
    
    // Add overall stats
    const overallStats = document.createElement('div');
    overallStats.classList.add('bg-white', 'dark:bg-gray-800', 'rounded-lg', 'shadow', 'p-6');
    overallStats.innerHTML = `
        <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-white">Overall Statistics</h3>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Total Data Points:</span>
                <span class="font-medium text-gray-800 dark:text-white">${
                    Object.values(focusData.aggregated_data).reduce((sum, student) => sum + parseInt(student.data_points), 0)
                }</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Average Focus:</span>
                <span class="font-medium text-gray-800 dark:text-white">${
                    Math.round(
                        Object.values(focusData.aggregated_data)
                        .reduce((sum, student) => sum + parseFloat(student.average_focus) * parseInt(student.data_points), 0) / 
                        Object.values(focusData.aggregated_data).reduce((sum, student) => sum + parseInt(student.data_points), 0)
                    )
                }%</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Students:</span>
                <span class="font-medium text-gray-800 dark:text-white">${Object.keys(focusData.aggregated_data).length}</span>
            </div>
        </div>
    `;
    
    statsGrid.appendChild(overallStats);
    containerElement.appendChild(statsGrid);
} 