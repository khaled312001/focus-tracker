// Import WebSocketManager
import { WebSocketManager } from './websocket';

// Get required elements synchronously
function getRequiredElements() {
    // Wait for a short moment to ensure elements are available
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            try {
                // Required elements that must exist
                const requiredElements = {
                    video: document.getElementById('video'),
                    canvas: document.getElementById('canvas'),
                    statusMessage: document.getElementById('status-message')
                };

                // Optional focus tracking elements
                const focusElements = {
                    focusScore: document.getElementById('focus-score'),
                    focusBar: document.getElementById('focus-bar')
                };

                // Get meeting data from window object
                if (!window.meetingData) {
                    throw new Error('Meeting data not available');
                }

                const elements = {
                    ...requiredElements,
                    ...focusElements,
                    meetingId: window.meetingData.meetingId,
                    userId: window.meetingData.userId,
                    userName: window.meetingData.userName
                };

                // Check only required elements
                const missingRequired = Object.entries(requiredElements)
                    .filter(([key, value]) => !value)
                    .map(([key]) => key);

                if (missingRequired.length > 0) {
                    throw new Error(`Missing required elements: ${missingRequired.join(', ')}`);
                }

                // Add flag for focus tracking availability
                elements.hasFocusTracking = Boolean(focusElements.focusScore && focusElements.focusBar);

                resolve(elements);
            } catch (error) {
                reject(error);
            }
        }, 100);
    });
}

export class StudentMeeting {
    constructor() {
        // Check if we're in student view
        if (!document.getElementById('meeting-data') || !window.meetingData) {
            console.log('[Student] Not in student view, skipping initialization');
            return;
        }

        this.focusUpdateInterval = 5000; // 5 seconds
        this.focusScores = [];
        this.isTracking = false;
        this.stream = null;
        this.focusTrackingEnabled = false;
        this.meeting = null;
        this.lastFocusUpdate = 0;
        this.connectionRetries = 0;
        this.maxConnectionRetries = 5;
        this.initialized = false;
        this.reconnectTimeout = null;
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        document.addEventListener('visibilitychange', this.handleVisibilityChange);

        // Start initialization
        this.initialize().catch(error => {
            console.error('[Student] Failed to initialize meeting:', error);
        });
    }

    setStatus(message, type = 'info') {
        console.log(`[Student] Status: ${message} (${type})`);
        const statusElement = document.getElementById('status-message');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `text-sm font-medium ${
                type === 'error' ? 'text-red-500' :
                type === 'success' ? 'text-green-500' :
                'text-blue-500'
            }`;
        }
    }

    async initializeUIElements() {
        try {
            this.setStatus('Initializing UI elements...');
            
            // Get all required elements asynchronously
            const elements = await getRequiredElements();
            
            // Assign elements to instance
            Object.assign(this, elements);

            if (!this.meetingId || !this.userId || !this.userName) {
                throw new Error('Missing required meeting data');
            }

            // Initialize focus UI only if available
            if (this.hasFocusTracking && this.focusScore && this.focusBar) {
                this.focusScore.textContent = '0%';
                this.focusBar.style.width = '0%';
                this.focusBar.className = 'h-full transition-all duration-300 ease-out bg-red-500 rounded-full';
            }
            
            // Make sure video is visible and properly styled
            this.video.className = 'w-full h-full object-cover';
            
            this.setStatus('UI elements initialized', 'success');
            return true;
        } catch (error) {
            this.setStatus(`Failed to initialize UI: ${error.message}`, 'error');
            throw error;
        }
    }

    async initialize() {
        try {
            // Initialize UI elements first
            await this.initializeUIElements();
            
            if (!this.meetingId || !this.userId || !this.userName) {
                console.error('[Student] Missing meeting data:', { meetingId: this.meetingId, userId: this.userId, userName: this.userName });
                this.setStatus('Missing meeting data', 'error');
                return;
            }
            
            console.log('[Student] Initializing with data:', {
                meetingId: this.meetingId,
                userId: this.userId,
                userName: this.userName
            });

            // Initialize WebSocket first
            await this.initializeWebSocket();
            
            // Then initialize video
            await this.initializeVideo();
            
            this.initialized = true;
            this.setStatus('Connected and tracking focus', 'success');
        } catch (error) {
            console.error('[Student] Initialization error:', error);
            this.setStatus('Failed to initialize: ' + error.message, 'error');
            throw error;
        }
    }

    async initializeWebSocket() {
        if (!window.wsManager) {
            console.error('[Student] WebSocket manager not found in window object');
            throw new Error('WebSocket manager not initialized');
        }

        try {
            console.log('[Student] WebSocket manager found, connecting to meeting...');
            this.setStatus('Connecting to meeting...', 'info');

            // Add connection status listener
            window.addEventListener('websocket-status', (event) => {
                const { status, message } = event.detail;
                console.log('[Student] WebSocket status:', status, message);
                switch (status) {
                    case 'connected':
                        this.setStatus('Connected to server', 'success');
                        break;
                    case 'disconnected':
                        this.setStatus('Disconnected from server', 'error');
                        break;
                    case 'error':
                        this.setStatus(`Connection error: ${message}`, 'error');
                        break;
                }
            });
            
            // Join the meeting
            console.log('[Student] Joining meeting with data:', {
                meetingId: this.meetingId,
                userId: this.userId,
                userName: this.userName,
                userRole: 'student'
            });
            
            // Wait for connection before joining
            await window.wsManager.connect();
            
            this.meeting = await window.wsManager.joinMeeting(this.meetingId, {
                userId: this.userId,
                userName: this.userName,
                userRole: 'student',
                meetingId: this.meetingId
            });
            
            // Listen for join confirmation
            window.wsManager.on('join_confirmed', (data) => {
                if (data.meetingId === this.meetingId && data.userId === this.userId) {
                    console.log('[Student] Join confirmed:', data);
                    this.setStatus('Successfully joined meeting', 'success');
                }
            });
            
            console.log('[Student] Successfully joined meeting, meeting object:', this.meeting);
            
            // Send initial student state update
            if (this.meeting && this.meeting.send) {
                console.log('[Student] Sending initial state update');
                await this.meeting.send({
                    type: 'student_state_update',
                    meetingId: this.meetingId,
                    userId: this.userId,
                    userName: this.userName,
                    userRole: 'student',
                    focusScore: 0,
                    focusTime: 0,
                    isActive: true,
                    joinTime: new Date().toISOString()
                });
            }
            
        } catch (error) {
            console.error('[Student] WebSocket initialization error:', error);
            this.setStatus('Failed to connect: ' + error.message, 'error');
            throw new Error('Failed to connect to meeting: ' + error.message);
        }
    }

    async initializeVideo() {
        try {
            this.setStatus('Initializing camera...', 'info');
            
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: {
                    width: { ideal: 1280, min: 640 },
                    height: { ideal: 720, min: 480 },
                    facingMode: "user"
                }
            });
            
            this.video.srcObject = stream;
            this.stream = stream;

            // Wait for video to be ready
            await new Promise((resolve) => {
                this.video.onloadedmetadata = () => {
                    this.video.play().then(resolve);
                };
            });
            
            this.setStatus('Camera initialized', 'success');
            this.startFocusTracking();
            
        } catch (error) {
            console.error('[Student] Camera initialization error:', error);
            throw new Error('Could not access camera: ' + error.message);
        }
    }

    startFocusTracking() {
        this.focusTrackingEnabled = true;
        this.trackFocus();
    }

    stopFocusTracking() {
        this.focusTrackingEnabled = false;
    }

    async trackFocus() {
        if (!this.focusTrackingEnabled) return;

        try {
            const focusScore = await this.analyzeFocus();
            this.updateFocusUI(focusScore);
            
            // Send focus update if connected and enough time has passed
            if (this.meeting && Date.now() - this.lastFocusUpdate >= this.focusUpdateInterval) {
                const updateData = {
                    type: 'focus_update',
                    meetingId: this.meetingId,
                    studentId: this.userId,
                    userName: this.userName,
                    focusScore: focusScore,
                    timestamp: new Date().toISOString(),
                    faceDetected: true
                };
                
                console.log('[Student] Sending focus update:', updateData);
                
                // Send the update to the WebSocket server
                if (this.meeting.send) {
                    await this.meeting.send(updateData);
                } else if (this.meeting.socket && this.meeting.socket.readyState === WebSocket.OPEN) {
                    this.meeting.socket.send(JSON.stringify(updateData));
                } else {
                    console.error('[Student] Cannot send focus update: WebSocket not available');
                }
                
                this.lastFocusUpdate = Date.now();
            }
        } catch (error) {
            console.error('[Student] Focus tracking error:', error);
            this.setStatus('Focus tracking error: ' + error.message, 'error');
        }

        // Schedule next update
        setTimeout(() => this.trackFocus(), this.focusUpdateInterval);
    }

    async analyzeFocus() {
        if (!this.video || !this.video.videoWidth || !this.video.videoHeight) {
            console.log('[Student] Video not ready yet, skipping focus analysis');
            return 0;
        }

        const canvas = document.createElement('canvas');
        canvas.width = this.video.videoWidth;
        canvas.height = this.video.videoHeight;
        const ctx = canvas.getContext('2d');
        
        try {
            ctx.drawImage(this.video, 0, 0);
            
            // Convert canvas to base64
            const base64Image = canvas.toDataURL('image/jpeg', 0.8);
            
            const response = await fetch('http://127.0.0.1:5000/analyze-focus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    image: base64Image,
                    userId: this.userId,
                    frameWidth: this.video.videoWidth,
                    frameHeight: this.video.videoHeight
                })
            });
            
            if (!response.ok) {
                throw new Error(`Analysis server error: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                console.error('[Student] Analysis error:', data.error);
                return 0;
            }

            // Get focus score from response
            const focusScore = data.focus_score || 0;
            console.log('[Student] Focus score:', focusScore);
            return focusScore;
        } catch (error) {
            console.error('[Student] Error analyzing focus:', error);
            return 0;
        }
    }

    updateFocusUI(score) {
        if (!this.hasFocusTracking) return;
        
        if (this.focusScore) {
            this.focusScore.textContent = `${Math.round(score)}%`;
        }
        
        if (this.focusBar) {
            this.focusBar.style.width = `${score}%`;
            
            // Update color based on score
            const colorClass = score >= 80 ? 'bg-green-500' : 
                             score >= 60 ? 'bg-yellow-500' : 
                             'bg-red-500';
                             
            this.focusBar.className = `h-full transition-all duration-300 ease-out ${colorClass} rounded-full`;
        }
        
        // Update status message with detailed feedback
        const message = score >= 80 ? 'Excellent focus! Your eyes are well centered on the content.' :
                       score >= 60 ? 'Good focus. Keep your eyes steady on the screen.' :
                       score >= 40 ? 'Your eyes are wandering. Try to maintain focus on the content.' :
                       score >= 20 ? 'Limited attention detected. Please try to focus on the screen.' :
                       'Cannot track your eyes. Are you looking at the screen?';
                       
        this.setStatus(message, score >= 80 ? 'success' : 
                               score >= 60 ? 'info' :
                               score >= 40 ? 'warning' : 'error');
    }

    handleVisibilityChange() {
        if (document.hidden) {
            this.stopFocusTracking();
            console.log('[Student] Focus tracking paused - tab not visible');
        } else {
            this.startFocusTracking();
            console.log('[Student] Focus tracking resumed - tab visible');
        }
    }

    async reconnect() {
        if (this.connectionRetries >= this.maxConnectionRetries) {
            this.setStatus('Maximum reconnection attempts reached. Please refresh the page.', 'error');
            return;
        }

        this.connectionRetries++;
        this.setStatus(`Attempting to reconnect (${this.connectionRetries}/${this.maxConnectionRetries})...`, 'warning');

        try {
            await this.initializeWebSocket();
            this.connectionRetries = 0;
            this.setStatus('Reconnected successfully', 'success');
        } catch (error) {
            console.error('[Student] Reconnection failed:', error);
            this.reconnectTimeout = setTimeout(() => this.reconnect(), 5000);
        }
    }

    cleanup() {
        // Stop tracking
        this.stopFocusTracking();

        // Stop media stream
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }

        // Clear reconnect timeout
        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout);
        }

        // Remove event listeners
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);

        // Close WebSocket connection
        if (this.meeting) {
            this.meeting.close();
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Check if we're in the student view by looking for the meeting-data element
        const meetingData = document.getElementById('meeting-data');
        if (!meetingData || !window.meetingData) {
            console.log('[Student] Not in student view, skipping initialization');
            return;
        }

        // Initialize WebSocket manager first
        if (!window.wsManager) {
            console.log('[Student] Creating new WebSocket manager instance');
            window.wsManager = new WebSocketManager();
        } else {
            console.log('[Student] Using existing WebSocket manager instance');
        }
        
        // Wait for the DOM to be fully rendered
        await new Promise(resolve => {
            if (document.readyState === 'complete') {
                resolve();
            } else {
                window.addEventListener('load', resolve);
            }
        });
        
        console.log('[Student] Starting initialization...');
        window.studentMeeting = new StudentMeeting();
        await window.studentMeeting.initialize();
        console.log('[Student] Initialization complete');
    } catch (error) {
        console.error('[Student] Failed to initialize meeting:', error);
        // Show error in UI
        const statusMessage = document.getElementById('status-message');
        if (statusMessage) {
            statusMessage.textContent = 'Failed to initialize meeting. Please refresh the page.';
            statusMessage.className = 'text-red-500';
        }
    }
}); 