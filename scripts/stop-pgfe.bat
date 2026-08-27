@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE - Arret

call "%~dp0_env.bat"

echo.
echo ========================================
echo   PGFE - Arret des services
echo ========================================
echo.

REM Close dedicated windows if still open
taskkill /FI "WINDOWTITLE eq PGFE Backend*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq PGFE Frontend*" /T /F >nul 2>&1

REM Free ports used by artisan / Vite (best-effort)
call :kill_port %BACKEND_PORT%
call :kill_port %FRONTEND_PORT%

echo.
echo Services PGFE arretes ^(Laragon reste ouvert^).
echo Vous pouvez fermer Laragon manuellement si besoin.
echo.
ping -n 3 127.0.0.1 >nul
exit /b 0

:kill_port
set "PORT=%~1"
for /f "tokens=5" %%P in ('netstat -ano ^| findstr /R /C:":%PORT% .*LISTENING" 2^>nul') do (
  if not "%%P"=="0" (
    echo Arret du processus sur le port %PORT% ^(PID %%P^)...
    taskkill /PID %%P /T /F >nul 2>&1
  )
)
goto :eof
