// Handles teacher-specific meeting functionality
export class TeacherHandler {
    constructor(meetingId, teacherId, teacherName) {
        this.meetingId = meetingId;
        this.teacherId = teacherId;
        this.teacherName = teacherName;
        this.wsManager = window.wsManager;
        this.students = new Map();
        this.initialized = false;
        this.elements = this.initializeElements();
        
        // Wait for WebSocket to be ready before initializing
        if (this.wsManager?.isConnected) {
            console.log('[TeacherHandler] WebSocket already connected, initializing...');
            this.initialize().catch(error => {
                console.error('[Teacher] Failed to initialize:', error);
            });
        } else {
            console.log('[TeacherHandler] Waiting for WebSocket connection...');
            // First remove any existing handlers to prevent duplicates
            this.wsManager?.off('connected');
            // Then add our handler
            this.wsManager?.on('connected', () => {
                console.log('[TeacherHandler] WebSocket connected event received, initializing...');
                this.initialize().catch(error => {
                    console.error('[Teacher] Failed to initialize:', error);
                });
            });
        }
    }

    // Initialize elements
    initializeElements() {
        return {
            studentList: document.getElementById('students-list'),
            averageFocus: document.getElementById('average-focus'),
            activeStudents: document.getElementById('active-students'),
            sortByName: document.getElementById('sort-name'),
            sortByFocus: document.getElementById('sort-focus'),
            highFocusCount: document.getElementById('high-focus-count'),
            mediumFocusCount: document.getElementById('medium-focus-count'),
            lowFocusCount: document.getElementById('low-focus-count'),
            highFocusBar: document.getElementById('high-focus-bar'),
            mediumFocusBar: document.getElementById('medium-focus-bar'),
            lowFocusBar: document.getElementById('low-focus-bar')
        };
    }

    // Setup WebSocket message handlers
    setupHandlers() {
        // First remove any existing handlers to prevent duplicates
        this.cleanup();

        // Handle join confirmation
        this.wsManager.on('join_confirmed', (data) => {
            console.log('[TeacherHandler] Join confirmed:', data);
        });

        // Handle student status updates
        this.wsManager.on('student_state', (data) => {
            console.log('[TeacherHandler] Raw student status update:', data);
            this.handleStudentStatus(this.normalizeMessage(data));
        });

        // Handle meeting state updates
        this.wsManager.on('meeting_state', (data) => {
            console.log('[TeacherHandler] Raw meeting state update:', data);
            this.handleMeetingState(this.normalizeMessage(data));
        });

        // Handle student join events
        this.wsManager.on('user_joined', (data) => {
            console.log('[TeacherHandler] Raw user joined:', data);
            const normalized = this.normalizeMessage(data);
            if (normalized.userrole?.toLowerCase() === 'student') {
                this.handleStudentJoined(normalized);
            }
        });

        // Handle student leave events
        this.wsManager.on('user_left', (data) => {
            console.log('[TeacherHandler] Raw user left:', data);
            const normalized = this.normalizeMessage(data);
            if (normalized.userrole?.toLowerCase() === 'student') {
                this.handleStudentLeft(normalized);
            }
        });

        // Handle error messages
        this.wsManager.on('ERROR', (data) => {
            console.error('[TeacherHandler] WebSocket error:', data);
            if (this.elements.studentList) {
                this.elements.studentList.innerHTML = `
                    <div class="text-center py-8 text-red-500 col-span-2">
                        <p>Connection error: ${data.error || 'Unknown error'}</p>
                        <p class="text-sm mt-2">Please refresh the page to try again</p>
                    </div>
                `;
            }
        });
    }

    // Normalize message keys to lowercase
    normalizeMessage(message) {
        if (!message || typeof message !== 'object') {
            console.warn('[TeacherHandler] Invalid message received:', message);
            return {};
        }

        const normalized = {};
        for (const [key, value] of Object.entries(message)) {
            // Store both lowercase and original case versions
            normalized[key.toLowerCase()] = value;
            normalized[key] = value;
        }
        
        console.log('[TeacherHandler] Normalized message:', normalized);
        return normalized;
    }

    // Setup sorting handlers
    setupSortingHandlers() {
        if (this.elements.sortByName) {
            this.elements.sortByName.addEventListener('click', () => this.sortStudents('name'));
        }
        if (this.elements.sortByFocus) {
            this.elements.sortByFocus.addEventListener('click', () => this.sortStudents('focus'));
        }
    }

    // Handle meeting state update
    handleMeetingState(data) {
        console.log('[TeacherHandler] Processing meeting state:', data);
        
        // Clear existing students
        this.students.clear();
        
        // Process each student in the state
        if (data.students && typeof data.students === 'object') {
            console.log('[TeacherHandler] Processing students:', data.students);
            Object.entries(data.students).forEach(([studentId, studentData]) => {
                if (!studentData) return;
                
                console.log('[TeacherHandler] Processing student:', studentId, studentData);
                this.students.set(studentId, {
                    id: studentId,
                    name: studentData.name || studentData.username || studentData.userName || 'Unknown',
                    focusScore: parseFloat(studentData.focusscore || studentData.focusScore || studentData.focus_score || 0),
                    isActive: Boolean(studentData.isactive || studentData.isActive || studentData.is_active || false),
                    lastUpdate: new Date(studentData.lastupdate || studentData.lastUpdate || studentData.last_update || Date.now())
                });
            });
        }

        this.updateUI();
    }

    // Handle student status update
    handleStudentStatus(data) {
        console.log('[TeacherHandler] Processing student status update:', data);
        
        const studentId = data.userId || data.studentId;
        if (!studentId) {
            console.error('[TeacherHandler] Missing student ID in status update');
            return;
        }

        // Update student in the map
        if (!this.students.has(studentId)) {
            console.log('[TeacherHandler] Adding new student:', data);
            this.students.set(studentId, {
                id: studentId,
                name: data.userName,
                focusScore: parseFloat(data.focusScore || 0),
                isActive: Boolean(data.isActive),
                lastUpdate: new Date()
            });
        } else {
            console.log('[TeacherHandler] Updating existing student:', data);
            const student = this.students.get(studentId);
            student.focusScore = parseFloat(data.focusScore || student.focusScore || 0);
            student.isActive = Boolean(data.isActive);
            student.lastUpdate = new Date();
            this.students.set(studentId, student);
        }

        // Update student card
        const studentCard = document.querySelector(`[data-student-id="${studentId}"]`);
        if (studentCard) {
            // Update focus score
            const focusScoreElement = studentCard.querySelector('.focus-score');
            if (focusScoreElement) {
                const score = parseFloat(data.focusScore || 0);
                focusScoreElement.textContent = `${Math.round(score)}%`;
                focusScoreElement.style.color = this.getFocusScoreColor(score);
            }

            // Update focus bar
            const focusBar = studentCard.querySelector('.focus-bar');
            if (focusBar) {
                const score = parseFloat(data.focusScore || 0);
                focusBar.style.width = `${score}%`;
                focusBar.className = `focus-bar h-2 rounded-full transition-all duration-300 ${this.getFocusScoreClass(score).replace('text-', 'bg-')}`;
            }

            // Update active status
            studentCard.classList.toggle('inactive', !data.isActive);
            const statusIndicator = studentCard.querySelector('.status-indicator');
            const attentionStatus = studentCard.querySelector('.attention-status');
            if (statusIndicator && attentionStatus) {
                statusIndicator.classList.toggle('active', data.isActive);
                statusIndicator.classList.toggle('inactive', !data.isActive);
                attentionStatus.textContent = data.isActive ? 'Active' : 'Inactive';
            }

            // Update focus time
            const focusTimeElement = studentCard.querySelector('.focus-time');
            if (focusTimeElement) {
                const student = this.students.get(studentId);
                const focusTime = student.focusScore * (Date.now() - student.lastUpdate) / 100;
                focusTimeElement.textContent = this.formatDuration(focusTime);
            }
        } else {
            console.log('[TeacherHandler] Creating new student card for:', studentId);
            const student = this.students.get(studentId);
            if (student) {
                const newCard = this.createStudentElement(student);
                this.elements.studentList?.appendChild(newCard);
            }
        }

        // Update metrics
        this.updateMetrics();
    }

    getErrorMessage(errorType) {
        const errorMessages = {
            'camera_disconnected': '📷 Camera disconnected',
            'audio_issues': '🎤 Audio issues detected',
            'connection_lost': '🔌 Connection lost',
            'low_bandwidth': '📶 Poor connection',
            'default': '⚠️ Error detected'
        };
        return errorMessages[errorType] || errorMessages.default;
    }

    getFocusScoreColor(score) {
        if (score >= 75) return '#4CAF50';  // Green
        if (score >= 50) return '#FFA726';  // Orange
        if (score >= 25) return '#FF7043';  // Light Red
        return '#E53935';  // Red
    }

    // Handle student joined event
    handleStudentJoined(data) {
        const userId = data.userid || data.userId || data.user_id;
        const userName = data.username || data.userName || data.user_name || 'Unknown';
        
        console.log('[TeacherHandler] Student joined:', { userId, userName });
        
        this.students.set(userId, {
            id: userId,
            name: userName,
            focusScore: 0,
            isActive: true,
            lastUpdate: new Date()
        });
        this.updateUI();
    }

    // Handle student left event
    handleStudentLeft(data) {
        const userId = data.userid || data.userId || data.user_id;
        console.log('[TeacherHandler] Student left:', userId);
        this.students.delete(userId);
        this.updateUI();
    }

    // Update the UI with current state
    updateUI() {
        this.updateStudentList();
        this.updateMetrics();
    }

    // Update student list in UI
    updateStudentList() {
        if (!this.elements.studentList) {
            console.warn('[TeacherHandler] Student list element not found');
            return;
        }

        const studentArray = Array.from(this.students.values());
        
        // Show message if no students
        if (studentArray.length === 0) {
            this.elements.studentList.innerHTML = `
                <div class="text-center py-8 text-gray-500 col-span-2">
                    <p>No students have joined yet</p>
                    <p class="text-sm mt-2">Share the meeting ID with your students to get started</p>
                </div>
            `;
            return;
        }

        const fragment = document.createDocumentFragment();

        studentArray.forEach(student => {
            const studentElement = this.createStudentElement(student);
            fragment.appendChild(studentElement);
        });

        this.elements.studentList.innerHTML = '';
        this.elements.studentList.appendChild(fragment);
    }

    // Create student element
    createStudentElement(student) {
        const div = document.createElement('div');
        div.className = 'bg-gray-700 rounded-lg p-4 student-card hover:bg-gray-600 transition-colors duration-200';
        div.dataset.studentId = student.id;

        // Calculate focus class
        const focusClass = this.getFocusScoreClass(student.focusScore);
        const focusBarClass = focusClass.replace('text-', 'bg-');

        div.innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center">
                        <span class="student-initial text-xl text-white">${student.name.charAt(0).toUpperCase()}</span>
                    </div>
                    <div>
                        <h3 class="text-white font-medium student-name">${student.name}</h3>
                        <div class="flex items-center space-x-2">
                            <div class="status-indicator w-2 h-2 rounded-full ${student.isActive ? 'active' : 'inactive'}"></div>
                            <span class="text-sm text-gray-400 attention-status">${student.isActive ? 'Active' : 'Inactive'}</span>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold focus-score ${focusClass}">${Math.round(student.focusScore)}%</div>
                    <div class="text-sm text-gray-400">Focus Score</div>
                </div>
            </div>
            <div class="space-y-3">
                <div class="w-full">
                    <div class="bg-gray-600 rounded-full h-2">
                        <div class="focus-bar h-2 rounded-full transition-all duration-300 ${focusBarClass}" 
                             style="width: ${student.focusScore}%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-400">Session Time</div>
                        <div class="text-white total-time">${this.formatDuration(Date.now() - student.lastUpdate)}</div>
                    </div>
                    <div>
                        <div class="text-gray-400">Focus Time</div>
                        <div class="text-white focus-time">${this.formatDuration(student.focusScore * (Date.now() - student.lastUpdate) / 100)}</div>
                    </div>
                </div>
            </div>
        `;

        return div;
    }

    // Update metrics in UI
    updateMetrics() {
        const students = Array.from(this.students.values());
        const totalStudents = students.length;

        // Calculate focus distribution
        const highFocus = students.filter(s => s.focusScore >= 80).length;
        const mediumFocus = students.filter(s => s.focusScore >= 50 && s.focusScore < 80).length;
        const lowFocus = students.filter(s => s.focusScore < 50).length;

        // Update counts
        if (this.elements.highFocusCount) {
            this.elements.highFocusCount.textContent = highFocus;
        }
        if (this.elements.mediumFocusCount) {
            this.elements.mediumFocusCount.textContent = mediumFocus;
        }
        if (this.elements.lowFocusCount) {
            this.elements.lowFocusCount.textContent = lowFocus;
        }

        // Update bars
        if (totalStudents > 0) {
            if (this.elements.highFocusBar) {
                this.elements.highFocusBar.style.width = `${(highFocus / totalStudents) * 100}%`;
            }
            if (this.elements.mediumFocusBar) {
                this.elements.mediumFocusBar.style.width = `${(mediumFocus / totalStudents) * 100}%`;
            }
            if (this.elements.lowFocusBar) {
                this.elements.lowFocusBar.style.width = `${(lowFocus / totalStudents) * 100}%`;
            }
        }

        // Update average focus
        if (this.elements.averageFocus) {
            const averageFocus = this.calculateAverageFocus();
            this.elements.averageFocus.textContent = `${Math.round(averageFocus)}%`;
        }

        // Update active students count
        if (this.elements.activeStudents) {
            const activeCount = students.filter(s => s.isActive).length;
            this.elements.activeStudents.textContent = activeCount;
        }
    }

    // Calculate average focus score
    calculateAverageFocus() {
        const students = Array.from(this.students.values());
        if (students.length === 0) return 0;
        
        const sum = students.reduce((acc, student) => acc + student.focusScore, 0);
        return sum / students.length;
    }

    // Sort students
    sortStudents(by) {
        if (!this.elements.studentList) return;

        const studentArray = Array.from(this.students.values());
        
        studentArray.sort((a, b) => {
            if (by === 'name') {
                return a.name.localeCompare(b.name);
            } else if (by === 'focus') {
                return b.focusScore - a.focusScore;
            }
            return 0;
        });

        this.updateStudentList();
    }

    // Get CSS class for focus score
    getFocusScoreClass(score) {
        if (score >= 80) return 'text-green-400';
        if (score >= 50) return 'text-yellow-400';
        return 'text-red-400';
    }

    // Format duration in minutes and seconds
    formatDuration(ms) {
        const minutes = Math.floor(ms / 60000);
        const seconds = Math.floor((ms % 60000) / 1000);
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    // Clean up when leaving
    cleanup() {
        if (!this.wsManager) return;
        
        const handlers = [
            'JOIN_CONFIRMED',
            'STUDENT_STATE',
            'MEETING_STATE',
            'USER_JOINED',
            'USER_LEFT',
            'ERROR'
        ];
        
        handlers.forEach(type => {
            this.wsManager.off(type);
        });
    }

    // Initialize teacher connection
    async initialize() {
        if (this.initialized) {
            console.log('[TeacherHandler] Already initialized');
            return;
        }

        console.log('[TeacherHandler] Initializing...');
        
        try {
            // Setup message handlers
            this.setupHandlers();
            
            // Setup sorting handlers
            this.setupSortingHandlers();
            
            // Send join message
            const joinData = {
                TYPE: 'JOIN',
                meetingId: parseInt(this.meetingId),
                userId: parseInt(this.teacherId),
                userName: this.teacherName,
                userRole: 'teacher'
            };
            
            console.log('[TeacherHandler] Sending join message:', joinData);
            await this.wsManager.send(joinData);
            
            // Request initial meeting state
            const stateRequest = {
                TYPE: 'REQUEST_MEETING_STATE',
                meetingId: parseInt(this.meetingId)
            };
            
            console.log('[TeacherHandler] Requesting meeting state:', stateRequest);
            await this.wsManager.send(stateRequest);
            
            this.initialized = true;
            console.log('[TeacherHandler] Initialization complete');
        } catch (error) {
            console.error('[TeacherHandler] Initialization failed:', error);
            throw error;
        }
    }
} 