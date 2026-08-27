@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE Frontend
call "%~dp0_env.bat"
cd /d "%FRONTEND%"
echo PGFE Frontend - %FRONTEND_URL%
echo.
npm run dev
if errorlevel 1 (
  echo.
  echo [ERREUR] Echec du frontend.
  pause
)
