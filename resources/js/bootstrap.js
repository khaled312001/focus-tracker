// Import Bootstrap's JavaScript
import * as bootstrap from 'bootstrap';
import axios from 'axios';

window.bootstrap = bootstrap;
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Create a connection status indicator on the page (if not already present)
let connectionIndicator = document.getElementById('socket-connection-status');
if (!connectionIndicator && document.body) {
    connectionIndicator = document.createElement('div');
    connectionIndicator.id = 'socket-connection-status';
    connectionIndicator.style.position = 'fixed';
    connectionIndicator.style.bottom = '10px';
    connectionIndicator.style.right = '10px';
    connectionIndicator.style.padding = '5px 10px';
    connectionIndicator.style.borderRadius = '5px';
    connectionIndicator.style.fontSize = '12px';
    connectionIndicator.style.zIndex = '9999';
    connectionIndicator.style.display = 'none'; // Hide by default, show only on error
    document.body.appendChild(connectionIndicator);
}

// Helper function to update connection status indicator
function updateConnectionStatus(status, message = '') {
    const indicator = document.getElementById('socket-connection-status');
    if (!indicator) return;

    const statusColors = {
        connecting: '#fbbf24', // yellow
        connected: '#34d399',  // green
        disconnected: '#f87171', // red
        error: '#ef4444'      // bright red
    };

    indicator.style.backgroundColor = statusColors[status] || '#6b7280';
    indicator.style.color = '#ffffff';
    indicator.style.display = 'block';
    indicator.textContent = message || `WebSocket: ${status}`;

    // Hide the indicator after 5 seconds if connected successfully
    if (status === 'connected') {
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 5000);
    }
}

/**
 * WebSocket Manager for handling real-time communication
 */
class WebSocketManager {
    constructor() {
        this.ws = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.handlers = new Map();
        this.pingInterval = null;
        this.pongTimeout = null;
        this.lastPingTime = null;
        this.debug = true;
        this.connect();
    }

    log(message, type = 'info') {
        if (this.debug) {
            const prefix = '[WebSocketManager]';
            switch (type) {
                case 'error':
                    console.error(prefix, message);
                    break;
                case 'warn':
                    console.warn(prefix, message);
                    break;
                default:
                    console.log(prefix, message);
            }
        }
    }

    async connect() {
        try {
            this.log('Initializing WebSocket connection...');
            const wsUrl = 'ws://127.0.0.1:6001';
            this.log('Connecting to:', wsUrl);

            return new Promise((resolve, reject) => {
                this.ws = new WebSocket(wsUrl);
                this.setupEventHandlers(resolve, reject);
            });
        } catch (error) {
            this.log('Connection error:', error, 'error');
            throw error;
        }
    }

    setupEventHandlers(resolve, reject) {
        this.ws.onopen = () => {
            this.log('Connected successfully');
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.startPingInterval();
            resolve();
        };

        this.ws.onclose = (event) => {
            this.log(`Disconnected: ${event.code} ${event.reason}`, 'error');
            this.isConnected = false;
            this.cleanup();
            
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnect();
            }
        };

        this.ws.onerror = (error) => {
            this.log('WebSocket error:', error, 'error');
            reject(error);
        };

        this.ws.onmessage = this.handleMessage.bind(this);
    }

    handleMessage(event) {
        try {
            // Handle raw ping/pong messages at the protocol level
            if (event.data === 'ping') {
                this.sendPong();
                return;
            }
            if (event.data === 'pong') {
                this.handlePong();
                return;
            }

            // Parse and handle JSON messages
            const message = JSON.parse(event.data);
            this.log('Received message:', message);

            // Normalize message type to lowercase
            const messageType = (message.type || message.TYPE || '').toLowerCase();
            
            if (!messageType) {
                this.log('Message has no type field:', message, 'warn');
                return;
            }

            // Find handler for the message type
            const handler = this.handlers.get(messageType);
            
            if (handler) {
                this.log(`Handling message type: ${messageType}`);
                handler(message);
            } else {
                this.log(`No handler for message type: ${messageType}`, 'warn');
                this.log('Available handlers:', Array.from(this.handlers.keys()));
            }
        } catch (error) {
            // Only log parsing errors for non-ping/pong messages
            if (event.data !== 'ping' && event.data !== 'pong') {
                this.log('Error handling message:', error, 'error');
                this.log('Raw message:', event.data);
            }
        }
    }

    startPingInterval() {
        this.stopPingInterval();
        
        this.pingInterval = setInterval(() => {
            if (this.isConnected) {
                this.sendPing();
            }
        }, 30000);
    }

    stopPingInterval() {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
        if (this.pongTimeout) {
            clearTimeout(this.pongTimeout);
            this.pongTimeout = null;
        }
    }

    sendPing() {
        if (!this.isConnected) return;

        try {
            this.ws.send('ping');
            this.lastPingTime = Date.now();
            
            // Set timeout for pong response
            this.pongTimeout = setTimeout(() => {
                this.log('Ping timeout - no pong received', 'error');
                this.ws.close(1000, 'Ping timeout');
            }, 5000);
        } catch (error) {
            this.log('Error sending ping:', error, 'error');
        }
    }

    sendPong() {
        if (!this.isConnected) return;

        try {
            this.ws.send('pong');
        } catch (error) {
            this.log('Error sending pong:', error, 'error');
        }
    }

    handlePong() {
        if (this.pongTimeout) {
            clearTimeout(this.pongTimeout);
            this.pongTimeout = null;
        }
    }

    async reconnect() {
        this.reconnectAttempts++;
        this.log(`Reconnecting (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
        
        try {
            await this.connect();
        } catch (error) {
            this.log('Reconnection failed:', error, 'error');
            
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                setTimeout(() => {
                    this.reconnect();
                }, Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000));
            }
        }
    }

    cleanup() {
        this.stopPingInterval();
        this.isConnected = false;
    }

    on(type, handler) {
        // Store handler with lowercase type
        this.handlers.set(type.toLowerCase(), handler);
        this.log(`Registered handler for type: ${type.toLowerCase()}`);
        return this;
    }

    off(type) {
        // Remove handler using lowercase type
        this.handlers.delete(type.toLowerCase());
        this.log(`Removed handler for type: ${type.toLowerCase()}`);
        return this;
    }

    async send(data) {
        if (!this.isConnected) {
            throw new Error('WebSocket is not connected');
        }

        try {
            // Normalize message type to lowercase
            if (data.type || data.TYPE) {
                data.type = (data.type || data.TYPE).toLowerCase();
                if (data.TYPE) delete data.TYPE;
            }

            this.log('Sending message:', data);
            this.ws.send(JSON.stringify(data));
        } catch (error) {
            this.log('Error sending message:', error, 'error');
            throw error;
        }
    }

    async joinMeeting(meetingId, userData) {
        this.log('Joining meeting:', meetingId, 'with user data:', userData);
        
        if (!this.isConnected) {
            await this.connect();
        }

        const joinMessage = {
            TYPE: 'JOIN',
            meetingId,
            ...userData
        };

        await this.send(joinMessage);
        this.log('Join message sent successfully');

        // Request initial meeting state if teacher
        if (userData.userRole === 'teacher') {
            await this.send({
                TYPE: 'REQUEST_MEETING_STATE',
                meetingId
            });
        }

        return {
            send: (data) => this.send(data),
            on: (type, handler) => this.on(type, handler),
            off: (type) => this.off(type)
        };
    }
}

// Create global instance
window.wsManager = new WebSocketManager();

// Connect immediately
window.wsManager.connect().catch(error => {
    console.error('Failed to connect to WebSocket server:', error);
});

// Make sure the WebSocket manager is initialized before any other scripts run
document.addEventListener('DOMContentLoaded', () => {
    if (!window.wsManager) {
        console.error('WebSocket manager not initialized properly');
        window.wsManager = wsManager;
    }
});

// Export for use in other files
export default wsManager;
