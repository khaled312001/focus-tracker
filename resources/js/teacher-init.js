import { WebSocketManager } from './websocket';
import { TeacherMeeting } from './teacher-meeting';

// Initialize the teacher meeting interface
export async function initializeTeacherMeeting() {
    try {
        // Wait a moment to ensure all scripts are loaded
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Initialize WebSocket Manager
        if (!window.wsManager) {
            window.wsManager = new WebSocketManager();
        }
        
        const meetingId = document.getElementById('meeting-id').value;
        const teacherId = document.getElementById('teacher-id').value;
        const teacherName = document.getElementById('teacher-name').value;
        window.teacherMeeting = new TeacherMeeting(meetingId, teacherId, teacherName);
    } catch (error) {
        console.error('[Teacher] Failed to initialize:', error);
        // Show error in UI
        const errorMessage = document.createElement('div');
        errorMessage.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded fixed top-4 right-4';
        errorMessage.textContent = 'Failed to initialize meeting. Please refresh the page.';
        document.body.appendChild(errorMessage);
        throw error; // Re-throw to be caught by the caller
    }
} 