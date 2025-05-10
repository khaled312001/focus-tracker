import http from 'http';
import { exec } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// Get directory name in ES module
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Configuration
const SERVERS = [
    { name: 'Laravel Server', url: 'http://127.0.0.1:8000/api/health', port: 8000 },
    { name: 'WebSocket Server', url: 'http://127.0.0.1:6001/health', port: 6001 },
];

const CHECK_INTERVAL = 30000; // 30 seconds
const MAX_FAILURES = 3; // Number of consecutive failures before restarting
const LOG_PATH = path.join(__dirname, 'logs', 'server-monitor.log');

// Ensure log directory exists
const logDir = path.join(__dirname, 'logs');
if (!fs.existsSync(logDir)) {
    fs.mkdirSync(logDir, { recursive: true });
}

// Create log stream
const logStream = fs.createWriteStream(LOG_PATH, { flags: 'a' });

// Logging function
function log(message, level = 'INFO') {
    const timestamp = new Date().toISOString();
    const logMessage = `[${timestamp}] [${level}] ${message}\n`;
    
    console.log(logMessage.trim());
    logStream.write(logMessage);
}

// Server failure counts
const failureCounts = {};
SERVERS.forEach(server => {
    failureCounts[server.name] = 0;
});

// Health check function
function checkServerHealth(server) {
    return new Promise((resolve) => {
        log(`Checking health of ${server.name} at ${server.url}...`);
        
        const request = http.get(server.url, { timeout: 5000 }, (response) => {
            let data = '';
            
            response.on('data', (chunk) => {
                data += chunk;
            });
            
            response.on('end', () => {
                if (response.statusCode >= 200 && response.statusCode < 300) {
                    // Server is healthy
                    failureCounts[server.name] = 0;
                    try {
                        const parsedData = JSON.parse(data);
                        log(`${server.name} is healthy: ${JSON.stringify(parsedData)}`);
                    } catch (e) {
                        log(`${server.name} is healthy but returned invalid JSON: ${data.substring(0, 100)}`);
                    }
                    resolve(true);
                } else {
                    // Server returned error status
                    failureCounts[server.name]++;
                    log(`${server.name} returned status ${response.statusCode}`, 'WARN');
                    log(`Response content: ${data.substring(0, 200)}`, 'WARN');
                    resolve(false);
                }
            });
        });
        
        request.on('error', (error) => {
            // Server is not responding
            failureCounts[server.name]++;
            log(`${server.name} check failed: ${error.message}`, 'ERROR');
            resolve(false);
        });
        
        request.on('timeout', () => {
            // Server timed out
            request.destroy();
            failureCounts[server.name]++;
            log(`${server.name} check timed out`, 'ERROR');
            resolve(false);
        });
    });
}

// Restart server function
function restartServer(server) {
    return new Promise((resolve) => {
        log(`Attempting to restart ${server.name}...`, 'WARN');
        
        // Kill process on port
        exec(`FOR /F "tokens=5" %a in ('netstat -aon ^| find ":${server.port}"') do taskkill /F /PID %a`, (error) => {
            if (error) {
                log(`Failed to kill ${server.name} process: ${error.message}`, 'ERROR');
            }
            
            // Start server
            if (server.port === 8000) {
                // Restart Laravel server
                exec('php artisan serve', { detached: true }, (error) => {
                    if (error) {
                        log(`Failed to restart Laravel server: ${error.message}`, 'ERROR');
                    } else {
                        log('Laravel server restarted', 'INFO');
                    }
                    resolve();
                });
            } else if (server.port === 6001) {
                // Restart WebSocket server
                exec('node websocket-server.js', { detached: true }, (error) => {
                    if (error) {
                        log(`Failed to restart WebSocket server: ${error.message}`, 'ERROR');
                    } else {
                        log('WebSocket server restarted', 'INFO');
                    }
                    resolve();
                });
            } else {
                log(`Unknown server port ${server.port}, not restarting`, 'ERROR');
                resolve();
            }
        });
    });
}

// Main monitoring loop
async function monitorServers() {
    log('Starting server health checks...');
    
    for (const server of SERVERS) {
        const isHealthy = await checkServerHealth(server);
        
        if (isHealthy) {
            log(`${server.name} is healthy`);
        } else {
            log(`${server.name} failed health check (${failureCounts[server.name]}/${MAX_FAILURES})`, 'WARN');
            
            if (failureCounts[server.name] >= MAX_FAILURES) {
                log(`${server.name} has failed ${MAX_FAILURES} times, restarting...`, 'ERROR');
                await restartServer(server);
                failureCounts[server.name] = 0;
            }
        }
    }
    
    // Schedule next check
    setTimeout(monitorServers, CHECK_INTERVAL);
}

// Start monitoring
log('Server monitor starting...');
monitorServers();

// Handle graceful shutdown
process.on('SIGINT', () => {
    log('Shutting down server monitor...', 'INFO');
    logStream.end();
    process.exit(0);
}); 