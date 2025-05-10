import http from 'http';
import express from 'express';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { WebSocketServer } from 'ws';
import cors from 'cors';
import { WebSocket } from 'ws';
import { v4 as uuidv4 } from 'uuid';

// Get directory name in ES module
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Environment variables
const PORT = process.env.PORT || 6001;
const HOST = process.env.HOST || '127.0.0.1';

// Create log directory if it doesn't exist
const logDir = path.join(__dirname, 'logs');
if (!fs.existsSync(logDir)) {
    fs.mkdirSync(logDir);
}

// Create log file stream
const logStream = fs.createWriteStream(path.join(logDir, `socket-server-${new Date().toISOString().split('T')[0]}.log`), { flags: 'a' });

// Custom logging function
function log(message, data = null) {
    const timestamp = new Date().toISOString();
    let logMessage = `[${timestamp}] ${message}`;
    
    if (data) {
        if (typeof data === 'object') {
            logMessage += '\n' + JSON.stringify(data, null, 2);
        } else {
            logMessage += ' ' + data;
        }
    }
    
    console.log(logMessage);
    logStream.write(logMessage + '\n');
}

// Handle uncaught exceptions
process.on('uncaughtException', (error) => {
    log(`Uncaught Exception: ${error.message}`, 'error');
    log(error.stack, 'error');
});

// Handle unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
    log(`Unhandled Rejection at: ${promise}, reason: ${reason}`, 'error');
});

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const wss = new WebSocketServer({ server });

// Store active meetings and participants
const meetings = new Map();
const participants = new Map();
const focusData = new Map();

// Track active meetings and participants
const activeMeetings = new Map();
const activeParticipants = new Map();

// Function to check if a WebSocket is alive
function heartbeat() {
    this.isAlive = true;
}

// Function to terminate inactive connections
const terminateInactiveConnections = () => {
    wss.clients.forEach((ws) => {
        if (ws.isAlive === false) {
            console.log('Terminating inactive connection');
            return ws.terminate();
        }
        ws.isAlive = false;
        ws.ping();
    });
};

// Set up ping interval
const pingInterval = setInterval(terminateInactiveConnections, 30000);

wss.on('close', function close() {
    clearInterval(pingInterval);
});

wss.on('connection', function connection(ws, req) {
    console.log('New WebSocket connection established');
    
    // Mark the connection as alive
    ws.isAlive = true;
    
    // Handle pong messages
    ws.on('pong', heartbeat);
    
    // Handle ping messages from client
    ws.on('ping', () => {
        ws.pong();
    });

    // Handle regular messages
    ws.on('message', async function incoming(message) {
        try {
            // Handle ping message from client
            if (message.toString() === 'ping') {
                ws.send('pong');
                return;
            }

            const data = JSON.parse(message);
            await handleMessage(ws, data);
        } catch (error) {
            console.error('Error handling message:', error);
            ws.send(JSON.stringify({
                type: 'error',
                error: 'Invalid message format'
            }));
        }
    });

    // Handle client disconnection
    ws.on('close', function close(code, reason) {
        console.log(`Client disconnected. Code: ${code}, Reason: ${reason || 'No reason provided'}`);
        handleDisconnect(ws);
    });

    // Handle errors
    ws.on('error', function error(err) {
        console.error('WebSocket error:', err);
    });
});

// Add a health check endpoint
app.get('/health', (req, res) => {
    res.status(200).json({ status: 'OK', uptime: process.uptime() });
});

// Add an endpoint for broadcasting focus data updates
app.post('/broadcast-focus', express.json(), (req, res) => {
    try {
        const { meetingId, studentId, focusScore, userName, timestamp } = req.body;
        
        if (!meetingId || !studentId || focusScore === undefined) {
            return res.status(400).json({
                status: 'error',
                message: 'Missing required fields'
            });
        }
        
        log(`Broadcasting focus data: Student ${studentId} in meeting ${meetingId}: ${focusScore}%`);
        
        // Broadcast to all teachers in the meeting
        const meetingParticipants = meetings.get(meetingId);
        if (meetingParticipants) {
            const message = JSON.stringify({
                type: 'focus_update',
                userId: studentId,
            userName: userName || 'Unknown Student',
            focusScore,
                timestamp: timestamp || new Date().toISOString()
            });

            meetingParticipants.forEach((client) => {
                const participant = participants.get(client);
                if (participant && participant.userRole === 'teacher' && client.readyState === WebSocket.OPEN) {
                    client.send(message);
                }
            });
        }
        
        return res.status(200).json({
            status: 'success',
            message: 'Focus data broadcasted successfully'
        });
    } catch (error) {
        log(`Error broadcasting focus data: ${error.message}`, 'error');
        return res.status(500).json({
            status: 'error',
            message: `Error broadcasting focus data: ${error.message}`
        });
    }
});

log('Starting WebSocket server...');

function handleMessage(ws, data) {
    try {
        // Parse the message if it's a string
        if (typeof data === 'string') {
            data = JSON.parse(data);
        }
        
        // Get message type and normalize to lowercase
        const messageType = (data.TYPE || data.type || '').toLowerCase();
        
        // Enhanced logging
        log(`[MESSAGE] Type: ${messageType}`, {
            from: participants.get(ws)?.userName || 'Unknown',
            data: data
        });
        
        if (!messageType) {
            log('[WARN] Message has no type field');
            ws.send(JSON.stringify({
                type: 'error',
                error: 'Message type is required'
            }));
            return;
        }
        
        // Handle different message types
        switch (messageType) {
            case 'join':
                handleJoinMeeting(ws, data);
                break;
            case 'request_meeting_state':
                handleMeetingStateRequest(ws, data);
                break;
            case 'focus_update':
            case 'student_state':
                handleStudentStateUpdate(ws, data);
                break;
            default:
                log('[WARN] Unknown message type: ' + messageType);
                ws.send(JSON.stringify({
                    type: 'error',
                    error: `Unknown message type: ${messageType}`
                }));
        }

        // Log current state after handling message
        logCurrentState();
    } catch (error) {
        log('[ERROR] Error handling message: ' + error.message);
        console.error(error);
        ws.send(JSON.stringify({
            type: 'error',
            error: 'Error processing message'
        }));
    }
}

function simulateStudentData(ws, meetingId) {
    const participant = participants.get(ws);
    if (!participant || participant.userRole !== 'student') return;

    // Simulate different focus scores and error conditions
    const scenarios = [
        {
            type: 'student_state',
            focusScore: 75,
            isActive: true,
            error: null,
            connectionQuality: 'good'
        },
        {
            type: 'student_state',
            focusScore: 30,
            isActive: true,
            error: 'camera_disconnected',
            connectionQuality: 'poor'
        },
        {
            type: 'student_state',
            focusScore: 0,
            isActive: false,
            error: 'connection_lost',
            connectionQuality: 'none'
        },
        {
            type: 'student_state',
            focusScore: 45,
            isActive: true,
            error: 'audio_issues',
            connectionQuality: 'fair'
        }
    ];

    let scenarioIndex = 0;
    const intervalId = setInterval(() => {
        if (!meetings.has(meetingId) || !meetings.get(meetingId).has(ws)) {
            clearInterval(intervalId);
            return;
        }

        const scenario = scenarios[scenarioIndex];
        const studentState = {
            type: 'student_state',
            userId: participant.userId,
            userName: participant.userName,
            focusScore: scenario.focusScore,
            isActive: scenario.isActive,
            error: scenario.error,
            connectionQuality: scenario.connectionQuality,
            timestamp: new Date().toISOString()
        };

        // Log the simulated data
        log(`[SIMULATION] Sending student state for ${participant.userName}:`, studentState);

        // Send to all teachers in the meeting
        const meetingParticipants = meetings.get(meetingId);
        meetingParticipants.forEach(client => {
            const p = participants.get(client);
            if (p && p.userRole === 'teacher' && client.readyState === WebSocket.OPEN) {
                client.send(JSON.stringify(studentState));
            }
        });

        // Update focus data
        if (!focusData.has(meetingId)) {
            focusData.set(meetingId, new Map());
        }
        focusData.get(meetingId).set(participant.userId, scenario.focusScore);

        // Move to next scenario
        scenarioIndex = (scenarioIndex + 1) % scenarios.length;
    }, 5000); // Send updates every 5 seconds
}

// Modify handleJoinMeeting to start simulation for students
function handleJoinMeeting(ws, data) {
    const { meetingId, userId, userName, userRole } = data;
    
    log('[JOIN]', {
        user: `${userName} (${userId})`,
        meeting: meetingId,
        role: userRole
    });
    
    // Store participant data
    participants.set(ws, {
        meetingId,
        userId,
        userName,
        userRole,
        joinTime: new Date()
    });
    
    // Initialize meeting if it doesn't exist
    if (!meetings.has(meetingId)) {
        log('[NEW_MEETING] Creating meeting ' + meetingId);
        meetings.set(meetingId, new Set());
        focusData.set(meetingId, new Map());
    }
    
    // Add participant to meeting
    meetings.get(meetingId).add(ws);
    
    // Send join confirmation
    ws.send(JSON.stringify({
        type: 'join_confirmed',
        meetingId,
        userId,
        userName,
        userRole
    }));

    // Start simulation if participant is a student
    if (userRole === 'student') {
        simulateStudentData(ws, meetingId);
    }

    // Send current meeting state to the joining user
    const meetingParticipants = meetings.get(meetingId);
    const currentParticipants = [];
    meetingParticipants.forEach(client => {
        if (client !== ws) {
            const participant = participants.get(client);
            if (participant) {
                currentParticipants.push({
                    userId: participant.userId,
                    userName: participant.userName,
                    userRole: participant.userRole
                });
            }
        }
    });

    const meetingState = {
        type: 'meeting_state',
        meetingId,
        participants: currentParticipants,
        students: {}
    };

    // Add student data
    meetingParticipants.forEach(client => {
        const p = participants.get(client);
        if (p && p.userRole === 'student') {
            meetingState.students[p.userId] = {
                name: p.userName,
                focusScore: focusData.get(meetingId)?.get(p.userId) || 0,
                isActive: client.readyState === WebSocket.OPEN
            };
        }
    });

    log('[MEETING_STATE] Sending state to new participant', meetingState);
    ws.send(JSON.stringify(meetingState));
    
    // Broadcast user joined to other participants with lowercase type
    broadcastToMeeting(meetingId, {
        type: 'user_joined',
        meetingId,
        userId,
        userName,
        userRole
    }, ws);
}

function handleMeetingStateRequest(ws, data) {
    const participant = participants.get(ws);
    if (!participant) {
        log('Participant not found for meeting state request', 'warn');
        return;
    }
    
    const { meetingId } = participant;
    const meetingParticipants = meetings.get(meetingId);

        if (!meetingParticipants) {
        log(`Meeting ${meetingId} not found`, 'warn');
            return;
        }

    // Build meeting state with all participants
    const currentParticipants = [];
    const students = {};
    
    meetingParticipants.forEach(client => {
        const p = participants.get(client);
        if (p) {
            currentParticipants.push({
                userId: p.userId,
                userName: p.userName,
                userRole: p.userRole
            });
            
            if (p.userRole === 'student') {
                const focus = focusData.get(meetingId)?.get(p.userId) || 0;
                students[p.userId] = {
                    name: p.userName,
                    focusScore: focus,
                    isActive: client.readyState === WebSocket.OPEN
                };
            }
        }
    });
    
    // Send meeting state
    ws.send(JSON.stringify({
            type: 'meeting_state',
            meetingId,
        participants: currentParticipants,
        students
    }));
    
    log(`Sent meeting state for meeting ${meetingId}`);
}

function handleFocusUpdate(ws, data) {
    const participant = participants.get(ws);
    if (!participant) {
        log('Participant not found for focus update', 'warn');
        return;
    }

    const { meetingId, userId, userName } = participant;
    const focusScore = parseFloat(data.focusScore || 0);
    
    // Update focus data
    if (!focusData.has(meetingId)) {
        focusData.set(meetingId, new Map());
    }
    focusData.get(meetingId).set(userId, focusScore);
    
    // Get additional status data
    const deviceStatus = data.deviceStatus || {};
    const isActive = data.isActive !== undefined ? data.isActive : true;
    
    // Broadcast to teachers
    broadcastToTeachers(meetingId, {
        type: 'student_state',
        userId: parseInt(userId, 10),
        userName,
        focusScore,
        isActive,
        deviceStatus,
        timestamp: new Date().toISOString()
    });
    
    log(`Updated focus score for user ${userId} in meeting ${meetingId}: ${focusScore}%`);
}

function handleStudentStateUpdate(ws, data) {
    const participant = participants.get(ws);
    if (!participant) {
        log('[ERROR] Participant not found for student state update', 'warn');
        return;
    }

    const { meetingId, userId, userName } = participant;
    const { focusScore, isActive } = data;
    
    log(`[STUDENT_STATE] Update from ${userName} (${userId}) in meeting ${meetingId}`);
    log(`[STUDENT_STATE] Focus Score: ${focusScore}, Active: ${isActive}`);
    
    // Update focus data if provided
    if (typeof focusScore !== 'undefined') {
        if (!focusData.has(meetingId)) {
            focusData.set(meetingId, new Map());
        }
        focusData.get(meetingId).set(userId, focusScore);
        log(`[STUDENT_STATE] Updated focus score for user ${userId}: ${focusScore}`);
    }
    
    // Get current meeting state
    const meetingParticipants = meetings.get(meetingId);
    if (!meetingParticipants) {
        log(`[ERROR] Meeting ${meetingId} not found`, 'warn');
        return;
    }

    // Build current student state with lowercase type
    const studentState = {
        type: 'student_state',
        userId,
        userName,
        focusScore: focusData.get(meetingId)?.get(userId) || 0,
        isActive: isActive ?? true,
        timestamp: new Date().toISOString()
    };
    
    log(`[STUDENT_STATE] Broadcasting state:`, studentState);

    // Broadcast to all teachers in the meeting
    let teacherCount = 0;
    meetingParticipants.forEach(client => {
        const p = participants.get(client);
        if (p && p.userRole === 'teacher' && client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(studentState));
            log(`[STUDENT_STATE] Sent to teacher ${p.userName} (${p.userId})`);
            teacherCount++;
        }
    });
    
    log(`[STUDENT_STATE] Update broadcast to ${teacherCount} teachers`);
    logCurrentState();
}

function handleDisconnect(ws) {
        const participant = participants.get(ws);
        if (!participant) {
            return;
        }

        const { meetingId, userId, userName, userRole } = participant;
    const meetingParticipants = meetings.get(meetingId);
    
    if (meetingParticipants) {
        // Remove from meeting
        meetingParticipants.delete(ws);
        
        // Broadcast user left
        broadcastToMeeting(meetingId, {
            type: 'user_left',
            meetingId,
            userId,
            userName,
            userRole
        });
        
        // Clean up empty meeting
            if (meetingParticipants.size === 0) {
                meetings.delete(meetingId);
                focusData.delete(meetingId);
            log(`Meeting ${meetingId} closed - no participants remaining`);
        }
    }
    
    // Clean up participant data
    participants.delete(ws);
    
    log(`User ${userName} (${userId}) disconnected from meeting ${meetingId}`);
}

// Helper function to broadcast to all teachers in a meeting
function broadcastToTeachers(meetingId, message) {
    const meetingParticipants = meetings.get(meetingId);
    if (!meetingParticipants) return;

    log(`Broadcasting to teachers in meeting ${meetingId}:`, message);

    meetingParticipants.forEach(client => {
        const participant = participants.get(client);
        if (participant && participant.userRole === 'teacher' && client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(message));
        }
    });
}

// Helper function to broadcast to all participants in a meeting except the sender
function broadcastToMeeting(meetingId, message, excludeWs = null) {
    const meetingParticipants = meetings.get(meetingId);
    if (!meetingParticipants) return;
    
    log(`Broadcasting to meeting ${meetingId}:`, message);

    meetingParticipants.forEach(client => {
        if (client !== excludeWs && client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(message));
        }
    });
}

function logCurrentState() {
    console.log('\n=== Current State ===');
    console.log(`Active Meetings: ${meetings.size}`);
    
    meetings.forEach((meetingParticipants, meetingId) => {
        console.log(`\nMeeting ${meetingId}:`);
        console.log(`Total Participants: ${meetingParticipants.size}`);
        
        let teachers = 0;
        let students = 0;
        meetingParticipants.forEach(ws => {
            const participant = participants.get(ws);
            if (participant) {
                if (participant.userRole === 'teacher') teachers++;
                if (participant.userRole === 'student') students++;
            }
        });
        
        console.log(`Teachers: ${teachers}`);
        console.log(`Students: ${students}`);
        
        // Log focus scores
        const meetingFocusData = focusData.get(meetingId);
        if (meetingFocusData) {
            console.log('Focus Scores:');
            meetingFocusData.forEach((score, userId) => {
                console.log(`  User ${userId}: ${score}%`);
            });
        }
    });
    console.log('===================\n');
}

// Start the server
server.listen(PORT, HOST, () => {
    log(`WebSocket server is running on http://${HOST}:${PORT}`);
    log(`Health check endpoint: http://localhost:${PORT}/health`);
    log(`WebSocket endpoint: ws://localhost:${PORT}`);
});

// Graceful shutdown
process.on('SIGINT', () => {
    log('Received SIGINT. Shutting down gracefully...');
    
    // Close server
    server.close(() => {
        log('Server closed.');
        
        // Close log stream
        logStream.end(() => {
            process.exit(0);
        });
    });
    
    // Force close after 10 seconds
    setTimeout(() => {
        log('Forcing shutdown after timeout', 'warn');
        process.exit(1);
    }, 10000);
});