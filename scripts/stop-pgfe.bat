@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE - Arret

call "%~dp0_env.bat"

set "LOGDIR=%SCRIPTS_DIR%\logs"

REM Tuer via PID enregistres (processus cmd caches)
if exist "%LOGDIR%\backend.pid" (
  for /f "usebackq delims=" %%P in ("%LOGDIR%\backend.pid") do (
    taskkill /PID %%P /T /F >nul 2>&1
  )
  del /f /q "%LOGDIR%\backend.pid" >nul 2>&1
)
if exist "%LOGDIR%\frontend.pid" (
  for /f "usebackq delims=" %%P in ("%LOGDIR%\frontend.pid") do (
    taskkill /PID %%P /T /F >nul 2>&1
  )
  del /f /q "%LOGDIR%\frontend.pid" >nul 2>&1
)

REM Liberer les ports (couvre php / node enfants)
call :kill_port %BACKEND_PORT%
call :kill_port %FRONTEND_PORT%

REM Anciennes fenetres nommees (si encore presentes)
taskkill /FI "WINDOWTITLE eq PGFE Backend*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq PGFE Frontend*" /T /F >nul 2>&1

echo Services PGFE arretes. Laragon reste ouvert.
ping -n 2 127.0.0.1 >nul
exit /b 0

:kill_port
set "PORT=%~1"
for /f "tokens=5" %%P in ('netstat -ano 2^>nul ^| findstr /R /C:":%PORT% .*LISTENING"') do (
  if not "%%P"=="0" (
    taskkill /PID %%P /T /F >nul 2>&1
  )
)
goto :eof
