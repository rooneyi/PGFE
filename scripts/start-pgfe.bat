@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE - Demarrage

call "%~dp0_env.bat"

echo.
echo ========================================
echo   PGFE - Demarrage automatique
echo ========================================
echo   Projet : %ROOT%
echo.

if not exist "%BACKEND%\artisan" (
  echo [ERREUR] Backend introuvable : %BACKEND%
  echo Verifiez que le depot a ete clone correctement.
  pause
  exit /b 1
)
if not exist "%FRONTEND%\package.json" (
  echo [ERREUR] Frontend introuvable : %FRONTEND%
  echo Verifiez que le depot a ete clone correctement.
  pause
  exit /b 1
)

where php >nul 2>&1
if not errorlevel 1 (
  php -r "exit(version_compare(PHP_VERSION,'8.4.0','>=')?0:1);" >nul 2>&1
  if errorlevel 1 (
    echo [ATTENTION] PHP 8.4+ est requis. Version detectee :
    php -v
    echo           Choisissez PHP 8.4 dans Laragon, ou installez PHP 8.4.
    echo.
  )
)

REM --- 1. Laragon ---
if defined LARAGON_EXE (
  tasklist /FI "IMAGENAME eq laragon.exe" 2>nul | find /I "laragon.exe" >nul
  if errorlevel 1 (
    echo [1/4] Demarrage de Laragon...
    start "" "%LARAGON_EXE%"
    echo       Attente des services ^(MySQL / PHP^)...
    ping -n 11 127.0.0.1 >nul
  ) else (
    echo [1/4] Laragon est deja en cours d'execution.
  )
) else (
  echo [1/4] Laragon non detecte.
  echo       Assurez-vous que MySQL et PHP sont disponibles.
)

REM --- 2. Backend ---
call :port_in_use %BACKEND_PORT%
if "%PORT_BUSY%"=="1" (
  echo [2/4] Backend deja actif sur le port %BACKEND_PORT%.
) else (
  echo [2/4] Demarrage du backend Laravel ^(port %BACKEND_PORT%^)...
  start "PGFE Backend" /MIN "%~dp0_run-backend.bat"
)

REM --- 3. Frontend ---
call :port_in_use %FRONTEND_PORT%
if "%PORT_BUSY%"=="1" (
  echo [3/4] Frontend deja actif sur le port %FRONTEND_PORT%.
) else (
  echo [3/4] Demarrage du frontend Vue ^(port %FRONTEND_PORT%^)...
  start "PGFE Frontend" /MIN "%~dp0_run-frontend.bat"
)

REM --- 4. Navigateur ---
echo [4/4] Ouverture du navigateur dans quelques secondes...
ping -n 7 127.0.0.1 >nul
start "" "%FRONTEND_URL%"

echo.
echo ----------------------------------------
echo   PGFE demarre.
echo   Front  : %FRONTEND_URL%
echo   API    : %BACKEND_URL%
echo.
echo   Pour arreter : scripts\stop-pgfe.bat
echo ----------------------------------------
echo.
ping -n 4 127.0.0.1 >nul
exit /b 0

:port_in_use
set "PORT_BUSY=0"
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { $c = New-Object System.Net.Sockets.TcpClient('127.0.0.1', %~1); $c.Close(); exit 0 } catch { exit 1 }" >nul 2>&1
if not errorlevel 1 set "PORT_BUSY=1"
goto :eof
