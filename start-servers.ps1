# Function to check if a port is in use
function Test-PortInUse {
    param($port)
    
    try {
        $listener = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Loopback, $port)
        $listener.Start()
        $listener.Stop()
        return $false
    } catch {
        return $true
    }
}

# Function to stop Node.js processes
function Stop-NodeProcesses {
    Get-Process -Name "node" -ErrorAction SilentlyContinue | Where-Object {$_.CommandLine -like "*websocket-server.js*"} | Stop-Process -Force
    Start-Sleep -Seconds 2
}

# Clear the console
Clear-Host

Write-Host "Starting servers..." -ForegroundColor Cyan

# Check and start WebSocket server
$wsPort = 6001
if (Test-PortInUse $wsPort) {
    Write-Host "Port $wsPort is in use. Stopping existing processes..." -ForegroundColor Yellow
    Stop-NodeProcesses
}

# Start WebSocket server
Write-Host "Starting WebSocket server..." -ForegroundColor Cyan
Start-Process -NoNewWindow powershell -ArgumentList "-Command", "node websocket-server.js"

# Wait a moment for WebSocket server to initialize
Start-Sleep -Seconds 2

# Start test student simulation
Write-Host "Starting test student simulation..." -ForegroundColor Cyan
Start-Process -NoNewWindow powershell -ArgumentList "-Command", "node test-student.js"

Write-Host "All servers started successfully!" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop all servers" -ForegroundColor Yellow

# Keep the script running
try {
    while ($true) {
        Start-Sleep -Seconds 1
    }
} finally {
    # Cleanup when script is interrupted
    Stop-NodeProcesses
    Write-Host "`nServers stopped." -ForegroundColor Cyan
} 