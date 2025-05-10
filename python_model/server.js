const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "http://localhost:8000",
        methods: ["GET", "POST"]
    }
});

// Store active meetings and their participants
const meetings = new Map();

io.on('connection', (socket) => {
    console.log('User connected:', socket.id);

    // Handle joining a meeting
    socket.on('join meeting', (data) => {
        const { meetingId, userId, userName, role } = data;
        
        // Join the meeting room
        socket.join(`meeting:${meetingId}`);
        
        // Initialize meeting if it doesn't exist
        if (!meetings.has(meetingId)) {
            meetings.set(meetingId, new Map());
        }
        
        // Add participant to meeting
        const meeting = meetings.get(meetingId);
        meeting.set(userId, {
            socketId: socket.id,
            userName,
            role
        });
        
        // Notify other participants
        socket.to(`meeting:${meetingId}`).emit('participant joined', {
            userId,
            userName,
            role
        });
        
        // Send current participants to the new participant
        const participants = Array.from(meeting.values()).map(p => ({
            userId: p.userId,
            userName: p.userName,
            role: p.role
        }));
        socket.emit('current participants', participants);
    });

    // Handle chat messages
    socket.on('chat message', (data) => {
        const { meetingId, userId, userName, message } = data;
        io.to(`meeting:${meetingId}`).emit('chat message', {
            userId,
            userName,
            message
        });
    });

    // Handle video/audio streams
    socket.on('stream', (data) => {
        const { meetingId, userId, stream } = data;
        socket.to(`meeting:${meetingId}`).emit('stream', {
            userId,
            stream
        });
    });

    // Handle leaving a meeting
    socket.on('leave meeting', (data) => {
        const { meetingId, userId } = data;
        
        // Remove participant from meeting
        if (meetings.has(meetingId)) {
            const meeting = meetings.get(meetingId);
            meeting.delete(userId);
            
            // Delete meeting if empty
            if (meeting.size === 0) {
                meetings.delete(meetingId);
            }
            
            // Notify other participants
            socket.to(`meeting:${meetingId}`).emit('participant left', {
                userId,
                userName: meeting.get(userId)?.userName
            });
        }
        
        // Leave the meeting room
        socket.leave(`meeting:${meetingId}`);
    });

    // Handle disconnection
    socket.on('disconnect', () => {
        console.log('User disconnected:', socket.id);
        
        // Find and remove the user from any meetings they were in
        for (const [meetingId, meeting] of meetings.entries()) {
            for (const [userId, participant] of meeting.entries()) {
                if (participant.socketId === socket.id) {
                    meeting.delete(userId);
                    
                    // Delete meeting if empty
                    if (meeting.size === 0) {
                        meetings.delete(meetingId);
                    }
                    
                    // Notify other participants
                    io.to(`meeting:${meetingId}`).emit('participant left', {
                        userId,
                        userName: participant.userName
                    });
                    break;
                }
            }
        }
    });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`WebSocket server running on port ${PORT}`);
}); 