@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE - Creer le raccourci

echo.
echo Creation du raccourci PGFE.lnk sur le Bureau...
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0create-shortcut.ps1" -AlsoDesktop -OpenDesktop
if errorlevel 1 (
  echo [ERREUR] Echec de la creation du raccourci.
  pause
  exit /b 1
)

echo.
pause
exit /b 0
