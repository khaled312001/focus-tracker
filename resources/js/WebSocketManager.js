class WebSocketManager {
    constructor(url) {
        this.url = url;
        this.ws = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.messageHandlers = new Map();
        this.pingInterval = null;
        this.lastPingTime = null;
        this.pingTimeout = null;
        this.connect();
    }

    connect() {
        try {
            this.ws = new WebSocket(this.url);
            this.setupEventHandlers();
        } catch (error) {
            console.error('WebSocket connection error:', error);
            this.handleReconnect();
        }
    }

    setupEventHandlers() {
        this.ws.onopen = () => {
            console.log('WebSocket connected');
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.setupPing();
        };

        this.ws.onclose = (event) => {
            console.log('WebSocket closed:', event.code, event.reason);
            this.isConnected = false;
            this.clearPingInterval();
            this.handleReconnect();
        };

        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
            this.clearPingInterval();
        };

        this.ws.onmessage = (event) => {
            try {
                // Handle ping/pong messages
                if (event.data === 'ping') {
                    this.ws.send('pong');
                    return;
                }
                if (event.data === 'pong') {
                    this.handlePong();
                    return;
                }

                const data = JSON.parse(event.data);
                const handler = this.messageHandlers.get(data.type);
                if (handler) {
                    handler(data);
                }
            } catch (error) {
                console.error('Error handling message:', error);
            }
        };
    }

    setupPing() {
        this.clearPingInterval();
        this.pingInterval = setInterval(() => {
            if (this.ws.readyState === WebSocket.OPEN) {
                this.ws.send('ping');
                this.lastPingTime = Date.now();
                
                // Set timeout for pong response
                this.pingTimeout = setTimeout(() => {
                    console.log('No pong received, reconnecting...');
                    this.ws.close();
                }, 5000); // Wait 5 seconds for pong
            }
        }, 15000); // Send ping every 15 seconds
    }

    handlePong() {
        clearTimeout(this.pingTimeout);
        this.lastPingTime = null;
    }

    clearPingInterval() {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
        if (this.pingTimeout) {
            clearTimeout(this.pingTimeout);
            this.pingTimeout = null;
        }
    }

    handleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            setTimeout(() => this.connect(), this.reconnectDelay * this.reconnectAttempts);
        } else {
            console.error('Max reconnection attempts reached');
        }
    }

    // ... rest of the existing code ...
} 