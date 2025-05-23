$port = 6001

function Test-PortInUse {
    param($port)
    $listener = $null
    try {
        $listener = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Loopback, $port)
        $listener.Start()
        return $false
    }
    catch {
        return $true
    }
    finally {
        if ($listener) {
            $listener.Stop()
        }
    }
}

while ($true) {
    Clear-Host
    Write-Host "Monitoring WebSocket Server (Press Ctrl+C to stop)..." -ForegroundColor Cyan
    Write-Host "Checking port $port availability..." -ForegroundColor Yellow
    
    if (Test-PortInUse -port $port) {
        Write-Host "Port $port is in use. Attempting to stop existing process..." -ForegroundColor Red
        Get-Process -Name "node" | Where-Object {$_.CommandLine -like "*websocket-server.js*"} | Stop-Process -Force
        Start-Sleep -Seconds 2
    }
    
    try {
        node --experimental-modules websocket-server.js
    }
    catch {
        Write-Host "Error starting WebSocket server: $_" -ForegroundColor Red
        Write-Host "Retrying in 5 seconds..." -ForegroundColor Yellow
    }
    
    Start-Sleep -Seconds 5
}
