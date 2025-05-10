@echo off
echo Starting Python server...

:: Kill any existing process on port 5000
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :5000') do taskkill /F /PID %%a 2>NUL

:: Start Python server
python app.py

pause 