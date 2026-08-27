@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE

call "%~dp0_env.bat"

if not exist "%BACKEND%\artisan" (
  call :fail "Backend introuvable. Lancez INSTALLER-PGFE.bat ou setup-pgfe.bat"
  exit /b 1
)
if not exist "%FRONTEND%\package.json" (
  call :fail "Frontend introuvable. Lancez INSTALLER-PGFE.bat ou setup-pgfe.bat"
  exit /b 1
)

REM Outils manquants ? Ne pas reinstaller silencieusement au quotidien
where php >nul 2>&1
if errorlevel 1 (
  call :fail "php introuvable. Lancez scripts\setup-pgfe.bat"
  exit /b 1
)
where npm >nul 2>&1
if errorlevel 1 (
  call :fail "npm introuvable. Lancez scripts\setup-pgfe.bat"
  exit /b 1
)
if not defined LARAGON_EXE (
  where php >nul 2>&1
  if errorlevel 1 (
    call :fail "Laragon/PHP manquant. Lancez scripts\setup-pgfe.bat"
    exit /b 1
  )
)

REM 1. Laragon (GUI visible)
if defined LARAGON_EXE (
  tasklist /FI "IMAGENAME eq laragon.exe" 2>nul | find /I "laragon.exe" >nul
  if errorlevel 1 (
    start "" "%LARAGON_EXE%"
    ping -n 8 127.0.0.1 >nul
  )
)

REM 2. Backend + frontend caches, DETACHES (survivent a la fermeture de cette fenetre)
REM    Ne pas appeler stop-pgfe ici — seul stop-pgfe.bat arrete les services.
powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "%~dp0_start-services.ps1" -Target all
if errorlevel 1 (
  call :fail "Demarrage des services echoue. Voir scripts\logs\backend.log et frontend.log"
  exit /b 1
)

REM 3. Attendre ports 8000 + 5173 (IPv4 ou IPv6), puis ouvrir le navigateur
powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -Command ^
  "$ok=$false; for($i=0;$i -lt 60;$i++){ $b=$false;$f=$false; foreach($h in @('127.0.0.1','::1')){ try{$c=New-Object Net.Sockets.TcpClient; $c.Connect($h,8000); $c.Close(); $b=$true}catch{}; try{$c=New-Object Net.Sockets.TcpClient; $c.Connect($h,5173); $c.Close(); $f=$true}catch{} }; if($b -and $f){$ok=$true; break}; Start-Sleep -Milliseconds 500 }; if(-not $ok){ exit 1 }"
if errorlevel 1 (
  call :fail "Services non joignables (ports 8000/5173). Voir scripts\logs\"
  exit /b 1
)

start "" "%FRONTEND_URL%"
REM exit (pas exit /b) : ferme la console du raccourci sans tuer les services detaches
exit 0

:fail
set "MSG=%~1"
powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -Command ^
  "Add-Type -AssemblyName System.Windows.Forms; [void][System.Windows.Forms.MessageBox]::Show('%MSG%','PGFE - Erreur',[System.Windows.Forms.MessageBoxButtons]::OK,[System.Windows.Forms.MessageBoxIcon]::Error)"
exit /b 1
