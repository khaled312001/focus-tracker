// WebSocket Manager for handling real-time communication
export class WebSocketManager {
    constructor() {
        this.ws = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.messageHandlers = new Map();
        this.lastPingTime = null;
        this.pingInterval = null;
        this.pingTimeout = null;
        this.currentMeetingId = null;
        this.initialize();
    }

    // Initialize WebSocket connection
    async initialize() {
        try {
            console.log('[WebSocketManager] Initializing WebSocket connection...');
            const wsUrl = 'ws://127.0.0.1:6001';
            console.log('[WebSocketManager] Connecting to:', wsUrl);
            
            this.ws = new WebSocket(wsUrl);
            this.setupEventHandlers();
            
            return new Promise((resolve, reject) => {
                this.ws.onopen = () => {
                    console.log('[WebSocketManager] Connected successfully');
                    this.isConnected = true;
                    this.reconnectAttempts = 0;
                    this.startPingInterval();
                    
                    // If we were in a meeting before reconnecting, rejoin it
                    if (this.currentMeetingId) {
                        this.requestMeetingState(this.currentMeetingId);
                    }
                    
                    resolve();
                };

                this.ws.onerror = (error) => {
                    console.error('[WebSocketManager] Connection error:', error);
                    reject(error);
                };
            });
            } catch (error) {
            console.error('[WebSocketManager] Failed to initialize:', error);
            throw error;
        }
    }

    // Setup WebSocket event handlers
    setupEventHandlers() {
        this.ws.onmessage = (event) => {
            try {
                // Handle ping/pong messages separately
                if (event.data === 'ping') {
                    this.handlePing();
                    return;
                }
                if (event.data === 'pong') {
                    this.handlePong();
                    return;
                }

                // Handle JSON messages
                const message = JSON.parse(event.data);
                console.log('[WebSocketManager] Received message:', message);
                this.handleMessage(message);
            } catch (error) {
                console.error('[WebSocketManager] Error handling message:', error);
            }
        };

        this.ws.onclose = (event) => {
            console.log('[WebSocketManager] Disconnected:', event.code, event.reason);
            this.isConnected = false;
            this.stopPingInterval();
            
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnect();
            }
        };
    }

    // Start ping interval
    startPingInterval() {
        this.stopPingInterval(); // Clear any existing intervals
        
        // Send ping every 30 seconds
        this.pingInterval = setInterval(() => {
            if (this.isConnected) {
                this.ws.send('ping');
                this.lastPingTime = Date.now();
                
                // Set timeout for pong response
                this.pingTimeout = setTimeout(() => {
                    console.log('[WebSocketManager] Ping timeout - no pong received');
                    this.ws.close(1000, 'Ping timeout');
                }, 5000); // 5 second timeout
            }
        }, 30000);
    }

    // Stop ping interval
    stopPingInterval() {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
        if (this.pingTimeout) {
            clearTimeout(this.pingTimeout);
            this.pingTimeout = null;
        }
    }

    // Handle incoming ping
    handlePing() {
        if (this.isConnected) {
            this.ws.send('pong');
        }
    }

    // Handle incoming pong
    handlePong() {
        if (this.pingTimeout) {
            clearTimeout(this.pingTimeout);
            this.pingTimeout = null;
        }
    }

    // Handle incoming message
    handleMessage(message) {
        const handler = this.messageHandlers.get(message.type);
        if (handler) {
            handler(message);
        } else {
            console.log('[WebSocketManager] No handler for message type:', message.type);
        }
    }

    // Register message handler
    on(type, handler) {
        console.log('[WebSocketManager] Registering handler for:', type);
        this.messageHandlers.set(type, handler);
    }

    // Remove message handler
    off(type) {
        this.messageHandlers.delete(type);
    }

    // Send message
    async send(message) {
        if (!this.isConnected) {
            throw new Error('WebSocket is not connected');
        }
        
        console.log('[WebSocketManager] Sending message:', message);
        this.ws.send(JSON.stringify(message));
    }

    // Request current meeting state
    async requestMeetingState(meetingId) {
        console.log('[WebSocketManager] Requesting meeting state for meeting:', meetingId);
        await this.send({
            type: 'request_meeting_state',
            meetingId: meetingId
        });
    }

    // Join meeting
    async joinMeeting(meetingId, userData) {
        console.log('[WebSocketManager] Joining meeting:', meetingId, 'with user data:', userData);
        this.currentMeetingId = meetingId;
        
        await this.send({
            type: 'join_meeting',
            meetingId,
            ...userData
        });

        // Request initial meeting state after joining
        await this.requestMeetingState(meetingId);
    }

    // Attempt to reconnect
    async reconnect() {
        this.reconnectAttempts++;
        console.log(`[WebSocketManager] Reconnecting (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
        
        try {
            await this.initialize();
        } catch (error) {
            console.error('[WebSocketManager] Reconnection failed:', error);
            
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                // Exponential backoff
                const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);
                setTimeout(() => this.reconnect(), delay);
            }
        }
    }
}

// Initialize WebSocket manager globally
window.wsManager = window.wsManager || new WebSocketManager();

// Export singleton instance
export const webSocketManager = window.wsManager; 