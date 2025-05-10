'use strict';

class MeetingRoom {
    constructor() {
        // Initialize properties
        this.localStream = null;
        this.socket = null;
        this.meetingId = null;
        this.userId = null;
        this.userName = null;
        this.userRole = null;
        this.focusTrackingInterval = null;
        this.isVideoEnabled = true;
        this.isAudioEnabled = true;
        this.students = new Map(); // Map to store student data
        this.raisedHands = new Set(); // Set to store raised hands
        this.focusScores = new Map(); // Map to store student focus scores
        this.lastFocusUpdate = 0; // Timestamp of last focus update
        this.focusUpdateInterval = 5000; // Update focus every 5 seconds
        this.isScreenSharing = false;
        this.screenStream = null;
        this.remoteStreams = new Map(); // Map to store remote video streams
        this.peerConnections = new Map(); // Map to store RTCPeerConnections
        this.focusHistory = null; // Added for focus tracking history
        this.peerConnection = null;
    }

    async initialize() {
        try {
            console.log('Initializing meeting room...');
            
            // Get meeting and user info from window.meetingData
            if (!window.meetingData) {
                throw new Error('Meeting data not found');
            }

            const { userId, userName, userRole, meetingId } = window.meetingData;

            // Validate required information
            if (!meetingId || !userId || !userName || !userRole) {
                const missingFields = [];
                if (!meetingId) missingFields.push('Meeting ID');
                if (!userId) missingFields.push('User ID');
                if (!userName) missingFields.push('User Name');
                if (!userRole) missingFields.push('User Role');
                throw new Error(`Missing required information: ${missingFields.join(', ')}`);
            }

            // Set instance properties
            this.meetingId = meetingId;
            this.userId = userId;
            this.userName = userName;
            this.userRole = userRole;

            console.log('User Role:', this.userRole);
            console.log('Meeting ID:', this.meetingId);
            console.log('User ID:', this.userId);

            // Create necessary containers first
            this.createUIContainers();

            // Initialize video
            await this.initializeLocalVideo();
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Start focus tracking if student
            if (this.userRole === 'student') {
                this.startFocusTracking();
            }

            // Initialize WebSocket last
            await this.setupWebSocket();

            console.log('Meeting room initialized successfully');
        } catch (error) {
            console.error('Initialization error:', error);
            this.handleError(error);
            throw error;
        }
    }

    async setupWebSocket() {
        try {
            logDebug('Setting up WebSocket connection...');
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//127.0.0.1:6001/ws/meeting/${this.meetingId}`;
            logDebug(`WebSocket URL: ${wsUrl}`);
            
            return new Promise((resolve, reject) => {
                this.socket = new WebSocket(wsUrl);

                this.socket.onopen = () => {
                    logDebug('WebSocket connection established', 'success');
                    updateConnectionStatus('Connected', 'green');
                    
                    // Send initial join message
                    const joinMessage = {
                        type: 'join',
                        meetingId: this.meetingId,
                        userId: this.userId,
                        userName: this.userName,
                        userRole: this.userRole
                    };
                    logDebug(`Sending join message: ${JSON.stringify(joinMessage)}`);
                    this.socket.send(JSON.stringify(joinMessage));

                    // If teacher, request current meeting state
                    if (this.userRole === 'teacher') {
                        const stateRequest = {
                            type: 'request_meeting_state',
                            meetingId: this.meetingId,
                            userId: this.userId
                        };
                        logDebug(`Requesting meeting state: ${JSON.stringify(stateRequest)}`);
                        this.socket.send(JSON.stringify(stateRequest));
                    }
                    resolve();
                };

                this.socket.onmessage = async (event) => {
                    try {
                        let data = event.data;
                        if (data instanceof Blob) {
                            data = await data.text();
                        }
                        data = JSON.parse(data);
                        logDebug(`Received WebSocket message: ${JSON.stringify(data)}`);

                        // Update meeting info with latest state
                        if (data.type === 'meeting_state') {
                            updateMeetingInfo({
                                'Active Students': data.activeStudents,
                                'Active Teachers': data.activeTeachers,
                                'Total Participants': data.participants.length
                            });
                        }

                        // Dispatch event for teacher meeting
                        document.dispatchEvent(new CustomEvent('websocket-message', { detail: data }));
                        
                        switch (data.type) {
                            case 'join':
                                await this.handleUserJoin(data);
                                break;
                            case 'leave':
                                await this.handleUserLeave(data);
                                break;
                            case 'focus_update':
                                this.handleFocusUpdate(data);
                                break;
                            case 'meeting_state':
                                this.handleMeetingState(data);
                                break;
                            case 'request_meeting_state':
                                this.handleMeetingStateRequest(data);
                                break;
                            case 'student_state_update':
                                this.handleStudentStateUpdate(data);
                                break;
                            case 'screen_share':
                                await this.handleScreenShare(data);
                                break;
                            case 'ice_candidate':
                                await this.handleICECandidate(data);
                                break;
                            case 'offer':
                                await this.handleOffer(data);
                                break;
                            case 'answer':
                                await this.handleAnswer(data);
                                break;
                            case 'error':
                                logDebug(`WebSocket error: ${data.message}`, 'error');
                                break;
                        }
                    } catch (error) {
                        logDebug(`Error handling WebSocket message: ${error.message}`, 'error');
                    }
                };

                this.socket.onclose = (event) => {
                    logDebug(`WebSocket connection closed. Code: ${event.code}, Reason: ${event.reason}`, 'error');
                    updateConnectionStatus('Disconnected - Reconnecting...', 'red');
                    // Attempt to reconnect after 5 seconds
                    setTimeout(() => {
                        logDebug('Attempting to reconnect...');
                        this.setupWebSocket();
                    }, 5000);
                };

                this.socket.onerror = (error) => {
                    logDebug(`WebSocket error: ${error.message}`, 'error');
                    updateConnectionStatus('Connection Error', 'red');
                    reject(error);
                };
            });
        } catch (error) {
            logDebug(`Error setting up WebSocket: ${error.message}`, 'error');
            updateConnectionStatus('Setup Error', 'red');
            throw error;
        }
    }

    async handleUserJoin(data) {
        if (data.userId !== this.userId) {
            logDebug(`User joined: ${JSON.stringify(data)}`);
            
            // Create peer connection for all participants
            const pc = await this.createPeerConnection(data.userId);
            
            // Handle student join/rejoin in teacher view
            if (this.userRole === 'teacher' && data.userRole === 'student') {
                let student = this.students.get(data.userId);
                const isRejoin = !!student;

                if (!student) {
                    // New student joining
                    logDebug(`New student joining: ${data.userName}`);
                    student = {
                        id: data.userId,
                        name: data.userName,
                        focusScore: 0,
                        focusTime: 0,
                        lastUpdate: new Date(),
                        isActive: true,
                        joinTime: new Date()
                    };
                } else {
                    // Student rejoining
                    logDebug(`Student rejoining: ${data.userName}`);
                    student.isActive = true;
                    student.lastUpdate = new Date();
                    student.rejoinTime = new Date();
                }

                this.students.set(data.userId, student);
                this.updateStudentListUI();

                // Update active students count
                const activeStudentsElement = document.getElementById('active-students');
                if (activeStudentsElement) {
                    const activeCount = Array.from(this.students.values()).filter(s => s.isActive).length;
                    activeStudentsElement.textContent = `${activeCount}`;
                    logDebug(`Updated active students count: ${activeCount}`);
                }

                // Show rejoin notification if applicable
                if (isRejoin) {
                    this.showNotification(`${student.name} has rejoined the meeting`);
                }
            }

            // Rest of the WebRTC setup
            if (this.userRole === 'teacher' || data.userRole === 'teacher') {
                try {
                    if (this.localStream) {
                        this.localStream.getTracks().forEach(track => {
                            pc.addTrack(track, this.localStream);
                        });
                    }
                    
                    const offer = await pc.createOffer({
                        offerToReceiveAudio: true,
                        offerToReceiveVideo: true
                    });
                    
                    await pc.setLocalDescription(offer);
                    
                    this.socket.send(JSON.stringify({
                        type: 'offer',
                        offer: offer,
                        targetUserId: data.userId,
                        userId: this.userId,
                        meetingId: this.meetingId
                    }));
                } catch (error) {
                    console.error('Error creating offer:', error);
                }
            }

            // Dispatch event for teacher meeting UI
            document.dispatchEvent(new CustomEvent('student-joined', { detail: data }));
        }
    }

    async handleUserLeave(data) {
        console.log('User left:', data);
        
        // Clean up peer connection
        const pc = this.peerConnections.get(data.userId);
        if (pc) {
            pc.close();
            this.peerConnections.delete(data.userId);
        }

        // Remove video element
        const videoElement = document.querySelector(`[data-student-id="${data.userId}"]`);
        if (videoElement) {
            videoElement.remove();
        }

        // Dispatch event for teacher meeting UI
        document.dispatchEvent(new CustomEvent('student-left', { detail: data }));
    }

    handleFocusUpdate(data) {
        console.log('Focus update received:', data);
        
        // Store focus score
        this.focusScores.set(data.userId, parseFloat(data.focusScore));

        // Update student data if we're the teacher
        if (this.userRole === 'teacher') {
            let student = this.students.get(data.userId);
            
            // If student doesn't exist in the map (might be a rejoin case), add them
            if (!student && data.userName) {
                student = {
                    id: data.userId,
                    name: data.userName,
                    focusScore: 0,
                    focusTime: 0,
                    lastUpdate: new Date(),
                    isActive: true
                };
                this.students.set(data.userId, student);
            }

            if (student) {
                student.focusScore = parseFloat(data.focusScore);
                student.focusTime = data.focusTime || 0;
                student.lastUpdate = new Date();
                student.isActive = true;
                this.students.set(data.userId, student);
            }

            // Calculate focus distribution
            const totalStudents = this.students.size;
            const highFocus = Array.from(this.students.values()).filter(s => s.focusScore >= 70).length;
            const mediumFocus = Array.from(this.students.values()).filter(s => s.focusScore >= 40 && s.focusScore < 70).length;
            const lowFocus = Array.from(this.students.values()).filter(s => s.focusScore < 40).length;

            // Update focus distribution bars
            const highBar = document.getElementById('high-focus-bar');
            const mediumBar = document.getElementById('medium-focus-bar');
            const lowBar = document.getElementById('low-focus-bar');

            if (totalStudents > 0) {
                if (highBar) highBar.style.width = `${(highFocus / totalStudents) * 100}%`;
                if (mediumBar) mediumBar.style.width = `${(mediumFocus / totalStudents) * 100}%`;
                if (lowBar) lowBar.style.width = `${(lowFocus / totalStudents) * 100}%`;
            }

            // Calculate and update average focus
            const totalFocus = Array.from(this.students.values()).reduce((sum, s) => sum + s.focusScore, 0);
            const averageFocus = totalStudents > 0 ? totalFocus / totalStudents : 0;
            
            const averageFocusElement = document.getElementById('average-focus');
            if (averageFocusElement) {
                averageFocusElement.textContent = `${Math.round(averageFocus)}%`;
            }

            // Update active students count
            const activeStudentsElement = document.getElementById('active-students');
            if (activeStudentsElement) {
                const activeCount = Array.from(this.students.values()).filter(s => s.isActive).length;
                activeStudentsElement.textContent = `${activeCount}`;
            }

            // Update student list UI
            this.updateStudentListUI();
        }

        // Dispatch event for UI updates
        document.dispatchEvent(new CustomEvent('focus-update', { 
            detail: {
                userId: data.userId,
                focusScore: parseFloat(data.focusScore),
                focusTime: data.focusTime || 0,
                timestamp: new Date().toISOString()
            }
        }));
    }

    handleMeetingState(data) {
        console.log('Meeting state update:', data);
        
        // Update UI
        this.updateAnalytics(data);
        
        // Dispatch event for teacher meeting UI
        document.dispatchEvent(new CustomEvent('meeting-state-update', { detail: data }));
    }

    async createPeerConnection(userId) {
        try {
            console.log('Creating peer connection for user:', userId);
            const pc = new RTCPeerConnection({
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' }
                ]
            });

            // Store the peer connection
            this.peerConnections.set(userId, pc);

            // Track which tracks have been added to this peer connection
            pc.addedTracks = new Set();

            // Handle connection state changes
            pc.onconnectionstatechange = () => {
                console.log(`Connection state for ${userId}:`, pc.connectionState);
            };

            // Handle ICE connection state changes
            pc.oniceconnectionstatechange = () => {
                console.log(`ICE connection state for ${userId}:`, pc.iceConnectionState);
            };

            // Handle negotiation needed
            pc.onnegotiationneeded = async () => {
                console.log('Negotiation needed for:', userId);
                try {
                    // Add local tracks if not already added
                    if (this.localStream) {
                        this.localStream.getTracks().forEach(track => {
                            // Check if this track has already been added
                            if (!pc.addedTracks.has(track.id)) {
                                console.log('Adding new track to peer connection:', track.kind);
                                pc.addTrack(track, this.localStream);
                                pc.addedTracks.add(track.id);
                            } else {
                                console.log('Track already added:', track.kind);
                            }
                        });
                    }

                    // Create offer with consistent m-line order
                    const offer = await pc.createOffer({
                        offerToReceiveAudio: true,
                        offerToReceiveVideo: true
                    });
                    
                    // Set local description before sending
                    await pc.setLocalDescription(offer);
                    
                    this.socket.send(JSON.stringify({
                        type: 'offer',
                        offer: offer,
                        targetUserId: userId,
                        userId: this.userId,
                        meetingId: this.meetingId
                    }));
                } catch (error) {
                    console.error('Error handling negotiation:', error);
                }
            };

            // Handle incoming tracks
            pc.ontrack = (event) => {
                console.log('Received remote track:', event.track.kind);
                const stream = event.streams[0];
                
                // Find the video element within the student container
                const videoElement = document.querySelector(`[data-student-container="${userId}"] video`);
                if (videoElement) {
                    console.log('Setting stream to video element');
                    videoElement.srcObject = stream;
                    videoElement.play().catch(error => console.error('Error playing video:', error));
                } else {
                    console.error('Video element not found for user:', userId);
                }
            };

            // Handle ICE candidates
            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    console.log('Sending ICE candidate to:', userId);
                    this.socket.send(JSON.stringify({
                        type: 'ice_candidate',
                        candidate: event.candidate,
                        userId: this.userId,
                        targetUserId: userId,
                        meetingId: this.meetingId
                    }));
                }
            };

            return pc;
        } catch (error) {
            console.error('Error creating peer connection:', error);
            throw error;
        }
    }

    async handleOffer(data) {
        try {
            let pc = this.peerConnections.get(data.userId);
            if (!pc) {
                pc = await this.createPeerConnection(data.userId);
            }

            // Add local tracks if not already added
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => {
                    // Check if this track has already been added
                    if (!pc.addedTracks.has(track.id)) {
                        console.log('Adding new track to peer connection:', track.kind);
                        pc.addTrack(track, this.localStream);
                        pc.addedTracks.add(track.id);
                    } else {
                        console.log('Track already added:', track.kind);
                    }
                });
            }

            // Set remote description first
            await pc.setRemoteDescription(new RTCSessionDescription(data.offer));
            
            // Create answer with consistent m-line order
            const answer = await pc.createAnswer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: true
            });
            
            // Set local description before sending
            await pc.setLocalDescription(answer);

            this.socket.send(JSON.stringify({
                type: 'answer',
                answer: answer,
                userId: this.userId,
                targetUserId: data.userId,
                meetingId: this.meetingId
            }));
        } catch (error) {
            console.error('Error handling offer:', error);
        }
    }

    async handleAnswer(data) {
        try {
            const pc = this.peerConnections.get(data.userId);
            if (pc) {
                // Set remote description
                await pc.setRemoteDescription(new RTCSessionDescription(data.answer));
            }
        } catch (error) {
            console.error('Error handling answer:', error);
        }
    }

    async handleIceCandidate(data) {
        try {
            const pc = this.peerConnections.get(data.userId);
            if (pc) {
                await pc.addIceCandidate(new RTCIceCandidate(data.candidate));
            }
        } catch (error) {
            console.error('Error handling ICE candidate:', error);
        }
    }

    async initializeLocalVideo() {
        try {
            console.log('Initializing video...');
            const constraints = {
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: true
            };

            console.log('Requesting media access...');
            this.localStream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log('Got media stream:', this.localStream);

            const localVideo = document.getElementById('localVideo');
            if (localVideo) {
                console.log('Setting up video element...');
                localVideo.srcObject = this.localStream;
                localVideo.muted = true; // Always mute local video to prevent feedback
                
                await new Promise((resolve) => {
                    localVideo.onloadedmetadata = () => {
                        console.log('Video metadata loaded');
                        resolve();
                    };
                });
                await localVideo.play();
                console.log('Video playback started');

                // Enable audio only for teachers by default
                if (this.userRole === 'teacher') {
                    this.localStream.getAudioTracks().forEach(track => {
                        track.enabled = true;
                    });
                } else {
                    this.localStream.getAudioTracks().forEach(track => {
                        track.enabled = false;
                    });
                }

                this.updateMediaButtons();
            } else {
                console.error('Local video element not found');
            }
        } catch (error) {
            console.error('Media access error:', error);
            this.handleError(error);
        }
    }

    setupEventListeners() {
        console.log('Setting up event listeners...');
        
        // Video toggle
        const toggleVideo = document.getElementById('toggleVideo');
        if (toggleVideo) {
            toggleVideo.addEventListener('click', () => {
                console.log('Toggle video clicked');
                this.toggleVideo();
            });
        }

        // Audio toggle
        const toggleAudio = document.getElementById('toggleAudio');
        if (toggleAudio) {
            toggleAudio.addEventListener('click', () => {
                console.log('Toggle audio clicked');
                this.toggleAudio();
            });
        }

        // Screen share toggle
        const toggleScreen = document.getElementById('toggleScreen');
        if (toggleScreen) {
            toggleScreen.addEventListener('click', () => {
                console.log('Toggle screen share clicked');
                this.toggleScreenShare();
            });
        }

        // Chat form
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const sendMessage = document.getElementById('send-message');

        if (chatForm && chatInput && sendMessage) {
            // Prevent form submission
            chatForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });

            // Send message on Enter key
            chatInput.addEventListener('keypress', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    this.sendMessage();
                }
            });
        }

        // End meeting
        const endMeeting = document.getElementById('endMeeting');
        if (endMeeting) {
            endMeeting.addEventListener('click', () => this.endMeeting());
        }
    }

    toggleVideo() {
        console.log('Toggling video...');
        if (this.localStream) {
            const videoTrack = this.localStream.getVideoTracks()[0];
            if (videoTrack) {
                this.isVideoEnabled = !this.isVideoEnabled;
                videoTrack.enabled = this.isVideoEnabled;
                console.log('Video enabled:', this.isVideoEnabled);
                this.updateMediaButtons();
            }
        }
    }

    toggleAudio() {
        console.log('Toggling audio...');
        if (this.localStream) {
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                this.isAudioEnabled = !this.isAudioEnabled;
                audioTrack.enabled = this.isAudioEnabled;
                console.log('Audio enabled:', this.isAudioEnabled);
                this.updateMediaButtons();
            }
        }
    }

    async toggleScreenShare() {
        console.log('Toggle screen share clicked');
        console.log('Toggling screen share...');
        try {
            if (!this.isScreenSharing) {
                // Start screen sharing
                this.screenStream = await navigator.mediaDevices.getDisplayMedia({
                    video: {
                        cursor: "always"
                    },
                    audio: false
                });

                // Get the screen video element
                const screenVideo = document.getElementById('screenShareVideo');
                if (!screenVideo) {
                    // Create screen share video element if it doesn't exist
                    const videoContainer = document.getElementById('video-container');
                    if (videoContainer) {
                        const newScreenVideo = document.createElement('video');
                        newScreenVideo.id = 'screenShareVideo';
                        newScreenVideo.className = 'w-full h-full object-contain';
                        newScreenVideo.autoplay = true;
                        newScreenVideo.playsInline = true;
                        videoContainer.appendChild(newScreenVideo);
                    }
                }

                // Update the video source
                const screenVideoElement = document.getElementById('screenShareVideo');
                if (screenVideoElement) {
                    screenVideoElement.srcObject = this.screenStream;
                    screenVideoElement.style.display = 'block';
                }

                // Hide the local video while screen sharing
               

                // Listen for the end of screen sharing
                this.screenStream.getVideoTracks()[0].addEventListener('ended', () => {
                    this.stopScreenSharing();
                });

                this.isScreenSharing = true;

                // Notify other participants
                if (this.socket?.readyState === WebSocket.OPEN) {
                    this.socket.send(JSON.stringify({
                        type: 'screen_share',
                        meetingId: this.meetingId,
                        userId: this.userId,
                        action: 'start'
                    }));
                }
            } else {
                await this.stopScreenSharing();
            }

            this.updateMediaButtons();
        } catch (error) {
            console.error('Error toggling screen share:', error);
            this.handleError(error);
        }
    }

    updateMediaButtons() {
        console.log('Updating media buttons...');
        const videoButton = document.getElementById('toggleVideo');
        const audioButton = document.getElementById('toggleAudio');

        if (videoButton) {
            videoButton.innerHTML = `<i class="fas fa-video${this.isVideoEnabled ? '' : '-slash'} text-xl"></i>`;
            videoButton.className = `p-4 rounded-full ${this.isVideoEnabled ? 'bg-blue-600 hover:bg-blue-700' : 'bg-red-600 hover:bg-red-700'} text-white transition-colors`;
        }

        if (audioButton) {
            audioButton.innerHTML = `<i class="fas fa-microphone${this.isAudioEnabled ? '' : '-slash'} text-xl"></i>`;
            audioButton.className = `p-4 rounded-full ${this.isAudioEnabled ? 'bg-blue-600 hover:bg-blue-700' : 'bg-red-600 hover:bg-red-700'} text-white transition-colors`;
        }
    }

    handleMediaError(error) {
        console.error('Media error:', error);
        let message = 'An error occurred while accessing media devices.';
        
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            message = 'Camera/Microphone access was denied. Please allow access to join the meeting.';
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            message = 'No camera or microphone found. Please connect a device and try again.';
        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
            message = 'Your camera or microphone is already in use by another application.';
        }

        alert(message);

        const focusStatus = document.getElementById('focus-status');
        if (focusStatus) {
            focusStatus.textContent = message;
            focusStatus.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-500 text-white transition-colors duration-300';
        }
    }

    async endMeeting() {
        try {
            // Calculate final analytics if we're a student
            if (this.userRole === 'student' && this.focusHistory?.length > 0) {
                const averageFocus = this.focusHistory.reduce((sum, entry) => sum + entry.score, 0) / this.focusHistory.length;
                
                // Send final analytics to server
                await fetch(`/api/meetings/${this.meetingId}/analytics`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        userId: this.userId,
                        meetingId: this.meetingId,
                        averageFocus: averageFocus,
                        focusHistory: this.focusHistory
                    })
                });
            }

            // Stop all tracks
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => track.stop());
            }

            // Clear intervals
            if (this.focusTrackingInterval) {
                clearInterval(this.focusTrackingInterval);
            }

            // Close all peer connections
            this.peerConnections.forEach(pc => pc.close());
            this.peerConnections.clear();

            // Close WebSocket connection
            if (this.socket) {
                this.socket.close();
            }

            // Redirect to appropriate page
            window.location.href = this.userRole === 'teacher' ? 
                `/meetings/${this.meetingId}/summary` : '/meetings';
        } catch (error) {
            console.error('Error ending meeting:', error);
            alert('There was an error ending the meeting. Please try again.');
        }
    }

    startFocusTracking() {
        if (this.userRole !== 'student') return;
        
        if (this.focusTrackingInterval) {
            clearInterval(this.focusTrackingInterval);
        }

        // Create focus tracking UI if it doesn't exist
        this.createFocusTrackingUI();

        this.focusTrackingInterval = setInterval(async () => {
            try {
                // Only capture and send focus data every 5 seconds
                const now = Date.now();
                if (now - this.lastFocusUpdate < this.focusUpdateInterval) {
                    return;
                }
                
                this.lastFocusUpdate = now;
                
                const frame = await this.captureVideoFrame();
                if (frame) {
                    const formData = new FormData();
                    formData.append('frame', frame);
                    formData.append('userId', this.userId);
                    formData.append('meetingId', this.meetingId);

                    const response = await fetch('http://127.0.0.1:5000/analyze-frame', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error('Failed to process focus data');
                    }

                    const data = await response.json();
                    if (data.focusScore !== undefined) {
                        // Update local UI
                        this.updateFocusUI(data.focusScore);
                        
                        // Calculate focus time
                        const focusTime = this.focusHistory ? 
                            this.focusHistory.filter(entry => entry.score >= 40).length : 0;
                        
                        // Send focus update to WebSocket server
                        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                            console.log('Sending focus update:', {
                                focusScore: data.focusScore,
                                focusTime: focusTime
                            });
                            
                            this.socket.send(JSON.stringify({
                                type: 'focus_update',
                                meetingId: this.meetingId,
                                userId: this.userId,
                                userName: this.userName,
                                focusScore: data.focusScore,
                                focusTime: focusTime,
                                timestamp: new Date().toISOString()
                            }));
                        }

                        // Store focus score in history
                        if (!this.focusHistory) {
                            this.focusHistory = [];
                        }
                        this.focusHistory.push({
                            score: data.focusScore,
                            timestamp: new Date().toISOString()
                        });

                        // Keep only last 5 minutes of history
                        const fiveMinutesAgo = new Date(Date.now() - 5 * 60 * 1000);
                        this.focusHistory = this.focusHistory.filter(entry => 
                            new Date(entry.timestamp) > fiveMinutesAgo
                        );

                        // Update local focus scores map
                        this.focusScores.set(this.userId, data.focusScore);
                        
                        // Dispatch local event for UI updates
                        document.dispatchEvent(new CustomEvent('focus-update', {
                            detail: {
                                userId: this.userId,
                                focusScore: data.focusScore,
                                focusTime: focusTime,
                                timestamp: new Date().toISOString()
                            }
                        }));
                    }
                }
            } catch (error) {
                console.error('Error in focus tracking:', error);
                const focusStatus = document.getElementById('focus-status');
                if (focusStatus) {
                    focusStatus.textContent = 'Focus tracking error';
                    focusStatus.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-500 text-white transition-colors duration-300';
                }
            }
        }, 1000); // Check every second
    }

    createFocusTrackingUI() {
        if (this.userRole !== 'student') return;

        let focusContainer = document.getElementById('focus-tracking');
        if (!focusContainer) {
            focusContainer = document.createElement('div');
            focusContainer.id = 'focus-tracking';
            focusContainer.className = 'fixed bottom-20 left-4 right-4 bg-gray-800 p-4 rounded-lg shadow-lg z-50 max-w-md mx-auto';
            focusContainer.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-lg font-semibold text-white">Focus Level</h2>
                    <span id="focus-value" class="text-sm font-medium text-white">0%</span>
                </div>
                <div class="bg-gray-700 rounded-full h-2">
                    <div id="focus-bar" class="h-2 rounded-full bg-red-500 transition-all duration-300" style="width: 0%"></div>
                </div>
                <div id="focus-status" class="text-sm text-gray-400 mt-2 text-center"></div>
            `;
            document.body.appendChild(focusContainer);
        }
    }

    updateFocusUI(focusScore) {
        const focusBar = document.getElementById('focus-bar');
        const focusValue = document.getElementById('focus-value');
        const focusStatus = document.getElementById('focus-status');
        
        if (focusBar && focusValue) {
            // Round the focus score
            const roundedScore = Math.round(focusScore);
            
            // Animate the focus bar
            focusBar.style.transition = 'all 0.5s ease-in-out';
            focusBar.style.width = `${roundedScore}%`;
            focusValue.textContent = `${roundedScore}%`;
            
            // Update focus bar color and status message with animation
            let statusMessage = '';
            let barColor = '';
            let valueColor = '';
            
            if (roundedScore >= 70) {
                barColor = 'bg-green-500';
                valueColor = 'bg-green-900 text-green-100';
                statusMessage = 'Excellent focus!';
            } else if (roundedScore >= 40) {
                barColor = 'bg-yellow-500';
                valueColor = 'bg-yellow-900 text-yellow-100';
                statusMessage = 'Moderate focus';
            } else {
                barColor = 'bg-red-500';
                valueColor = 'bg-red-900 text-red-100';
                statusMessage = 'Low focus - please pay attention';
            }
            
            focusBar.className = `h-2 rounded-full ${barColor} transition-all duration-300 transform`;
            focusValue.className = `text-sm font-medium px-2 py-1 rounded ${valueColor} transition-all duration-300`;
            
            if (focusStatus) {
                focusStatus.textContent = statusMessage;
                focusStatus.className = 'text-sm mt-2 text-center transition-all duration-300 ' + 
                    (roundedScore >= 70 ? 'text-green-400' : 
                     roundedScore >= 40 ? 'text-yellow-400' : 'text-red-400');
            }
        }
    }

    async captureVideoFrame() {
        const video = document.getElementById('localVideo');
        if (!video || !video.srcObject) return null;

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        return new Promise((resolve) => {
            canvas.toBlob((blob) => {
                resolve(blob);
            }, 'image/jpeg', 0.8);
        });
    }

    async stopScreenSharing() {
        console.log('Stopping screen sharing...');
        try {
            if (this.screenStream) {
                this.screenStream.getTracks().forEach(track => track.stop());
            }

            // Show the local video again
            const localVideo = document.getElementById('localVideo');
            if (localVideo) {
                localVideo.style.display = 'block';
            }

            // Hide the screen share video
            const screenVideo = document.getElementById('screenShareVideo');
            if (screenVideo) {
                screenVideo.style.display = 'none';
            }

            // Notify other participants
            if (this.socket?.readyState === WebSocket.OPEN) {
                this.socket.send(JSON.stringify({
                    type: 'screen_share',
                    meetingId: this.meetingId,
                    userId: this.userId,
                    action: 'stop'
                }));
            }

            this.isScreenSharing = false;
            this.screenStream = null;

            this.updateMediaButtons();
        } catch (error) {
            console.error('Error stopping screen share:', error);
            this.handleError(error);
        }
    }

    handleError(error) {
        console.error('Error:', error);
        let message = 'An error occurred. Please try again later.';
        
        if (error instanceof DOMException) {
            message = error.message;
        }

        alert(message);

        const focusStatus = document.getElementById('focus-status');
        if (focusStatus) {
            focusStatus.textContent = message;
            focusStatus.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-500 text-white transition-colors duration-300';
        }
    }

    async handleScreenShare(data) {
        try {
            if (data.userId !== this.userId) {
                const mainContainer = document.getElementById('video-container');
                let screenVideo = document.getElementById('screenShareVideo');
                
                if (data.action === 'start') {
                    // Create screen share container if it doesn't exist
                    if (!screenVideo) {
                        const screenContainer = document.createElement('div');
                        screenContainer.id = 'screen-container';
                        screenContainer.className = 'absolute inset-0 bg-black z-10';
                        
                        screenVideo = document.createElement('video');
                        screenVideo.id = 'screenShareVideo';
                        screenVideo.className = 'w-full h-full object-contain';
                        screenVideo.autoplay = true;
                        screenVideo.playsInline = true;
                        
                        screenContainer.appendChild(screenVideo);
                        mainContainer.appendChild(screenContainer);
                    }
                    
                    // Create new peer connection for screen sharing
                    let pc = this.peerConnections.get(`${data.userId}-screen`);
                    if (!pc) {
                        pc = await this.createPeerConnection(`${data.userId}-screen`);
                    }
                    
                    // Show the screen container
                    screenVideo.parentElement.style.display = 'block';
                } else if (data.action === 'stop') {
                    // Hide the screen container
                    if (screenVideo) {
                        screenVideo.parentElement.style.display = 'none';
                    }
                    
                    // Clean up screen sharing peer connection
                    const pc = this.peerConnections.get(`${data.userId}-screen`);
                    if (pc) {
                        pc.close();
                        this.peerConnections.delete(`${data.userId}-screen`);
                    }
                }
            }
        } catch (error) {
            console.error('Error handling remote screen share:', error);
        }
    }

    updateStudentListUI() {
        if (this.userRole !== 'teacher') return;

        const studentListContainer = document.getElementById('student-list-container');
        if (!studentListContainer) return;

        // Update student count
        const studentCount = document.getElementById('student-count');
        if (studentCount) {
            const activeCount = Array.from(this.students.values()).filter(s => s.isActive).length;
            studentCount.textContent = `${activeCount}`;
        }

        // Get or create student list
        let studentList = studentListContainer.querySelector('.student-list');
        if (!studentList) {
            studentList = document.createElement('div');
            studentList.className = 'student-list divide-y divide-gray-700';
            studentListContainer.appendChild(studentList);
        }

        // Update each student card
        this.students.forEach((student, userId) => {
            let studentCard = document.querySelector(`[data-student-id="${userId}"]`);
            
            if (!studentCard) {
                studentCard = document.createElement('div');
                studentCard.setAttribute('data-student-id', userId);
                studentList.appendChild(studentCard);
            }

            // Calculate time in meeting
            const timeInMeeting = Math.floor((Date.now() - (student.rejoinTime || student.joinTime).getTime()) / 1000);
            const minutes = Math.floor(timeInMeeting / 60);
            const seconds = timeInMeeting % 60;

            const focusLevelClass = student.focusScore >= 70 ? 'bg-green-500/50' :
                                  student.focusScore >= 40 ? 'bg-yellow-500/50' :
                                  'bg-red-500/50';

            studentCard.className = `p-4 hover:bg-gray-700/50 transition-colors duration-200 ${student.isActive ? '' : 'opacity-50'}`;
            studentCard.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full ${focusLevelClass} flex items-center justify-center">
                            <span class="text-lg font-semibold text-white">${student.name.charAt(0).toUpperCase()}</span>
                        </div>
                        <div>
                            <div class="flex items-center">
                                <span class="font-medium text-white">${student.name}</span>
                                ${student.rejoinTime ? '<span class="ml-2 text-xs bg-blue-500/50 text-white px-2 py-0.5 rounded">Rejoined</span>' : ''}
                            </div>
                            <div class="text-sm text-gray-400">
                                ${minutes}:${seconds.toString().padStart(2, '0')} in meeting
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <div class="text-sm font-medium mb-1 ${
                            student.focusScore >= 70 ? 'text-green-400' :
                            student.focusScore >= 40 ? 'text-yellow-400' :
                            'text-red-400'
                        }">
                            ${Math.round(student.focusScore)}%
                        </div>
                        <div class="w-16 h-1 rounded-full bg-gray-600">
                            <div class="h-1 rounded-full ${focusLevelClass} transition-all duration-300"
                                 style="width: ${student.focusScore}%"></div>
                        </div>
                    </div>
                </div>
            `;
        });

        // Remove cards of students who left
        const existingCards = studentList.querySelectorAll('[data-student-id]');
        existingCards.forEach(card => {
            const cardId = card.getAttribute('data-student-id');
            if (!this.students.has(cardId)) {
                card.remove();
            }
        });
    }

    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-fade-in-out';
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    createUIContainers() {
        // Create main container with a modern gradient background
        let mainContainer = document.querySelector('main');
        if (!mainContainer) {
            mainContainer = document.createElement('main');
            mainContainer.className = 'flex h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900';
            document.body.appendChild(mainContainer);
        }

        // Create content wrapper with glass morphism effect
        let contentWrapper = document.createElement('div');
        contentWrapper.className = 'flex flex-1 h-full relative backdrop-blur-lg bg-opacity-75';
        mainContainer.appendChild(contentWrapper);

        // Create video container with improved layout
        let videoContainer = document.getElementById('video-container');
        if (!videoContainer) {
            videoContainer = document.createElement('div');
            videoContainer.id = 'video-container';
            videoContainer.className = 'relative flex-1 flex flex-col bg-gray-900 bg-opacity-50';
            contentWrapper.appendChild(videoContainer);

            // Create video grid container with better spacing
            const gridContainer = document.createElement('div');
            gridContainer.id = 'video-grid';
            gridContainer.className = 'flex-1 p-6 relative';
            videoContainer.appendChild(gridContainer);

            // Create modern controls bar
            const controlsBar = document.createElement('div');
            controlsBar.className = 'bg-gray-800 bg-opacity-90 backdrop-blur-md p-4 flex items-center justify-center space-x-6 border-t border-gray-700 shadow-lg';
           
            videoContainer.appendChild(controlsBar);
        }

        // Create remote videos container with improved grid layout
        let remoteContainer = document.getElementById('remote-videos');
        if (!remoteContainer) {
            remoteContainer = document.createElement('div');
            remoteContainer.id = 'remote-videos';
            if (this.userRole === 'teacher') {
                remoteContainer.className = 'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 p-4';
            } else {
                remoteContainer.className = 'flex justify-center items-center h-full';
            }
            document.getElementById('video-grid').appendChild(remoteContainer);
        }

        // Create enhanced local video container
        let localVideoContainer = document.getElementById('local-video-container');
        if (!localVideoContainer) {
            localVideoContainer = document.createElement('div');
            localVideoContainer.id = 'local-video-container';
            localVideoContainer.className = 'absolute bottom-24 right-6 w-72 aspect-video rounded-xl overflow-hidden shadow-2xl transition-all duration-300 transform hover:scale-105 hover:shadow-3xl border border-gray-700 bg-gray-800';
            
            const localVideo = document.createElement('video');
            localVideo.id = 'localVideo';
            localVideo.autoplay = true;
            localVideo.playsInline = true;
            localVideo.muted = true;
            localVideo.className = 'w-full h-full object-cover';
            
            const nameLabel = document.createElement('div');
            nameLabel.className = 'absolute bottom-3 left-3 bg-black bg-opacity-75 text-white px-3 py-2 rounded-lg backdrop-blur-sm flex items-center space-x-2 text-sm';
            nameLabel.innerHTML = `
                <i class="fas fa-user-circle"></i>
                <span class="font-medium">${this.userName} (You)</span>
            `;
            
            localVideoContainer.appendChild(localVideo);
            localVideoContainer.appendChild(nameLabel);
            videoContainer.appendChild(localVideoContainer);
        }

        // Create enhanced teacher sidebar with analytics and student list
        if (this.userRole === 'teacher') {
            let sidebar = document.getElementById('teacher-sidebar');
            if (!sidebar) {
                sidebar = document.createElement('div');
                sidebar.id = 'teacher-sidebar';
                sidebar.className = 'w-96 bg-gray-800 bg-opacity-95 backdrop-blur-md flex flex-col h-full border-l border-gray-700 shadow-xl';
                contentWrapper.appendChild(sidebar);

                // Create analytics section with improved visuals
                const analyticsSection = document.createElement('div');
                analyticsSection.className = 'p-6 border-b border-gray-700';
                analyticsSection.innerHTML = `
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-white">Meeting Analytics</h2>
                        <span class="px-2 py-1 rounded-full bg-blue-500 text-xs text-white">Live</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-700 rounded-lg p-4 transition-all duration-300 hover:bg-gray-600">
                            <div class="text-sm text-gray-400 mb-1">Average Focus</div>
                            <div id="average-focus" class="text-2xl font-bold text-white">0%</div>
                        </div>
                        <div class="bg-gray-700 rounded-lg p-4 transition-all duration-300 hover:bg-gray-600">
                            <div class="text-sm text-gray-400 mb-1">Active Students</div>
                            <div id="active-students" class="text-2xl font-bold text-white">0</div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">High Focus (≥70%)</span>
                            <div class="w-48 bg-gray-700 rounded-full h-2">
                                <div id="high-focus-bar" class="bg-green-500 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Medium Focus (40-69%)</span>
                            <div class="w-48 bg-gray-700 rounded-full h-2">
                                <div id="medium-focus-bar" class="bg-yellow-500 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Low Focus (<40%)</span>
                            <div class="w-48 bg-gray-700 rounded-full h-2">
                                <div id="low-focus-bar" class="bg-red-500 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                `;
                sidebar.appendChild(analyticsSection);

                // Create student list container with header
                const studentListContainer = document.createElement('div');
                studentListContainer.id = 'student-list-container';
                studentListContainer.className = 'flex-1 overflow-y-auto';
                
                const studentListHeader = document.createElement('div');
                studentListHeader.className = 'sticky top-0 px-6 py-4 bg-gray-800 border-b border-gray-700 z-10';
                studentListHeader.innerHTML = `
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white">Students</h3>
                        <span id="student-count" class="px-2 py-1 bg-gray-700 rounded-full text-xs text-white">0</span>
                    </div>
                `;
                
                studentListContainer.appendChild(studentListHeader);
                sidebar.appendChild(studentListContainer);
            }
        }

        // Create enhanced focus tracking UI for students
        if (this.userRole === 'student') {
            let focusContainer = document.getElementById('focus-tracking');
            if (!focusContainer) {
                focusContainer = document.createElement('div');
                focusContainer.id = 'focus-tracking';
                focusContainer.className = 'fixed bottom-24 left-1/2 transform -translate-x-1/2 bg-gray-800 p-6 rounded-xl shadow-lg z-50 w-96 backdrop-blur-md bg-opacity-90 border border-gray-700';
                focusContainer.innerHTML = `
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Focus Level</h2>
                        <div id="focus-value" class="px-3 py-1 rounded-full bg-gray-700 text-white text-sm font-medium">0%</div>
                    </div>
                    <div class="bg-gray-700 rounded-full h-2 mb-3">
                        <div id="focus-bar" class="h-2 rounded-full bg-red-500 transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <div id="focus-status" class="text-sm text-center text-gray-400"></div>
                `;
                document.body.appendChild(focusContainer);
            }
        }
    }

    handleMeetingStateRequest(data) {
        // Only students respond to meeting state requests
        if (this.userRole === 'student') {
            console.log('Received meeting state request, sending current state');
            
            // Send current student state
            this.socket.send(JSON.stringify({
                type: 'student_state_update',
                meetingId: this.meetingId,
                userId: this.userId,
                userName: this.userName,
                userRole: this.userRole,
                focusScore: this.focusScores.get(this.userId) || 0,
                focusTime: this.focusHistory ? this.focusHistory.length * 5 : 0,
                isActive: true,
                joinTime: new Date().toISOString()
            }));

            // Simulate student rejoining to establish peer connection with teacher
            setTimeout(() => {
                if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                    console.log('Simulating rejoin for student after teacher joined...');
                    this.socket.send(JSON.stringify({
                        type: 'join',
                        meetingId: this.meetingId,
                        userId: this.userId,
                        userName: this.userName,
                        userRole: this.userRole
                    }));
                }
            }, 1000); // Delay slightly to allow initial state to be processed
        }
    }

    handleStudentStateUpdate(data) {
        // Only teachers process student state updates
        if (this.userRole === 'teacher') {
            console.log('Received student state update:', data);
            
            let student = this.students.get(data.userId);
            const isNewStudent = !student;

            if (!student) {
                student = {
                    id: data.userId,
                    name: data.userName,
                    focusScore: 0,
                    focusTime: 0,
                    lastUpdate: new Date(),
                    isActive: true,
                    joinTime: new Date(data.joinTime)
                };
            }

            // Update student data
            student.focusScore = parseFloat(data.focusScore);
            student.focusTime = data.focusTime;
            student.lastUpdate = new Date();
            student.isActive = data.isActive;
            
            this.students.set(data.userId, student);
            this.focusScores.set(data.userId, parseFloat(data.focusScore));

            // Update UI
            this.updateStudentListUI();
            
            // If this is a new student, show notification
            if (isNewStudent) {
                this.showNotification(`${student.name} is in the meeting`);
            }

            // Update analytics
            this.updateAnalytics({
                averageFocus: Array.from(this.focusScores.values()).reduce((a, b) => a + b, 0) / this.focusScores.size,
                activeStudents: Array.from(this.students.values()).filter(s => s.isActive).length,
                totalStudents: this.students.size
            });
        }
    }

    updateAnalytics(data) {
        if (this.userRole !== 'teacher') return;

        const totalStudents = this.students.size;
        const highFocus = Array.from(this.students.values()).filter(s => s.focusScore >= 70).length;
        const mediumFocus = Array.from(this.students.values()).filter(s => s.focusScore >= 40 && s.focusScore < 70).length;
        const lowFocus = Array.from(this.students.values()).filter(s => s.focusScore < 40).length;

        // Update focus distribution bars
        const highBar = document.getElementById('high-focus-bar');
        const mediumBar = document.getElementById('medium-focus-bar');
        const lowBar = document.getElementById('low-focus-bar');

        if (totalStudents > 0) {
            if (highBar) highBar.style.width = `${(highFocus / totalStudents) * 100}%`;
            if (mediumBar) mediumBar.style.width = `${(mediumFocus / totalStudents) * 100}%`;
            if (lowBar) lowBar.style.width = `${(lowFocus / totalStudents) * 100}%`;
        }

        // Update average focus and active students
        const averageFocusElement = document.getElementById('average-focus');
        if (averageFocusElement) {
            const avgFocus = Array.from(this.students.values())
                .reduce((sum, student) => sum + student.focusScore, 0) / totalStudents;
            averageFocusElement.textContent = `${Math.round(avgFocus)}%`;
        }

        const activeStudentsElement = document.getElementById('active-students');
        if (activeStudentsElement) {
            const activeCount = Array.from(this.students.values()).filter(s => s.isActive).length;
            activeStudentsElement.textContent = `${activeCount}`;
        }
    }
}

// Initialize when the page loads
document.addEventListener('DOMContentLoaded', async () => {
    console.log('DOM loaded, initializing meeting room...');
    try {
        const meetingRoom = new MeetingRoom();
        await meetingRoom.initialize();
    } catch (error) {
        console.error('Failed to initialize meeting room:', error);
    }
}); 
