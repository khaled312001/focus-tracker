/**
 * Focus Tracking Module
 * Handles real-time focus tracking and analysis
 */

class FocusTracker {
    constructor() {
        this.video = document.getElementById('video');
        this.focusScore = document.getElementById('focus-score');
        this.focusBar = document.getElementById('focus-bar');
        this.statusMessage = document.getElementById('status-message');
        this.focusScores = [];
        this.isTracking = false;
        this.lastAnalysisTime = 0;
        this.analyzeInterval = 1000; // Analyze every second
    }

    /**
     * Initialize focus tracking
     */
    async initialize() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            this.video.srcObject = stream;
            this.video.play();
            this.isTracking = true;
            this.startTracking();
        } catch (error) {
            console.error('Error initializing camera:', error);
            this.setStatus('Camera access denied or not available', 'error');
        }
    }

    /**
     * Start continuous focus tracking
     */
    startTracking() {
        if (!this.isTracking) return;
        
        const track = async () => {
            if (!this.isTracking) return;
            
            const now = Date.now();
            if (now - this.lastAnalysisTime >= this.analyzeInterval) {
                await this.analyzeFocus();
                this.lastAnalysisTime = now;
            }
            
            requestAnimationFrame(track);
        };
        
        track();
    }

    /**
     * Analyze current focus level
     */
    async analyzeFocus() {
        if (!this.video || !this.video.videoWidth || !this.video.videoHeight) {
            console.log('Video not ready yet');
            return;
        }

        try {
            // Capture current frame
            const canvas = document.createElement('canvas');
            canvas.width = this.video.videoWidth;
            canvas.height = this.video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(this.video, 0, 0);

            // Convert to blob
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.8));

            // Create form data
            const formData = new FormData();
            formData.append('frame', blob);

            // Send to analysis server
            const response = await fetch('http://127.0.0.1:5000/analyze-focus', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }

            // Update UI with focus score
            this.updateFocusUI(data);

        } catch (error) {
            console.error('Focus analysis error:', error);
            this.setStatus('Focus analysis error: ' + error.message, 'error');
        }
    }

    /**
     * Update UI with focus data
     */
    updateFocusUI(data) {
        // Calculate real focus score from data
        const focusScore = data.focus_score || 0;
        
        // Update focus score display
        if (this.focusScore) {
            this.focusScore.textContent = `${focusScore}%`;
        }

        // Update focus bar with real score and color
        if (this.focusBar) {
            this.focusBar.style.width = `${focusScore}%`;
            this.focusBar.className = `h-2.5 rounded-full transition-all duration-300 ${
                focusScore >= 80 ? 'bg-green-600' :
                focusScore >= 50 ? 'bg-yellow-500' :
                'bg-red-600'
            }`;
        }

        // Set status message based on focus
        this.setStatus(
            focusScore >= 80 ? 'Focused on screen' :
            focusScore >= 50 ? 'Partially focused' :
            'Not focused on screen',
            focusScore >= 80 ? 'success' : 'warning'
        );

        // Create WebSocket update data with proper types
        const updateData = {
            type: 'focus_update',
            meetingId: parseInt(document.getElementById('meeting-id')?.value || '0', 10),
            studentId: parseInt(document.getElementById('user-id')?.value || '0', 10),
            userName: document.getElementById('user-name')?.value || '',
            focusScore: focusScore,
            lastUpdate: new Date().toISOString()
        };

        // Send update through WebSocket if available
        if (window.socket && window.socket.send) {
            window.socket.send(JSON.stringify(updateData));
        }

        // Also dispatch event for local listeners
        const focusUpdateEvent = new CustomEvent('focusUpdate', {
            detail: {
                focusScore: focusScore,
                timestamp: new Date().toISOString()
            }
        });
        window.dispatchEvent(focusUpdateEvent);
    }

    /**
     * Set status message
     */
    setStatus(message, type = 'info') {
        if (!this.statusMessage) return;

        this.statusMessage.textContent = message;
        this.statusMessage.className = 'text-sm font-medium text-green-600';
    }

    /**
     * Stop focus tracking
     */
    stop() {
        this.isTracking = false;
        if (this.video && this.video.srcObject) {
            this.video.srcObject.getTracks().forEach(track => track.stop());
        }
    }
}

// Export the FocusTracker class
export default FocusTracker; 