// MeetingRoom class for handling student meeting interactions
class MeetingRoom {
    constructor() {
        // Basic properties
        this.meetingData = window.meetingData;
        this.focusScore = 0;
        this.wsConnected = false;
        
        // UI elements
        this.connectionStatus = document.getElementById('connection-status');
        this.focusStatus = document.getElementById('focus-status');
        
        // Initialize
        this.initialize();
    }

    async initialize() {
        try {
            console.log('[MeetingRoom] Initializing...', this.meetingData);
            
            // Initialize WebSocket connection
            await this.initializeWebSocket();
            
            // Set up focus score listener
            this.setupFocusListener();
            
            this.wsConnected = true;
            this.updateConnectionStatus('Connected', 'success');
            this.updateFocusStatus('Focus: Waiting for Python app...');
            
            console.log('[MeetingRoom] Initialization complete');
        } catch (error) {
            console.error('[MeetingRoom] Initialization failed:', error);
            this.updateConnectionStatus('Connection Failed', 'error');
            this.updateFocusStatus('Focus: Error');
        }
    }

    async initializeWebSocket() {
        if (!window.wsManager) {
            throw new Error('WebSocket manager not found');
        }
        
        // Connect to WebSocket
        await window.wsManager.connect();
        
        // Join the meeting
        await window.wsManager.joinMeeting(this.meetingData.meetingId, {
            userId: this.meetingData.userId,
            userName: this.meetingData.userName,
            userRole: this.meetingData.userRole
        });
    }
    
    setupFocusListener() {
        // Listen for focus updates from the Python app
        window.wsManager.on('focus_update', (data) => {
            if (data.userId === this.meetingData.userId) {
                this.handleFocusUpdate(data.focusScore);
            }
        });
    }
    
    handleFocusUpdate(score) {
        this.focusScore = score;
        this.updateFocusStatus(`Focus: ${Math.round(score)}%`);
        
        // Send focus update to server
        this.sendFocusUpdate(score);
    }
    
    async sendFocusUpdate(score) {
        try {
            await fetch(`/api/meetings/${this.meetingData.meetingId}/focus`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    focus_score: score,
                    user_id: this.meetingData.userId
                })
            });
        } catch (error) {
            console.error('[MeetingRoom] Failed to send focus update:', error);
        }
    }
    
    updateConnectionStatus(message, type = 'info') {
        if (this.connectionStatus) {
            this.connectionStatus.textContent = message;
            this.connectionStatus.className = `px-3 py-1 rounded-full text-sm font-medium ${
                type === 'error' ? 'bg-red-500 text-white' :
                type === 'success' ? 'bg-green-500 text-white' :
                'bg-gray-700 text-gray-200'
            }`;
        }
    }
    
    updateFocusStatus(message) {
        if (this.focusStatus) {
            this.focusStatus.textContent = message;
        }
    }
}

// Initialize when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.meetingRoom = new MeetingRoom();
}); 