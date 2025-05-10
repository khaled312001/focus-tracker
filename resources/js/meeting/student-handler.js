// Handles student-specific meeting functionality
export class StudentHandler {
    constructor(meetingId, userId, userName) {
        this.meetingId = meetingId;
        this.userId = userId;
        this.userName = userName;
        this.wsManager = window.wsManager;
        this.focusScore = 0;
        this.isActive = true;
        this.lastUpdate = Date.now();
        this.initialized = false;
    }

    // Initialize student connection
    async initialize() {
        if (this.initialized) {
            console.log('[StudentHandler] Already initialized');
            return;
        }

        try {
            console.log('[StudentHandler] Initializing...');
            
            // Initialize video stream first
            await this.initializeVideo();
            
            // Send join message
            const joinData = {
                type: 'join',
                meetingId: parseInt(this.meetingId, 10),
                userId: parseInt(this.userId, 10),
                userName: this.userName,
                role: 'student'
            };
            
            console.log('[StudentHandler] Sending join message:', joinData);
            await this.wsManager.send(joinData);

            // Start focus tracking immediately after joining
            this.initializeFocusTracking();

            // Request initial meeting state
            const stateRequest = {
                type: 'request_meeting_state',
                meetingId: parseInt(this.meetingId, 10)
            };
            await this.wsManager.send(stateRequest);

            // Send initial status with real focus data
            await this.sendStatus();

            // Setup more frequent status updates (every 2 seconds)
            this.statusInterval = setInterval(() => {
                this.sendStatus();
            }, 2000);

            this.initialized = true;
            console.log('[StudentHandler] Initialization complete');
            return true;
        } catch (error) {
            console.error('[StudentHandler] Failed to initialize:', error);
            throw error;
        }
    }

    // Initialize video stream
    async initializeVideo() {
        try {
            console.log('[StudentHandler] Initializing video stream...');
            
            // Request camera access
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });

            // Set up video element
            const videoElement = document.getElementById('localVideo');
            if (videoElement) {
                videoElement.srcObject = this.stream;
                await videoElement.play();
            }

            console.log('[StudentHandler] Video stream initialized');
        } catch (error) {
            console.error('[StudentHandler] Video initialization error:', error);
            // Continue even if video fails - we'll just reduce focus score
        }
    }

    // Update and send focus score
    async updateFocusScore(score) {
        console.log('[StudentHandler] Updating focus score:', score);
        this.focusScore = score;
        this.lastUpdate = new Date();
        
        // Get additional state information
        const isTabVisible = document.visibilityState === 'visible';
        const isWindowFocused = document.hasFocus();
        const hasCamera = !!(this.stream && this.stream.getVideoTracks().length > 0);
        const isCameraEnabled = hasCamera && this.stream.getVideoTracks()[0].enabled;
        
        // Send comprehensive status update
        const statusData = {
            type: 'student_state',
            meetingId: parseInt(this.meetingId, 10),
            studentId: parseInt(this.userId, 10),
            userName: this.userName,
            focusScore: score,
            isActive: isTabVisible && isWindowFocused,
            deviceStatus: {
                hasCamera: hasCamera,
                cameraEnabled: isCameraEnabled,
                tabVisible: isTabVisible,
                windowFocused: isWindowFocused
            },
            lastUpdate: new Date().toISOString()
        };

        console.log('[StudentHandler] Sending status update:', statusData);
        
        if (this.wsManager && this.wsManager.send) {
            await this.wsManager.send(statusData);
        } else {
            console.error('[StudentHandler] WebSocket manager not available');
        }
    }

    // Initialize focus tracking
    initializeFocusTracking() {
        if (this.focusTrackingInterval) {
            clearInterval(this.focusTrackingInterval);
        }

        // Track user activity
        this.lastActivityTime = Date.now();
        const activityEvents = ['mousemove', 'keydown', 'click', 'scroll'];
        activityEvents.forEach(event => {
            document.addEventListener(event, () => {
                this.lastActivityTime = Date.now();
            });
        });

        // Handle visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                console.log('[StudentHandler] Tab became visible');
                this.updateFocusScore(this.calculateFocusScore());
            } else {
                console.log('[StudentHandler] Tab hidden');
                this.updateFocusScore(0);
            }
        });

        // Start regular focus updates
        this.focusTrackingInterval = setInterval(() => {
            this.updateFocusScore(this.calculateFocusScore());
        }, 2000);
    }

    // Calculate focus score based on various factors
    calculateFocusScore() {
        let score = 100;

        // Check tab visibility
        if (document.visibilityState !== 'visible') {
            score -= 50;
        }

        // Check window focus
        if (!document.hasFocus()) {
            score -= 25;
        }

        // Check camera status
        if (this.stream && this.stream.getVideoTracks().length > 0) {
            if (!this.stream.getVideoTracks()[0].enabled) {
                score -= 15;
            }
        }

        // Check user activity
        const timeSinceLastActivity = Date.now() - (this.lastActivityTime || Date.now());
        if (timeSinceLastActivity > 30000) { // 30 seconds
            score -= Math.min(25, Math.floor(timeSinceLastActivity / 30000) * 5);
        }

        return Math.max(0, Math.min(100, score));
    }

    // Send current status to server
    async sendStatus() {
        try {
            // Calculate current focus score
            const currentFocusScore = this.calculateFocusScore();
            
            // Get device and activity status
            const isTabVisible = document.visibilityState === 'visible';
            const isWindowFocused = document.hasFocus();
            const hasCamera = !!(this.stream && this.stream.getVideoTracks().length > 0);
            const isCameraEnabled = hasCamera && this.stream.getVideoTracks()[0].enabled;

            const statusData = {
                type: 'student_state',
                meetingId: parseInt(this.meetingId, 10),
                userId: parseInt(this.userId, 10),
                userName: this.userName,
                focusScore: currentFocusScore,
                isActive: isTabVisible && isWindowFocused,
                deviceStatus: {
                    hasCamera,
                    cameraEnabled: isCameraEnabled,
                    tabVisible: isTabVisible,
                    windowFocused: isWindowFocused
                },
                lastUpdate: new Date().toISOString()
            };
            
            console.log('[StudentHandler] Sending status:', statusData);
            await this.wsManager.send(statusData);
            
            // Update local UI
            this.updateLocalUI(currentFocusScore);
            
            this.lastUpdate = Date.now();
        } catch (error) {
            console.error('[StudentHandler] Failed to send status:', error);
        }
    }

    // Update local UI with focus score
    updateLocalUI(focusScore) {
        // Update focus score display
        const focusScoreElement = document.getElementById('focus-score');
        if (focusScoreElement) {
            focusScoreElement.textContent = `${Math.round(focusScore)}`;
        }

        // Update focus status message
        const focusStatusElement = document.getElementById('focus-status');
        if (focusStatusElement) {
            let statusText = 'Excellent focus!';
            let statusClass = 'text-green-500';
            
            if (focusScore < 40) {
                statusText = 'Low focus';
                statusClass = 'text-red-500';
            } else if (focusScore < 70) {
                statusText = 'Moderate focus';
                statusClass = 'text-yellow-500';
            }
            
            focusStatusElement.textContent = statusText;
            focusStatusElement.className = `text-sm font-medium ${statusClass}`;
        }
    }

    // Clean up resources
    cleanup() {
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
            this.statusInterval = null;
        }
        
        if (this.focusTrackingInterval) {
            clearInterval(this.focusTrackingInterval);
            this.focusTrackingInterval = null;
        }

        // Stop video stream
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        // Remove event listeners
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        
        this.initialized = false;
        console.log('[StudentHandler] Cleanup complete');
    }
} 