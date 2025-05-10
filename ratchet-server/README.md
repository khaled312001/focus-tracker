# Focus Tracker WebSocket Server

This is a standalone WebSocket server implementation using Ratchet for the Focus Tracker application.

## Requirements

- PHP 7.4 or higher
- Composer
- PHP extensions:
  - ext-json
  - ext-mbstring
  - ext-pcntl
  - ext-posix
  - ext-sockets
  - ext-zip

## Installation

1. Clone this repository
2. Run `composer install` to install dependencies

## Running the Server

To start the WebSocket server, run:

```bash
php websocket-server.php
```

The server will start listening on port 8080.

## WebSocket Client Connection

To connect to the WebSocket server from a client, use:

```javascript
const ws = new WebSocket('ws://localhost:8080');

ws.onopen = function() {
    console.log('Connected to WebSocket server');
};

ws.onmessage = function(e) {
    console.log('Received:', e.data);
};

ws.onclose = function() {
    console.log('Disconnected from WebSocket server');
};
```

## Features

- Real-time bidirectional communication
- Broadcast messages to all connected clients
- Connection management
- Error handling 