// Meeting room functionality for teachers
export class TeacherMeeting {
    constructor(meetingId, teacherId, teacherName) {
        this.meetingId = meetingId;
        this.teacherId = teacherId;
        this.teacherName = teacherName;
        this.students = new Map();
        this.focusData = new Map();
        this.lastUpdateTime = null;
        this.wsManager = window.wsManager;
        this.handlers = new Map();
        this.initialized = false;
        this.elements = this.initializeElements();

        this.log('Initializing teacher meeting interface');
        this.initialize();
    }

    log(message, type = 'info') {
        const timestamp = new Date().toISOString();
        const prefix = `[Teacher Meeting ${this.meetingId}]`;
        
        switch(type) {
            case 'error':
                console.error(`${prefix} ${message}`);
                break;
            case 'warn':
                console.warn(`${prefix} ${message}`);
                break;
            case 'success':
                console.log(`%c${prefix} ${message}`, 'color: green');
                break;
            default:
                console.log(`%c${prefix} ${message}`, 'color: blue');
        }
    }

    initializeElements() {
        this.log('Initializing UI elements');
        return {
            studentsList: document.getElementById('students-list'),
            averageFocus: document.getElementById('average-focus'),
            activeStudents: document.getElementById('active-students'),
            highFocusCount: document.getElementById('high-focus-count'),
            mediumFocusCount: document.getElementById('medium-focus-count'),
            lowFocusCount: document.getElementById('low-focus-count'),
            highFocusBar: document.getElementById('high-focus-bar'),
            mediumFocusBar: document.getElementById('medium-focus-bar'),
            lowFocusBar: document.getElementById('low-focus-bar'),
            sortName: document.getElementById('sort-name'),
            sortFocus: document.getElementById('sort-focus'),
            endMeeting: document.getElementById('end-meeting'),
            studentTemplate: document.getElementById('student-template')
        };
    }

    async initialize() {
        try {
            this.log('Joining meeting as teacher');
            
            await this.wsManager.joinMeeting(this.meetingId, {
                userId: this.teacherId,
                userName: this.teacherName,
                userRole: 'teacher'
            });

            this.setupEventHandlers();
            this.initialized = true;

            this.log('Meeting initialized successfully', 'success');
        } catch (error) {
            this.log(`Failed to initialize meeting: ${error.message}`, 'error');
            this.showError('Failed to initialize meeting: ' + error.message);
        }
    }

    setupEventHandlers() {
        this.log('Setting up event handlers');

        this.wsManager.on('meeting_state', (data) => {
            this.log(`Received meeting state update with ${Object.keys(data.students || {}).length} students`);
            this.handleMeetingState(data);
        });

        this.wsManager.on('focus_update', (data) => {
            const student = this.students.get(data.studentId);
            this.log(`Focus update from ${student?.name || data.studentId}: ${data.focusScore}%`);
            this.handleFocusUpdate(data);
        });

        window.addEventListener('websocket-status', (event) => {
            this.log(`WebSocket status changed: ${event.detail.status}`, event.detail.status === 'connected' ? 'success' : 'warn');
            this.handleConnectionStatus(event.detail);
        });

        if (this.elements.sortName) {
            this.elements.sortName.addEventListener('click', () => {
                this.log('Sorting students by name');
                this.sortStudents('name');
            });
        }

        if (this.elements.sortFocus) {
            this.elements.sortFocus.addEventListener('click', () => {
                this.log('Sorting students by focus score');
                this.sortStudents('focus');
            });
        }
    }

    handleMeetingState(data) {
        if (!data || !data.students) {
            this.log('Invalid meeting state received', 'error');
            return;
        }

        this.log(`Processing meeting state with ${Object.keys(data.students).length} students`);

        this.students.clear();
        this.focusData.clear();

        Object.entries(data.students).forEach(([userId, student]) => {
            this.students.set(userId, {
                id: userId,
                name: student.name,
                connected: true,
                lastSeen: new Date(student.lastUpdate * 1000)
            });

            this.focusData.set(userId, {
                score: student.focusScore,
                timestamp: new Date(student.lastUpdate * 1000)
            });

            this.log(`Updated student ${student.name}: Focus ${student.focusScore}%, Last seen: ${this.getTimeSince(new Date(student.lastUpdate * 1000))}`);
        });

        this.lastUpdateTime = new Date();
        this.updateUI();
    }

    handleFocusUpdate(data) {
        const { studentId, focusScore } = data;
        
        if (!this.students.has(studentId)) {
            this.log(`Received focus update for unknown student: ${studentId}`, 'warn');
            return;
        }

        const student = this.students.get(studentId);
        const oldScore = this.focusData.get(studentId)?.score || 0;

        this.focusData.set(studentId, {
            score: focusScore,
            timestamp: new Date()
        });

        student.lastSeen = new Date();
        this.students.set(studentId, student);

        this.log(`Focus score updated for ${student.name}: ${oldScore}% → ${focusScore}%`);
        this.updateUI();
    }

    updateUI() {
        this.updateStudentsList();
        this.updateFocusStats();
        this.updateFocusDistribution();
    }

    updateStudentsList() {
        if (!this.elements.studentsList || !this.elements.studentTemplate) return;

        const studentsList = this.elements.studentsList;
        studentsList.innerHTML = '';

        if (this.students.size === 0) {
            studentsList.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <p>No students have joined yet</p>
                    <p class="text-sm mt-2">Share the meeting ID with your students to get started</p>
                </div>
            `;
            return;
        }

        const sortedStudents = Array.from(this.students.values())
            .sort((a, b) => a.name.localeCompare(b.name));

        sortedStudents.forEach(student => {
            const focusData = this.focusData.get(student.id);
            const template = this.elements.studentTemplate.content.cloneNode(true);
            const card = template.querySelector('.student-card');

            // Set student data
            card.dataset.studentId = student.id;
            card.querySelector('.student-name').textContent = student.name;
            card.querySelector('.join-time').textContent = this.getTimeSince(student.lastSeen);

            // Set focus data
            const focusScore = focusData ? focusData.score : 0;
            card.querySelector('.focus-score').textContent = `${focusScore.toFixed(1)}%`;

            // Update focus bar
            const focusBar = card.querySelector('.focus-bar');
            focusBar.style.width = `${focusScore}%`;
            focusBar.className = `focus-bar h-2 rounded-full transition-all duration-300 ${this.getFocusColorClass(focusScore)}`;

            // Update status indicator
            const statusIndicator = card.querySelector('.status-indicator');
            const isActive = (new Date() - student.lastSeen) < 30000; // 30 seconds
            statusIndicator.className = `status-indicator w-3 h-3 rounded-full ${isActive ? 'bg-green-500' : 'bg-red-500'}`;

            studentsList.appendChild(template);
        });
    }

    updateFocusStats() {
        if (!this.elements.averageFocus || !this.elements.activeStudents) return;

        const now = new Date();
        const activeStudents = Array.from(this.students.values())
            .filter(student => (now - student.lastSeen) < 30000);

        const focusScores = Array.from(this.focusData.values())
            .map(data => data.score)
            .filter(score => !isNaN(score));

        const averageFocus = focusScores.length > 0
            ? focusScores.reduce((a, b) => a + b, 0) / focusScores.length
            : 0;

        this.elements.averageFocus.textContent = `${averageFocus.toFixed(1)}%`;
        this.elements.activeStudents.textContent = activeStudents.length;
    }

    updateFocusDistribution() {
        const focusScores = Array.from(this.focusData.values())
            .map(data => data.score)
            .filter(score => !isNaN(score));

        const highFocus = focusScores.filter(score => score >= 80).length;
        const mediumFocus = focusScores.filter(score => score >= 50 && score < 80).length;
        const lowFocus = focusScores.filter(score => score < 50).length;

        const total = Math.max(focusScores.length, 1);

        if (this.elements.highFocusCount) {
            this.elements.highFocusCount.textContent = highFocus;
            this.elements.highFocusBar.style.width = `${(highFocus / total) * 100}%`;
        }

        if (this.elements.mediumFocusCount) {
            this.elements.mediumFocusCount.textContent = mediumFocus;
            this.elements.mediumFocusBar.style.width = `${(mediumFocus / total) * 100}%`;
        }

        if (this.elements.lowFocusCount) {
            this.elements.lowFocusCount.textContent = lowFocus;
            this.elements.lowFocusBar.style.width = `${(lowFocus / total) * 100}%`;
        }
    }

    getFocusColorClass(score) {
        return score >= 80 ? 'bg-green-500' :
               score >= 50 ? 'bg-yellow-500' :
               'bg-red-500';
    }

    getTimeSince(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        if (seconds < 60) return 'just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        return `${hours}h ${minutes % 60}m ago`;
    }

    sortStudents(by = 'name') {
        const students = Array.from(this.students.values());
        if (by === 'name') {
            students.sort((a, b) => a.name.localeCompare(b.name));
        } else if (by === 'focus') {
            students.sort((a, b) => {
                const scoreA = this.focusData.get(a.id)?.score || 0;
                const scoreB = this.focusData.get(b.id)?.score || 0;
                return scoreB - scoreA;
            });
        }
        this.updateUI();
    }

    showError(message) {
        this.log(message, 'error');
        // You can add UI error display here
    }

    handleConnectionStatus({ status, message }) {
        this.log(`Connection status: ${status} - ${message}`, status === 'connected' ? 'success' : 'warn');
        // You can add UI status display here
    }

    async endMeeting() {
        try {
            this.log('Ending meeting...');
            await fetch(`/meetings/${this.meetingId}/end`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            this.log('Meeting ended successfully', 'success');
            window.location.href = '/meetings';
        } catch (error) {
            this.log(`Failed to end meeting: ${error.message}`, 'error');
            this.showError('Failed to end meeting: ' + error.message);
        }
    }
}

// Remove automatic initialization 