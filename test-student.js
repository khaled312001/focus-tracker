import WebSocket from 'ws';

// Create WebSocket connection
const ws = new WebSocket('ws://127.0.0.1:6001');

// Student data
const studentData = {
    type: 'join',
    meetingId: 20,
    userId: 2,
    userName: "Test Student",
    userRole: "student"
};

// Handle connection open
ws.on('open', () => {
    console.log('Connected to WebSocket server');
    
    // Send join message
    ws.send(JSON.stringify(studentData));
    console.log('Sent join message:', studentData);
});

// Handle incoming messages
ws.on('message', (data) => {
    const message = JSON.parse(data);
    console.log('Received message:', message);
});

// Handle errors
ws.on('error', (error) => {
    console.error('WebSocket error:', error);
});

// Handle connection close
ws.on('close', () => {
    console.log('Disconnected from WebSocket server');
});

// Keep the process running
process.stdin.resume(); 