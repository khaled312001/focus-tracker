@echo off 
echo Stopping all servers... 
taskkill /F /IM "php.exe" /FI "WINDOWTITLE eq Laravel Development Server" 2>nul 
taskkill /F /IM "node.exe" /FI "WINDOWTITLE eq WebSocket Server" 2>nul 
taskkill /F /IM "python.exe" /FI "WINDOWTITLE eq Python Focus Server" 2>nul 
taskkill /F /IM "node.exe" /FI "WINDOWTITLE eq Vite Dev Server" 2>nul 
echo All servers stopped 
del "%~f0" 
