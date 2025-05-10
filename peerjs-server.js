import { PeerServer } from 'peer';
import express from 'express';
import cors from 'cors';

const app = express();
app.use(cors());

console.log('Starting PeerJS server...');

const server = new PeerServer({
    port: 3001,
    path: '/peerjs',
    corsOptions: { 
        origin: '*',
        methods: ['GET', 'POST'],
        credentials: true
    },
    debug: true,
    allow_discovery: true,
    proxied: false,
    cleanup_out_msgs: 1000,
    ssl: {
        enabled: false
    },
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' }
    ]
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok',
        server: {
            port: 3001,
            path: '/peerjs',
            debug: true,
            allow_discovery: true
        }
    });
});

app.listen(3000, () => {
    console.log('Express server running on port 3000');
});

server.on('connection', (client) => {
    console.log('Client connected:', client.getId());
    console.log('Connection details:', {
        id: client.getId(),
        token: client.getToken(),
        ip: client.getIp()
    });
});

server.on('disconnect', (client) => {
    console.log('Client disconnected:', client.getId());
    console.log('Disconnection details:', {
        id: client.getId(),
        token: client.getToken(),
        ip: client.getIp()
    });
});

server.on('error', (error) => {
    console.error('PeerJS server error:', error);
    console.error('Error details:', {
        type: error.type,
        message: error.message,
        stack: error.stack
    });
});

console.log('PeerJS server running on port 3001'); 