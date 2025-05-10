class FocusTracking {
    constructor() {
        this.video = document.getElementById('video');
        this.focusScore = document.getElementById('focus-score');
        this.sessionTime = document.getElementById('session-time');
        this.statusMessage = document.getElementById('status-message');
        this.userId = document.querySelector('meta[name="user-id"]').content;
        this.meetingId = document.querySelector('meta[name="meeting-id"]').content;
        this.startTime = new Date();
        this.isTracking = false;
        this.lastUpdateTime = null;
        this.updateInterval = null;
    }

    async initializeCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: "user"
                } 
            });
            this.video.srcObject = stream;
            await this.video.play();
            this.startTracking();
        } catch (error) {
            console.error('Error accessing camera:', error);
            this.statusMessage.textContent = 'Error: Could not access camera';
            this.statusMessage.style.color = 'red';
        }
    }

    startTracking() {
        if (this.isTracking) return;
        
        this.isTracking = true;
        this.lastUpdateTime = new Date();
        
        // Update session time every second
        this.updateInterval = setInterval(() => {
            this.updateSessionTime();
            this.trackFocus();
        }, 1000);
    }

    stopTracking() {
        if (!this.isTracking) return;
        
        this.isTracking = false;
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
        
        if (this.video.srcObject) {
            const tracks = this.video.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            this.video.srcObject = null;
        }
    }

    updateSessionTime() {
        const now = new Date();
        const diff = now - this.startTime;
        const hours = Math.floor(diff / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        this.sessionTime.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    async trackFocus() {
        if (!this.isTracking) return;

        const now = new Date();
        const timeDiff = (now - this.lastUpdateTime) / 1000; // Convert to seconds
        
        try {
            const response = await fetch('/api/focus-logs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    student_id: this.userId,
                    meeting_id: this.meetingId,
                    focus_level: 100, // This should be replaced with actual focus calculation
                    duration: Math.round(timeDiff),
                    timestamp: now.toISOString()
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.status === 'success' && data.data) {
                this.updateFocusUI(data.data.focus_level);
                this.lastUpdateTime = now;
            } else {
                throw new Error('Invalid response format');
            }

        } catch (error) {
            console.error('Error updating focus:', error);
            this.statusMessage.textContent = 'Error: Failed to update focus score';
            this.statusMessage.style.color = 'red';
        }
    }

    updateFocusUI(score) {
        // Format the score based on its value
        let displayScore;
        if (score === 0 || score === 100) {
            displayScore = Math.round(score);
        } else {
            displayScore = score.toFixed(1);
        }
        
        this.focusScore.textContent = displayScore;

        // Update status message and color based on score
        if (score >= 90) {
            this.statusMessage.textContent = 'Excellent focus!';
            this.statusMessage.style.color = '#22c55e'; // green-500
        } else if (score >= 70) {
            this.statusMessage.textContent = 'Good focus';
            this.statusMessage.style.color = '#3b82f6'; // blue-500
        } else if (score >= 50) {
            this.statusMessage.textContent = 'Moderate focus';
            this.statusMessage.style.color = '#f59e0b'; // amber-500
        } else {
            this.statusMessage.textContent = 'Need to focus more';
            this.statusMessage.style.color = '#ef4444'; // red-500
        }
    }
}

// Initialize focus tracking when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const focusTracker = new FocusTracking();
    focusTracker.initializeCamera();
}); 