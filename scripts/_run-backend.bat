@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE Backend
call "%~dp0_env.bat"
cd /d "%BACKEND%"
echo PGFE Backend - http://%BACKEND_HOST%:%BACKEND_PORT%
echo.
php artisan serve --host=%BACKEND_HOST% --port=%BACKEND_PORT%
if errorlevel 1 (
  echo.
  echo [ERREUR] Echec du backend.
  pause
)
