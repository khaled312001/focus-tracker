// Student tracking
let students = new Map();
let meetingStartTime = Date.now();
let focusUpdateInterval;
let lastAverages = [];
let channel;

// Initialize the meeting room
function initializeTeacherMeeting() {
    // Get meeting data from window object
    const { meetingId, userId, userName } = window.meetingData;
    
    console.log(`Initializing teacher meeting: ${meetingId}`);
    console.log(`Teacher: ${userName} (${userId})`);
    
    // Join the meeting channel using our new Socket.IO interface
    if (window.socket) {
        console.log('Socket available, joining meeting channel');
        
        // Check if the socket is connected
        if (!window.socket.connected) {
            console.log('Socket not connected, waiting for connection...');
            
            // Set up a one-time event listener for connect event
            window.socket.once('connect', () => {
                console.log('Socket connected, now joining meeting channel');
                joinMeetingAsTeacher();
            });
        } else {
            console.log('Socket already connected, joining meeting channel immediately');
            joinMeetingAsTeacher();
        }
    } else {
        console.error('Socket not available - check if websocket-server.js is running');
        
        // Show error notification
        showNotification('WebSocket server not available. Some features may not work properly.', 'error');
    }
    
    // Add event listener for end meeting button
    const endMeetingButton = document.getElementById('end-meeting');
    if (endMeetingButton) {
        endMeetingButton.addEventListener('click', endMeeting);
    }

    // Start tracking session duration
    updateSessionDuration();
    setInterval(updateSessionDuration, 1000);

    // Start focus tracking
    focusUpdateInterval = setInterval(updateFocusMetrics, 5000);

    // Initialize video controls if needed
    initializeVideoControls();
    
    // Immediately fetch data from database
    fetchFocusDataFromDatabase();
    
    // Update student count
    updateStudentCount();
    
    // Initial focus metrics
    updateFocusMetrics();
}

// Helper function to join meeting as teacher
function joinMeetingAsTeacher() {
    // Get meeting data
    const { meetingId, userId, userName } = window.meetingData;
    
    // Join the meeting room
    channel = window.joinMeetingChannel(meetingId, {
        userId,
        userName,
        userRole: 'teacher'
    });
    
    // Announce teacher presence
    socket.emit('join-meeting', {
        meetingId,
        userId,
        userName,
        userRole: 'teacher'
    });
    
    // Set up event listeners for student activities
    setupSocketEvents();
    
    // Show notification
    showNotification('Connected to meeting room');
}

// Update student count 
function updateStudentCount() {
    const count = students.size;
    const studentCountElement = document.getElementById('student-count');
    if (studentCountElement) {
        studentCountElement.textContent = count;
    }
    
    // Show/hide empty state
    const emptyState = document.getElementById('empty-state');
    if (emptyState) {
        if (count === 0) {
            emptyState.classList.remove('hidden');
        } else {
            emptyState.classList.add('hidden');
        }
    }
}

// End meeting function
function endMeeting() {
    // Confirm with user
    if (!confirm('Are you sure you want to end this meeting for all participants?')) {
        return;
    }
    
    // Get meeting data
    const { meetingId } = window.meetingData;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Send end meeting request
    fetch(`/api/meetings/${meetingId}/end`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Meeting ended:', data);
        
        // Broadcast end meeting event to all students
        if (window.socket && window.socket.connected) {
            socket.emit('end-meeting', {
                meetingId: meetingId
            });
        }
        
        // Redirect to meeting summary page
        window.location.href = `/teacher/meetings/${meetingId}/summary`;
    })
    .catch(error => {
        console.error('Error ending meeting:', error);
        showNotification('Failed to end meeting. Please try again.', 'error');
    });
}

// Set up socket event listeners
function setupSocketEvents() {
    // Listen for new students joining
    socket.on('user-joined', (data) => {
        if (data.userRole !== 'teacher') {
            handleStudentJoined({
                studentId: data.userId,
                studentName: data.userName
            });
            
            console.log(`Student ${data.userName} joined the meeting`);
        }
    });
    
    // Listen for students leaving
    socket.on('user-left', (data) => {
        handleStudentLeft({
            studentId: data.userId
        });
        
        console.log(`Student ${data.userId} left the meeting`);
    });
    
    // Listen for focus updates directly from WebSocket
    socket.on('focus-update', (data) => {
        console.log('Socket focus update received:', data);
        handleStudentFocusUpdate({
            studentId: data.studentId,
            focusScore: data.focusScore,
            timestamp: data.timestamp,
            userName: data.userName
        });
    });
    
    // Listen for focus data directly from the database
    socket.on('focus-data-from-db', (data) => {
        console.log('Database focus data received:', data);
        handleStudentFocusUpdate({
            studentId: data.studentId,
            focusScore: data.focusScore,
            timestamp: data.timestamp,
            userName: data.userName,
            source: 'database'
        });
    });
    
    // Also keep the channel listener for legacy compatibility
    if (channel && typeof channel.listen === 'function') {
        channel.listen('focus-update', (data) => {
            console.log('Channel focus update received:', data);
            handleStudentFocusUpdate({
                studentId: data.studentId,
                focusScore: data.focusScore,
                timestamp: new Date(data.timestamp),
                userName: data.userName
            });
        });
    }
}

// Handle student focus update
function handleStudentFocusUpdate(data) {
    const { studentId, focusScore, timestamp, userName, source } = data;
    
    // If student doesn't exist in our map yet, add them
    if (!students.has(studentId)) {
        if (userName) {
            console.log(`Adding new student from focus update: ${userName} (${studentId})`);
            handleStudentJoined({
                studentId,
                studentName: userName
            });
        } else {
            console.warn(`Received focus update for unknown student: ${studentId}`);
            return; // Can't process without student info
        }
    }

    const student = students.get(studentId);
    student.focusScore = focusScore;
    student.lastUpdate = timestamp || Date.now();

    // Update UI
    updateStudentUI(studentId);
    updateFocusMetrics();

    // Log focus update
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        console.error('CSRF token not found');
        return;
    }
    
    fetch('/api/focus-logs', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            meeting_id: window.meetingData.meetingId,
            student_id: studentId,
            focus_level: focusScore,
            timestamp: new Date().toISOString()
        })
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        
        if (!response.ok) {
            let errorMessage = `HTTP error! status: ${response.status}`;
            
            try {
                if (contentType && contentType.includes('application/json')) {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } else {
                    const textResponse = await response.text();
                    errorMessage = `${errorMessage}. Response: ${textResponse.substring(0, 100)}...`;
                }
            } catch (parseError) {
                console.error('Error parsing error response:', parseError);
            }
            
            throw new Error(errorMessage);
        }
        
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Invalid response type. Expected JSON.');
        }
        
        return response.json();
    })
    .then(result => {
        if (result.status === 'success') {
            console.log('Focus data stored successfully:', result.data);
        } else {
            console.error('Error storing focus data:', result.message);
        }
    })
    .catch(error => {
        console.error('Error storing focus data:', error.message);
        // Don't throw the error, just log it and continue
    });
}

// Handle student joined event
function handleStudentJoined(data) {
    console.log(`Student joined: ${data.studentName} (${data.studentId})`);
    
    // Check if student already exists
    if (!students.has(data.studentId)) {
        // Add student to the collection
        students.set(data.studentId, {
            id: data.studentId,
            name: data.studentName,
            focusScore: 0,
            lastUpdate: Date.now(),
            joinTime: Date.now()
        });
        
        // Update the UI with the new student
        createStudentElement(data.studentId, data.studentName);
        
        // Hide empty state if exists
        const emptyState = document.getElementById('empty-state');
        if (emptyState) {
            emptyState.classList.add('hidden');
        }
        
        // Update focus metrics
        updateFocusMetrics();
        
        // Show notification
        showNotification(`${data.studentName} has joined the meeting`);
    }
    
    // Log current students
    console.log(`Current student count: ${students.size}`);
    console.log('Current students:', Array.from(students.entries()));
    
    // Request focus data for this student from the database
    fetchStudentFocusData(data.studentId);
}

// Remove a student from the tracking system
function handleStudentLeft(data) {
    const { studentId } = data;
    
    if (!students.has(studentId)) {
        return;
    }
    
    const student = students.get(studentId);
    
    // Show notification
    showNotification(`${student.name} has left the meeting`);
    
    // Remove student
    students.delete(studentId);
    
    // Update UI
    updateFocusMetrics();
    
    // Remove student element
    const studentElement = document.getElementById(`student-${studentId}`);
    if (studentElement) {
        studentElement.remove();
    }
    
    // Show empty state if no students
    if (students.size === 0) {
        document.getElementById('empty-state').classList.remove('hidden');
    }
}

// Update the focus metrics (average focus and distribution)
function updateFocusMetrics() {
    // Get references to DOM elements first and check if they exist
    const averageFocusElement = document.getElementById('average-focus');
    const averageFocusBar = document.getElementById('average-focus-bar');
    const highFocusCount = document.getElementById('high-focus-count');
    const mediumFocusCount = document.getElementById('medium-focus-count');
    const lowFocusCount = document.getElementById('low-focus-count');
    const highFocusBar = document.getElementById('high-focus-bar');
    const mediumFocusBar = document.getElementById('medium-focus-bar');
    const lowFocusBar = document.getElementById('low-focus-bar');
    const activeStudents = document.getElementById('active-students');
    
    // If any required elements are missing, log an error and return
    if (!averageFocusElement || !averageFocusBar || !highFocusCount || 
        !mediumFocusCount || !lowFocusCount || !highFocusBar || 
        !mediumFocusBar || !lowFocusBar || !activeStudents) {
        console.error('Missing required DOM elements for focus metrics');
        return;
    }

    if (students.size === 0) {
        averageFocusElement.textContent = '0%';
        averageFocusBar.style.width = '0%';
        highFocusCount.textContent = '0';
        mediumFocusCount.textContent = '0';
        lowFocusCount.textContent = '0';
        highFocusBar.style.width = '0%';
        mediumFocusBar.style.width = '0%';
        lowFocusBar.style.width = '0%';
        activeStudents.textContent = '0';
        return;
    }

    let totalFocus = 0;
    let highFocus = 0;
    let mediumFocus = 0;
    let lowFocus = 0;

    students.forEach(student => {
        totalFocus += student.focusScore;
        
        if (student.focusScore >= 70) {
            highFocus++;
        } else if (student.focusScore >= 40) {
            mediumFocus++;
        } else {
            lowFocus++;
        }
    });

    const averageFocus = totalFocus / students.size;
    
    // Update average focus
    averageFocusElement.textContent = `${Math.round(averageFocus)}%`;
    averageFocusBar.style.width = `${averageFocus}%`;
    
    // Update focus trend
    const trendElement = document.getElementById('focus-trend');
    if (trendElement) {
        lastAverages.push(averageFocus);
        
        if (lastAverages.length > 5) {
            lastAverages.shift();
            
            const prevAvg = lastAverages[0];
            const currentAvg = lastAverages[lastAverages.length - 1];
            
            if (currentAvg > prevAvg + 5) {
                trendElement.innerHTML = '<span class="text-green-500">↑</span>';
                trendElement.title = 'Focus is improving';
            } else if (currentAvg < prevAvg - 5) {
                trendElement.innerHTML = '<span class="text-red-500">↓</span>';
                trendElement.title = 'Focus is declining';
            } else {
                trendElement.innerHTML = '<span class="text-gray-500">→</span>';
                trendElement.title = 'Focus is stable';
            }
        }
    }

    // Update focus distribution
    highFocusCount.textContent = highFocus;
    mediumFocusCount.textContent = mediumFocus;
    lowFocusCount.textContent = lowFocus;
    
    const total = students.size;
    highFocusBar.style.width = `${(highFocus / total) * 100}%`;
    mediumFocusBar.style.width = `${(mediumFocus / total) * 100}%`;
    lowFocusBar.style.width = `${(lowFocus / total) * 100}%`;
    
    // Update active students
    activeStudents.textContent = students.size;
}

// Update the UI for a specific student
function updateStudentUI(studentId) {
    if (!students.has(studentId)) {
        console.warn(`Attempted to update UI for non-existent student: ${studentId}`);
        return;
    }
    
    const student = students.get(studentId);
    
    // Get the student element
    const studentElement = document.getElementById(`student-${studentId}`);
    if (!studentElement) {
        console.warn(`Student element not found for student ${studentId}`);
        return;
    }
    
    // Find and update various elements
    try {
        // Update name if needed
        const nameElement = studentElement.querySelector('.student-name');
        if (nameElement && student.name) {
            nameElement.textContent = student.name;
        }
        
        // Update focus score
        const focusElement = studentElement.querySelector('.focus-score');
        if (focusElement) {
            // Store previous value to detect changes
            const prevFocus = focusElement.dataset.focusScore || 0;
            const newFocus = Math.round(student.focusScore);
            
            // Update the display
            focusElement.textContent = `${newFocus}%`;
            focusElement.dataset.focusScore = newFocus;
            
            // Highlight changes
            if (parseInt(prevFocus) !== newFocus) {
                highlightElement(focusElement);
                
                // Show trend indicator
                if (parseInt(prevFocus) < newFocus) {
                    focusElement.dataset.trend = 'up';
                } else if (parseInt(prevFocus) > newFocus) {
                    focusElement.dataset.trend = 'down';
                }
                
                // Clear trend after 3 seconds
                setTimeout(() => {
                    focusElement.dataset.trend = 'none';
                }, 3000);
            }
        }
        
        // Update focus bar
        const focusBar = studentElement.querySelector('.focus-bar');
        if (focusBar) {
            focusBar.style.width = `${student.focusScore}%`;
            
            // Update color based on focus level
            const colorClass = getFocusColorClass(student.focusScore);
            
            // Remove old color classes and add new one
            focusBar.className = focusBar.className
                .split(' ')
                .filter(c => !c.startsWith('bg-'))
                .join(' ');
                
            focusBar.classList.add(colorClass);
        }
        
        // Update timestamp
        const lastUpdateElement = studentElement.querySelector('.last-update');
        if (lastUpdateElement && student.lastUpdate) {
            // Format the timestamp
            const timestamp = new Date(student.lastUpdate);
            const now = new Date();
            let displayTime;
            
            // Show relative time for recent updates
            const secondsAgo = Math.floor((now - timestamp) / 1000);
            
            if (secondsAgo < 60) {
                displayTime = `${secondsAgo}s ago`;
            } else if (secondsAgo < 3600) {
                displayTime = `${Math.floor(secondsAgo / 60)}m ago`;
            } else {
                // For older timestamps, show actual time
                displayTime = timestamp.toLocaleTimeString();
            }
            
            lastUpdateElement.textContent = displayTime;
            
            // Mark as stale if data is old
            if (secondsAgo > 30) {
                lastUpdateElement.classList.add('text-red-500', 'dark:text-red-400');
                lastUpdateElement.classList.remove('text-gray-600', 'dark:text-gray-400');
            } else {
                lastUpdateElement.classList.remove('text-red-500', 'dark:text-red-400');
                lastUpdateElement.classList.add('text-gray-600', 'dark:text-gray-400');
            }
        }
        
        // Update status indicator
        const statusIndicator = studentElement.querySelector('.status-indicator');
        if (statusIndicator) {
            // Update status based on last update time
            const timeSinceUpdate = Date.now() - student.lastUpdate;
            
            if (timeSinceUpdate > 60000) { // More than 1 minute
                // Probably offline
                statusIndicator.className = 'status-indicator bg-gray-400 dark:bg-gray-600';
                statusIndicator.title = 'Inactive';
            } else if (student.focusScore < 30) {
                // Low focus
                statusIndicator.className = 'status-indicator bg-red-500 dark:bg-red-400';
                statusIndicator.title = 'Low Focus';
            } else if (student.focusScore < 60) {
                // Medium focus
                statusIndicator.className = 'status-indicator bg-yellow-500 dark:bg-yellow-400';
                statusIndicator.title = 'Medium Focus';
    } else {
                // Good focus
                statusIndicator.className = 'status-indicator bg-green-500 dark:bg-green-400';
                statusIndicator.title = 'Good Focus';
            }
        }
    } catch (error) {
        console.error(`Error updating UI for student ${studentId}:`, error);
    }
}

// Helper function to highlight changed elements
function highlightElement(element) {
    // Add highlight class
    element.classList.add('highlight-change');
    
    // Remove it after animation completes
    setTimeout(() => {
        element.classList.remove('highlight-change');
    }, 1000);
}

// Update the session duration
function updateSessionDuration() {
    const now = Date.now();
    const seconds = Math.floor((now - meetingStartTime) / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    
    const formattedMinutes = String(minutes % 60).padStart(2, '0');
    const formattedSeconds = String(seconds % 60).padStart(2, '0');
    
    let duration = '';
    
    if (hours > 0) {
        duration = `${hours}:${formattedMinutes}:${formattedSeconds}`;
    } else {
        duration = `${formattedMinutes}:${formattedSeconds}`;
    }
    
    document.getElementById('session-duration').textContent = duration;
}

// Show a notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 bg-${type}-600 text-white px-4 py-2 rounded shadow-lg`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('opacity-0', 'transition-opacity', 'duration-500');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 500);
    }, 3000);
}

// Initialize video controls
function initializeVideoControls() {
    const toggleVideoButton = document.getElementById('toggleVideo');
    const toggleAudioButton = document.getElementById('toggleAudio');
    
    let isVideoOn = true;
    let isAudioOn = true;
    
    toggleVideoButton.addEventListener('click', () => {
        isVideoOn = !isVideoOn;
        
        if (isVideoOn) {
            toggleVideoButton.classList.remove('bg-red-500', 'hover:bg-red-600');
            toggleVideoButton.classList.add('bg-blue-500', 'hover:bg-blue-600');
        } else {
            toggleVideoButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
            toggleVideoButton.classList.add('bg-red-500', 'hover:bg-red-600');
        }
        
        // Here you would toggle actual video
    });
    
    toggleAudioButton.addEventListener('click', () => {
        isAudioOn = !isAudioOn;
        
        if (isAudioOn) {
            toggleAudioButton.classList.remove('bg-red-500', 'hover:bg-red-600');
            toggleAudioButton.classList.add('bg-blue-500', 'hover:bg-blue-600');
        } else {
            toggleAudioButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
            toggleAudioButton.classList.add('bg-red-500', 'hover:bg-red-600');
        }
        
        // Here you would toggle actual audio
    });
}

// Clean up when leaving
function cleanup() {
    clearInterval(focusUpdateInterval);
    
    if (window.socket && socket.connected) {
        socket.emit('leave-meeting', {
            meetingId: window.meetingData.meetingId,
            userId: window.meetingData.userId,
            userName: window.meetingData.userName
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeTeacherMeeting);

// Clean up on page unload
window.addEventListener('beforeunload', cleanup);

// Function to fetch focus data directly from database with retry logic
async function fetchFocusDataFromDatabase() {
    try {
        console.log('Fetching focus data directly from database');
        
        // Check if socket is connected before making fetch
        if (!window.socket || !window.socket.connected) {
            console.warn('Socket disconnected, trying to reconnect before fetching data');
            if (window.socket) {
                window.socket.connect();
            }
        }
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000); // 8 second timeout
        
        // Include current timestamp to prevent caching
        const timestamp = new Date().getTime();
        const response = await fetch(`/api/focus-logs/meeting/${window.meetingData.meetingId}?time_range=last_5_minutes&_=${timestamp}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            signal: controller.signal
        }).catch(error => {
            console.warn('Fetch error:', error.message);
            return null;
        });
        
        clearTimeout(timeoutId);
        
        if (!response) {
            console.warn('Network error when fetching focus data');
            return;
        }
        
        if (!response.ok) {
            console.warn(`Failed to fetch focus data: HTTP ${response.status}`);
            
            // Try to get error details
            try {
                const errorText = await response.text();
                console.warn('Error response:', errorText.substring(0, 200));
            } catch (e) {
                console.warn('Could not read error response');
            }
            return;
        }
        
        // Get content type to determine how to parse
        const contentType = response.headers.get('Content-Type');
        
        if (contentType && contentType.includes('application/json')) {
            // JSON response
            const data = await response.json();
            console.log('Focus data from database (JSON):', data);
            
            if (data.status === 'success' && data.data && Array.isArray(data.data)) {
                processFocusData(data.data);
            } else {
                console.warn('Invalid focus data format:', data);
            }
        } else {
            // Try to parse as JSON anyway
            const text = await response.text();
            
            try {
                const data = JSON.parse(text);
                console.log('Focus data from database (parsed text):', data);
                
                if (data.status === 'success' && data.data && Array.isArray(data.data)) {
                    processFocusData(data.data);
                } else {
                    console.warn('Invalid focus data format (from text):', data);
                }
            } catch (jsonError) {
                console.error('Failed to parse focus data as JSON:', jsonError);
                console.error('Raw response:', text.substring(0, 200));
            }
        }
    } catch (error) {
        console.error('Error fetching focus data from database:', error);
    }
}

// Process focus data from the database
function processFocusData(focusData) {
    console.log(`Processing ${focusData.length} focus data records`);
    
    // Group focus data by student for easier processing
    const studentFocusMap = new Map();
    
    // Process each record
    focusData.forEach(record => {
        const studentId = record.student_id;
        const focusLevel = record.focus_level;
        const timestamp = new Date(record.timestamp);
        const studentName = record.student_name;
        
        // Keep track of the latest data for each student
        if (!studentFocusMap.has(studentId) || timestamp > studentFocusMap.get(studentId).timestamp) {
            studentFocusMap.set(studentId, {
                studentId,
                studentName,
                focusLevel,
                timestamp
            });
        }
    });
    
    console.log(`Found data for ${studentFocusMap.size} students`);
    
    // Update our student tracking with the latest data
    studentFocusMap.forEach((data, studentId) => {
        if (students.has(studentId)) {
            // Update existing student
            const student = students.get(studentId);
            student.focusScore = data.focusLevel;
            student.lastUpdate = data.timestamp;
            students.set(studentId, student);
            
            console.log(`Updated student ${student.name}: Focus ${data.focusLevel}%`);
        } else if (data.studentName) {
            // Add new student from database
            console.log(`Adding new student from database: ${data.studentName} (${studentId})`);
            
            students.set(studentId, {
                id: studentId,
                name: data.studentName,
                focusScore: data.focusLevel,
                lastUpdate: data.timestamp,
                joinTime: Date.now()
            });
            
            // Create UI element for this student
            createStudentElement(studentId, data.studentName);
            
            // Hide empty state
            const emptyState = document.getElementById('empty-state');
            if (emptyState) {
                emptyState.classList.add('hidden');
            }
        }
    });
    
    // Update UI for all students
    students.forEach((student, studentId) => {
        updateStudentUI(studentId);
    });
    
    // Update overall metrics
    updateFocusMetrics();
}

// Start fetch polling (every 5 seconds)
setInterval(fetchFocusDataFromDatabase, 5000);

// Update the focus stats with better error handling
async function updateFocusStats() {
    try {
        if (!window.meetingData || !window.meetingData.meetingId) {
            console.warn('Missing meeting data, cannot update focus stats');
            return;
        }
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
        
        const response = await fetch(`/api/meetings/${window.meetingData.meetingId}/analytics`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            },
            signal: controller.signal
        }).catch(error => {
            console.warn('Analytics fetch error:', error.message);
            return null;
        });
        
        clearTimeout(timeoutId);
        
        if (!response || !response.ok) {
            console.warn(`Failed to fetch focus stats: ${response ? response.status : 'Network error'}`);
            
            // Use local calculations as fallback
            if (students.size > 0) {
                updateFocusMetrics();
            }
            return;
        }
        
        const data = await response.json();
        
        // Update UI with analytics data
        if (data.average_focus) {
            document.getElementById('average-focus').textContent = `${Math.round(data.average_focus)}%`;
            document.getElementById('average-focus-bar').style.width = `${data.average_focus}%`;
        }
        
        // Update other stats as needed
    } catch (error) {
        console.error('Error updating focus stats:', error);
        
        // Use local calculations as fallback
        updateFocusMetrics();
    }
}

// Fetch focus data for a specific student from the database
async function fetchStudentFocusData(studentId) {
    try {
        const response = await fetch(`/api/focus-logs/student/${studentId}?time_range=last_5_minutes`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            console.warn(`Failed to fetch focus data for student ${studentId}: ${response.status}`);
            return;
        }
        
        const data = await response.json();
        console.log(`Focus data for student ${studentId}:`, data);
        
        if (data.data && Array.isArray(data.data) && data.data.length > 0) {
            const latestData = data.data[0]; // Most recent entry
            
            if (students.has(studentId)) {
                const student = students.get(studentId);
                student.focusScore = latestData.focus_level;
                student.lastUpdate = new Date(latestData.created_at);
                students.set(studentId, student);
                
                // Update UI
                updateStudentUI(studentId);
            }
        }
    } catch (error) {
        console.error(`Error fetching focus data for student ${studentId}:`, error);
    }
}

// Create a visual element for a student
function createStudentElement(studentId, studentName) {
    // Get the container for students
    const studentsContainer = document.getElementById('students-container');
    
    if (!studentsContainer) {
        console.error('Students container not found!');
        return;
    }
    
    // Check if element already exists
    if (document.getElementById(`student-${studentId}`)) {
        console.log(`Student element for ${studentId} already exists`);
        return;
    }
    
    console.log(`Creating UI element for student: ${studentName} (${studentId})`);
    
    // Create student card
    const studentCard = document.createElement('div');
    studentCard.id = `student-${studentId}`;
    studentCard.className = 'bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-3 border border-gray-200 dark:border-gray-700 student-card';
    
    // Get student data
    const student = students.get(studentId) || { 
        name: studentName, 
        id: studentId,
        focusScore: 0,
        lastUpdate: new Date()
    };
    
    // Create card content
    studentCard.innerHTML = `
        <div class="flex items-center space-x-2 mb-3">
            <div class="status-indicator bg-gray-300 dark:bg-gray-600" title="Status"></div>
            <h3 class="student-name text-lg font-semibold text-gray-900 dark:text-white">${studentName}</h3>
        </div>
        
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Focus level:</span>
            <span class="focus-score font-semibold text-md" data-focus-score="0">${Math.round(student.focusScore || 0)}%</span>
        </div>
        
        <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full mb-3">
            <div class="focus-bar h-full rounded-full transition-all duration-500 ${getFocusColorClass(student.focusScore || 0)}" style="width: ${student.focusScore || 0}%"></div>
        </div>
        
        <div class="flex justify-between text-xs">
            <span class="last-update text-gray-600 dark:text-gray-400">Just now</span>
            <button class="focus-history-btn text-blue-600 dark:text-blue-400 hover:underline" 
                data-student-id="${studentId}" 
                data-student-name="${studentName}"
                onclick="showStudentFocusHistory('${studentId}', '${studentName}')"
            >
                View history
            </button>
        </div>
    `;
    
    // Add to container
    studentsContainer.appendChild(studentCard);
    
    // Add any click handlers or other interactions
    const focusHistoryBtn = studentCard.querySelector('.focus-history-btn');
    if (focusHistoryBtn) {
        focusHistoryBtn.addEventListener('click', (e) => {
            e.preventDefault();
            showStudentFocusHistory(studentId, studentName);
        });
    }
    
    // Update UI to reflect current state
    updateStudentUI(studentId);
}

// Show student focus history in a modal or panel
function showStudentFocusHistory(studentId, studentName) {
    console.log(`Showing focus history for ${studentName} (${studentId})`);
    
    // Fetch detailed student focus history from server
    fetch(`/api/focus-logs/student/${studentId}?time_range=last_30_minutes`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.data) {
            // Show history in a modal/dialog
            const historyModal = document.getElementById('student-history-modal');
            if (historyModal) {
                // Fill in modal content
                const titleElement = historyModal.querySelector('.modal-title');
                const bodyElement = historyModal.querySelector('.modal-body');
                
                if (titleElement) {
                    titleElement.textContent = `Focus History: ${studentName}`;
                }
                
                if (bodyElement) {
                    // Create a simple timeline/chart of focus data
                    if (data.data.length > 0) {
                        // Create chart or table
                        let html = '<div class="overflow-x-auto"><table class="w-full text-sm">';
                        html += '<thead><tr><th class="px-2 py-1 text-left">Time</th><th class="px-2 py-1 text-right">Focus</th></tr></thead><tbody>';
                        
                        data.data.forEach(entry => {
                            const focusClass = getFocusTextColorClass(entry.focus_level);
                            const time = new Date(entry.timestamp).toLocaleTimeString();
                            html += `<tr class="border-t border-gray-200 dark:border-gray-700">
                                <td class="px-2 py-1">${time}</td>
                                <td class="px-2 py-1 text-right ${focusClass}">${Math.round(entry.focus_level)}%</td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table></div>';
                        
                        if (data.statistics) {
                            html += `<div class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded">
                                <h4 class="font-semibold mb-2">Session Statistics</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>Average Focus:</div>
                                    <div class="text-right font-medium">${Math.round(data.statistics.average_focus)}%</div>
                                    <div>Time with High Focus:</div>
                                    <div class="text-right font-medium">${data.statistics.focus_distribution.high} readings</div>
                                    <div>Time with Low Focus:</div>
                                    <div class="text-right font-medium">${data.statistics.focus_distribution.very_low + data.statistics.focus_distribution.low} readings</div>
                                </div>
                            </div>`;
                        }
                        
                        bodyElement.innerHTML = html;
                    } else {
                        bodyElement.innerHTML = '<p class="text-gray-600 dark:text-gray-400">No focus data available for this student.</p>';
                    }
                }
                
                // Show the modal
                historyModal.classList.remove('hidden');
            }
        } else {
            console.error('Failed to load student history:', data.message || 'Unknown error');
        }
    })
    .catch(error => {
        console.error('Error fetching student history:', error);
    });
}

// Helper function to get text color class based on focus level
function getFocusTextColorClass(level) {
    if (level >= 80) return 'text-green-600 dark:text-green-400';
    if (level >= 60) return 'text-teal-600 dark:text-teal-400';
    if (level >= 40) return 'text-yellow-600 dark:text-yellow-400';
    if (level >= 20) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400';
}

// Get the appropriate color class based on focus level
function getFocusColorClass(focusScore) {
    if (focusScore >= 70) {
        return 'bg-green-500';
    } else if (focusScore >= 40) {
        return 'bg-yellow-500';
    } else {
        return 'bg-red-500';
    }
}
