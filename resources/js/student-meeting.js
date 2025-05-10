// Meeting room functionality for students
class StudentMeeting {
    constructor(meetingId, studentId, studentName) {
        this.meetingId = meetingId;
        this.studentId = studentId;
        this.studentName = studentName;
        this.sessionStartTime = new Date();
        this.focusUpdateInterval = null;
        this.connectionStatus = 'connecting';
        this.focusStatus = 'waiting';
        this.currentFocus = 0;
        this.lastFocusUpdate = null;
        
        this.initialize();
    }

    async initialize() {
        try {
            this.debug('Initializing student meeting');
            await this.setupEventListeners();
            await this.connectToMeeting();
            this.startFocusTracking();
            this.updateSessionDuration();
        } catch (error) {
            this.showError('Failed to initialize meeting: ' + error.message);
        }
    }

    async setupEventListeners() {
        // Leave meeting button
        const leaveButton = document.getElementById('leave-meeting');
        if (leaveButton) {
            leaveButton.addEventListener('click', () => this.leaveMeeting());
        }

        // Window beforeunload event
        window.addEventListener('beforeunload', (e) => {
            this.leaveMeeting();
        });
    }

    async connectToMeeting() {
        try {
            this.debug('Connecting to meeting');
            this.updateConnectionStatus('connecting');

            // Save current student info for Python tracker
            await fetch('/student/save-current', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    meeting_id: this.meetingId,
                    user_id: this.studentId,
                    user_name: this.studentName
                })
            });

            this.updateConnectionStatus('connected');
            this.debug('Successfully connected to meeting');
        } catch (error) {
            this.updateConnectionStatus('error');
            throw new Error('Failed to connect to meeting: ' + error.message);
        }
    }

    startFocusTracking() {
        this.debug('Starting focus tracking');
        this.focusUpdateInterval = setInterval(() => {
            this.updateFocusLevel();
        }, 5000); // Update every 5 seconds
    }

    async updateFocusLevel() {
        try {
            const response = await fetch(`/api/meetings/${this.meetingId}/focus`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch focus level');
            }

            const data = await response.json();
            this.currentFocus = data.focus_level;
            this.lastFocusUpdate = new Date();
            
            this.updateFocusDisplay();
            this.updateFocusStatus('active');
        } catch (error) {
            this.debug('Error updating focus level', error);
            this.updateFocusStatus('error');
        }
    }

    updateFocusDisplay() {
        const focusElement = document.getElementById('current-focus');
        const focusBar = document.getElementById('focus-bar');
        
        if (focusElement && focusBar) {
            focusElement.textContent = `${Math.round(this.currentFocus)}%`;
            focusBar.style.width = `${this.currentFocus}%`;
            
            // Update focus bar color based on level
            if (this.currentFocus >= 80) {
                focusBar.className = 'shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500 transition-all duration-500';
            } else if (this.currentFocus >= 50) {
                focusBar.className = 'shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-yellow-500 transition-all duration-500';
            } else {
                focusBar.className = 'shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-red-500 transition-all duration-500';
            }
        }
    }

    updateSessionDuration() {
        setInterval(() => {
            const duration = new Date() - this.sessionStartTime;
            const hours = Math.floor(duration / 3600000);
            const minutes = Math.floor((duration % 3600000) / 60000);
            const seconds = Math.floor((duration % 60000) / 1000);
            
            const durationElement = document.getElementById('session-duration');
            if (durationElement) {
                durationElement.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
        }, 1000);
    }

    updateConnectionStatus(status) {
        this.connectionStatus = status;
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            switch (status) {
                case 'connecting':
                    statusElement.className = 'px-3 py-1 rounded-full text-sm font-medium bg-yellow-600 text-white';
                    statusElement.textContent = 'Connecting...';
                    break;
                case 'connected':
                    statusElement.className = 'px-3 py-1 rounded-full text-sm font-medium bg-green-600 text-white';
                    statusElement.textContent = 'Connected';
                    break;
                case 'error':
                    statusElement.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-600 text-white';
                    statusElement.textContent = 'Connection Error';
                    break;
            }
        }
    }

    updateFocusStatus(status) {
        this.focusStatus = status;
        const statusElement = document.getElementById('focus-status');
        if (statusElement) {
            switch (status) {
                case 'waiting':
                    statusElement.className = 'px-3 py-1 rounded-full text-sm font-medium bg-gray-600 text-white';
                    statusElement.textContent = 'Focus: Waiting for Python app...';
                    break;
                case 'active':
                    statusElement.className = 'px-3 py-1 rounded-full text-sm font-medium bg-green-600 text-white';
                    statusElement.textContent = 'Focus: Active';
                    break;
                case 'error':
                    statusElement.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-600 text-white';
                    statusElement.textContent = 'Focus: Error';
                    break;
            }
        }
    }

    async leaveMeeting() {
        try {
            this.debug('Leaving meeting');
            if (this.focusUpdateInterval) {
                clearInterval(this.focusUpdateInterval);
            }

            await fetch(`/meetings/${this.meetingId}/leave`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            window.location.href = '/student/dashboard';
        } catch (error) {
            this.showError('Failed to leave meeting: ' + error.message);
        }
    }

    showError(message) {
        this.debug('Showing error message', { message });
        const errorElement = document.createElement('div');
        errorElement.className = 'fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg';
        errorElement.textContent = message;
        document.body.appendChild(errorElement);
        
        setTimeout(() => {
            errorElement.remove();
        }, 5000);
    }

    debug(message, data = null) {
        if (process.env.NODE_ENV === 'development') {
            console.debug(`[Student Meeting] ${message}`, data || '');
        }
    }
}

// Export for module usage
export default StudentMeeting;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', async () => {
    console.debug('[Student Meeting] Document ready event received');
    try {
        const meetingId = document.getElementById('meeting-id')?.value;
        const studentId = document.getElementById('user-id')?.value;
        const studentName = document.getElementById('user-name')?.value;

        console.debug('[Student Meeting] Retrieved form values', { meetingId, studentId, studentName });

        if (!meetingId || !studentId || !studentName) {
            throw new Error('Required meeting information is missing');
        }
        
        console.log('[Student Meeting] Starting initialization...');
        window.studentMeeting = new StudentMeeting(meetingId, studentId, studentName);
    } catch (error) {
        console.error('[Student Meeting] Initialization failed:', error.message);
        console.debug('[Student Meeting] Initialization error details:', error);
    }
}); 