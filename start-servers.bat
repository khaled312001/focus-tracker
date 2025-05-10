@echo off
setlocal EnableDelayedExpansion

:: Set up automatic cleanup on script exit
set "CLEANUP_SCRIPT=%~dp0cleanup.bat"
echo @echo off > "%CLEANUP_SCRIPT%"
echo echo Stopping all servers... >> "%CLEANUP_SCRIPT%"
echo taskkill /F /IM "php.exe" /FI "WINDOWTITLE eq Laravel Development Server" 2^>nul >> "%CLEANUP_SCRIPT%"
echo taskkill /F /IM "node.exe" /FI "WINDOWTITLE eq WebSocket Server" 2^>nul >> "%CLEANUP_SCRIPT%"
echo taskkill /F /IM "python.exe" /FI "WINDOWTITLE eq Python Focus Server" 2^>nul >> "%CLEANUP_SCRIPT%"
echo taskkill /F /IM "node.exe" /FI "WINDOWTITLE eq Vite Dev Server" 2^>nul >> "%CLEANUP_SCRIPT%"
echo echo All servers stopped >> "%CLEANUP_SCRIPT%"
echo del "%%~f0" >> "%CLEANUP_SCRIPT%"

:: Register cleanup script to run on exit
for /f "tokens=2" %%a in ('reg query "HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Run" /v "FocusTrackerCleanup" 2^>nul') do (
    reg delete "HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Run" /v "FocusTrackerCleanup" /f
)
reg add "HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Run" /v "FocusTrackerCleanup" /t REG_SZ /d "\"%CLEANUP_SCRIPT%\"" /f

echo Stopping existing processes...

REM Kill all relevant processes
taskkill /F /IM "php.exe" /FI "WINDOWTITLE eq Laravel Development Server" 2>nul
taskkill /F /IM "node.exe" /FI "WINDOWTITLE eq WebSocket Server" 2>nul
taskkill /F /IM "python.exe" /FI "WINDOWTITLE eq Python Focus Server" 2>nul
taskkill /F /IM "node.exe" /FI "WINDOWTITLE eq Vite Dev Server" 2>nul

REM Kill any process using our required ports
echo Checking and freeing required ports...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000') do taskkill /F /PID %%a 2>nul
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :6001') do taskkill /F /PID %%a 2>nul
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :5174') do taskkill /F /PID %%a 2>nul

REM Wait for processes to fully terminate
timeout /t 2 /nobreak > nul

:START_SERVERS
cls
echo Starting core servers...

REM Store the current directory
set "CURRENT_DIR=%CD%"

echo Starting Laravel Server...
start "Laravel Development Server" /min cmd /c "php artisan serve"
timeout /t 2 /nobreak > nul

echo Starting Vite Server...
start "Vite Dev Server" /min cmd /c "npm run dev"
timeout /t 2 /nobreak > nul

echo Starting WebSocket Server...
start "WebSocket Server" cmd /c "node websocket-server.js"
timeout /t 2 /nobreak > nul

echo Starting Python Focus Tracker...
start "Python Focus Server" cmd /c "python python_model\test_focus.py --meeting-id 20 --user-id 1 --user-name Jane"
timeout /t 2 /nobreak > nul

echo All servers started successfully!
echo.
echo Laravel Server: http://localhost:8000
echo Vite Server: http://localhost:5174
echo WebSocket Server: ws://localhost:6001
echo.
echo Press any key to stop all servers...
pause > nul

REM Run cleanup script
call "%CLEANUP_SCRIPT%"

endlocal