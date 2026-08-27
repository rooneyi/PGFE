@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE

call "%~dp0_env.bat"

if not exist "%BACKEND%\artisan" (
  echo [ERREUR] Backend introuvable. Lancez INSTALLER-PGFE.bat ou setup-pgfe.bat
  ping -n 4 127.0.0.1 >nul
  exit /b 1
)
if not exist "%FRONTEND%\package.json" (
  echo [ERREUR] Frontend introuvable. Lancez INSTALLER-PGFE.bat ou setup-pgfe.bat
  ping -n 4 127.0.0.1 >nul
  exit /b 1
)

REM Outils manquants ? Ne pas reinstaller silencieusement au quotidien
where php >nul 2>&1
if errorlevel 1 (
  echo [ERREUR] php introuvable. Lancez scripts\setup-pgfe.bat
  ping -n 5 127.0.0.1 >nul
  exit /b 1
)
where npm >nul 2>&1
if errorlevel 1 (
  echo [ERREUR] npm introuvable. Lancez scripts\setup-pgfe.bat
  ping -n 5 127.0.0.1 >nul
  exit /b 1
)
if not defined LARAGON_EXE (
  where php >nul 2>&1
  if errorlevel 1 (
    echo [ERREUR] Laragon/PHP manquant. Lancez scripts\setup-pgfe.bat
    ping -n 5 127.0.0.1 >nul
    exit /b 1
  )
)

REM 1. Laragon (GUI visible — OK)
if defined LARAGON_EXE (
  tasklist /FI "IMAGENAME eq laragon.exe" 2>nul | find /I "laragon.exe" >nul
  if errorlevel 1 (
    start "" "%LARAGON_EXE%"
    ping -n 8 127.0.0.1 >nul
  )
)

REM 2. Backend + frontend caches (pas de fenetre CMD)
powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "%~dp0_start-services.ps1" -Target all
if errorlevel 1 (
  echo [ERREUR] Demarrage des services echoue. Voir scripts\logs\
  ping -n 5 127.0.0.1 >nul
  exit /b 1
)

REM 3. Navigateur
ping -n 6 127.0.0.1 >nul
start "" "%FRONTEND_URL%"

exit /b 0
