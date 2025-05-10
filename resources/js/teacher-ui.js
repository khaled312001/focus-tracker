class TeacherMeetingUI {
    constructor(teacherMeeting) {
        this.meeting = teacherMeeting;
        this.startTime = Date.now();
        
        // Cache DOM elements
        this.elements = {
            studentsContainer: document.getElementById('students-container'),
            emptyState: document.getElementById('empty-state'),
            sessionDuration: document.getElementById('session-duration'),
            avgFocus: document.getElementById('avg-focus'),
            classFocus: document.getElementById('class-focus'),
            highFocusCount: document.getElementById('high-focus-count'),
            mediumFocusCount: document.getElementById('medium-focus-count'),
            lowFocusCount: document.getElementById('low-focus-count'),
            studentCount: document.getElementById('student-count'),
            activeStudents: document.getElementById('active-students'),
            meetingStatus: document.getElementById('meeting-status'),
            sortName: document.getElementById('sort-name'),
            sortFocus: document.getElementById('sort-focus')
        };

        // Set up event handlers
        this.setupEventHandlers();
        
        // Start update loops
        this.startUpdateLoops();
    }

    setupEventHandlers() {
        // Listen for meeting events
        this.meeting.on('state_updated', (state) => this.handleStateUpdate(state));
        this.meeting.on('student_joined', ({ userId, userName, state }) => this.handleStateUpdate(state));
        this.meeting.on('student_left', ({ userId, state }) => this.handleStateUpdate(state));
        this.meeting.on('focus_updated', ({ state }) => this.handleStateUpdate(state));
        this.meeting.on('connection_status', ({ status, message }) => this.updateStatus(message, status));
        
        // Set up sorting handlers
        this.elements.sortName?.addEventListener('click', () => this.sortStudents('name'));
        this.elements.sortFocus?.addEventListener('click', () => this.sortStudents('focus'));
    }

    handleStateUpdate(state) {
        this.updateStudentsUI(state);
        this.updateFocusStats(state);
        this.updateStudentCount(state);
    }

    updateStudentsUI(state) {
        if (!this.elements.studentsContainer) return;

        // Clear container
        this.elements.studentsContainer.innerHTML = '';

        // Add student cards
        state.students.forEach(student => {
            const focusData = state.focusData[student.id] || { score: 0 };
            const studentElement = document.createElement('div');
            studentElement.id = `student-${student.id}`;
            studentElement.className = 'student-card';
            studentElement.dataset.studentId = student.id;
            
            studentElement.innerHTML = `
                <h3>${student.name}</h3>
                <p>Focus Score: <span class="focus-score">${Math.round(focusData.score)}%</span></p>
                <p>Status: <span class="status ${student.connected ? 'active' : 'inactive'}">
                    ${student.connected ? 'Active' : 'Inactive'}
                </span></p>
            `;

            const focusScoreElement = studentElement.querySelector('.focus-score');
            if (focusScoreElement) {
                focusScoreElement.style.color = this.getFocusScoreColor(focusData.score);
            }

            this.elements.studentsContainer.appendChild(studentElement);
        });

        // Show/hide empty state
        if (this.elements.emptyState) {
            this.elements.emptyState.style.display = state.students.length === 0 ? 'block' : 'none';
        }
    }

    updateFocusStats(state) {
        if (this.elements.avgFocus) {
            this.elements.avgFocus.textContent = `${Math.round(state.averageFocusScore)}%`;
        }
        if (this.elements.classFocus) {
            this.elements.classFocus.textContent = `${Math.round(state.averageFocusScore)}%`;
        }

        // Calculate focus distributions
        let highFocus = 0, mediumFocus = 0, lowFocus = 0;
        Object.values(state.focusData).forEach(({ score }) => {
            if (score >= 80) highFocus++;
            else if (score >= 50) mediumFocus++;
            else lowFocus++;
        });

        // Update focus distribution counters
        if (this.elements.highFocusCount) {
            this.elements.highFocusCount.textContent = highFocus;
        }
        if (this.elements.mediumFocusCount) {
            this.elements.mediumFocusCount.textContent = mediumFocus;
        }
        if (this.elements.lowFocusCount) {
            this.elements.lowFocusCount.textContent = lowFocus;
        }
    }

    updateStudentCount(state) {
        const activeCount = state.activeStudents;
        const totalCount = state.students.length;

        if (this.elements.studentCount) {
            this.elements.studentCount.textContent = totalCount;
        }
        if (this.elements.activeStudents) {
            this.elements.activeStudents.textContent = activeCount;
        }
    }

    updateStatus(message, type = 'info') {
        if (this.elements.meetingStatus) {
            this.elements.meetingStatus.textContent = message;
            this.elements.meetingStatus.className = `status-message ${type}`;
        }
    }

    getFocusScoreColor(score) {
        if (score >= 80) return '#28a745';  // Green
        if (score >= 60) return '#ffc107';  // Yellow
        return '#dc3545';  // Red
    }

    sortStudents(by) {
        if (!this.elements.studentsContainer) return;

        const cards = Array.from(this.elements.studentsContainer.querySelectorAll('.student-card'));
        const state = this.meeting.getState();
        
        cards.sort((a, b) => {
            const studentA = state.students.find(s => s.id === a.dataset.studentId);
            const studentB = state.students.find(s => s.id === b.dataset.studentId);
            
            if (!studentA || !studentB) return 0;
            
            if (by === 'name') {
                return studentA.name.localeCompare(studentB.name);
            } else {
                const focusA = state.focusData[studentA.id]?.score || 0;
                const focusB = state.focusData[studentB.id]?.score || 0;
                return focusB - focusA;
            }
        });

        // Reappend cards in sorted order
        cards.forEach(card => this.elements.studentsContainer.appendChild(card));
    }

    startUpdateLoops() {
        // Update session duration
        setInterval(() => {
            if (this.elements.sessionDuration) {
                const duration = Math.floor((Date.now() - this.startTime) / 1000);
                const minutes = Math.floor(duration / 60);
                const seconds = duration % 60;
                this.elements.sessionDuration.textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.teacherMeeting) {
        window.teacherUI = new TeacherMeetingUI(window.teacherMeeting);
    }
}); 