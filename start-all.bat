@echo off
echo Starting all servers...

REM Kill existing processes on the required ports
echo Cleaning up existing processes...
taskkill /F /IM node.exe /FI "WINDOWTITLE eq Laravel Echo Server*" 2>NUL
taskkill /F /IM node.exe /FI "WINDOWTITLE eq WebSocket Server*" 2>NUL
taskkill /F /IM node.exe /FI "WINDOWTITLE eq Vite Server*" 2>NUL
taskkill /F /IM php.exe /FI "WINDOWTITLE eq PHP Server*" 2>NUL
taskkill /F /IM python.exe /FI "WINDOWTITLE eq Python Server*" 2>NUL

REM Additional cleanup by port
FOR /F "tokens=5" %%P IN ('netstat -ano ^| findstr :6001') DO taskkill /F /PID %%P 2>NUL
FOR /F "tokens=5" %%P IN ('netstat -ano ^| findstr :6002') DO taskkill /F /PID %%P 2>NUL
FOR /F "tokens=5" %%P IN ('netstat -ano ^| findstr :5000') DO taskkill /F /PID %%P 2>NUL

REM Wait for processes to be killed
timeout /t 2 /nobreak

echo Starting Laravel server...
start "PHP Server" cmd /k "php artisan serve"
timeout /t 3 /nobreak

echo Starting Python server...
cd python
start "Python Server" cmd /k "python app.py"
cd ..
timeout /t 3 /nobreak

echo Starting WebSocket server...
start "WebSocket Server" cmd /k "npm run websocket"
timeout /t 3 /nobreak

echo Starting Laravel Echo Server...
start "Laravel Echo Server" cmd /k "npx laravel-echo-server start"
timeout /t 3 /nobreak

echo Starting Vite development server...
start "Vite Server" cmd /k "npm run dev"

echo.
echo All servers started!
echo.
echo Available endpoints:
echo Laravel: http://127.0.0.1:8000
echo Python Focus Detection: http://127.0.0.1:5000
echo WebSocket Server: ws://127.0.0.1:6002
echo Laravel Echo Server: http://127.0.0.1:6001
echo Vite: http://127.0.0.1:5173
echo.
echo Press any key to stop all servers...
pause

echo Stopping all servers...
taskkill /F /IM node.exe /FI "WINDOWTITLE eq Laravel Echo Server*"
taskkill /F /IM node.exe /FI "WINDOWTITLE eq WebSocket Server*"
taskkill /F /IM node.exe /FI "WINDOWTITLE eq Vite Server*"
taskkill /F /IM php.exe /FI "WINDOWTITLE eq PHP Server*"
taskkill /F /IM python.exe /FI "WINDOWTITLE eq Python Server*"

REM Additional cleanup by port
FOR /F "tokens=5" %%P IN ('netstat -ano ^| findstr :6001') DO taskkill /F /PID %%P 2>NUL
FOR /F "tokens=5" %%P IN ('netstat -ano ^| findstr :6002') DO taskkill /F /PID %%P 2>NUL
FOR /F "tokens=5" %%P IN ('netstat -ano ^| findstr :5000') DO taskkill /F /PID %%P 2>NUL 