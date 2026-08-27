@echo off
REM Detecte et installe Git / Node.js / Laragon si manquants.
REM Usage: call "%~dp0_install-tools.bat"
REM Exit: 0 OK, 1 echec

setlocal EnableExtensions
chcp 65001 >nul

set "PS1=%~dp0_install-tools.ps1"
if not exist "%PS1%" (
  echo [ERREUR] _install-tools.ps1 introuvable.
  exit /b 1
)

REM Evite une boucle UAC infinie
if /I "%~1"=="--elevated" goto :run_elevated

powershell -NoProfile -ExecutionPolicy Bypass -File "%PS1%"
set "RC=%ERRORLEVEL%"

if "%RC%"=="0" (
  endlocal & exit /b 0
)
if "%RC%"=="2" (
  echo.
  echo Elevation administrateur requise pour installer les outils.
  echo Acceptez la fenetre UAC...
  powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "Start-Process -FilePath '%ComSpec%' -ArgumentList '/c \"\"%~f0\"\" --elevated' -Verb RunAs -Wait"
  REM Re-verifier apres elevation
  powershell -NoProfile -ExecutionPolicy Bypass -File "%PS1%" -CheckOnly
  if errorlevel 1 (
    echo [ATTENTION] Certains outils manquent encore apres installation.
    endlocal & exit /b 1
  )
  endlocal & exit /b 0
)

echo [ERREUR] Echec de l'installation automatique des outils.
endlocal & exit /b 1

:run_elevated
powershell -NoProfile -ExecutionPolicy Bypass -File "%PS1%" -Elevated
exit /b %ERRORLEVEL%
