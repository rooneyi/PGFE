@echo off
REM Shared environment for PGFE launcher scripts.
REM Call with: call "%~dp0_env.bat"

set "SCRIPTS_DIR=%~dp0"
if "%SCRIPTS_DIR:~-1%"=="\" set "SCRIPTS_DIR=%SCRIPTS_DIR:~0,-1%"

pushd "%SCRIPTS_DIR%\.." >nul
set "ROOT=%CD%"
popd >nul

set "BACKEND=%ROOT%\PGFEv2-ENABEL"
set "FRONTEND=%ROOT%\PGFEv2-ENABEL-FRONT"
set "BACKEND_HOST=127.0.0.1"
set "BACKEND_PORT=8000"
set "FRONTEND_PORT=5173"
set "BACKEND_URL=http://127.0.0.1:8000"
set "FRONTEND_URL=http://localhost:5173"
set "DB_NAME=pgfev2"

set "LARAGON_EXE="
set "LARAGON_ROOT="
set "PHP_DIR="
set "MYSQL_EXE="
set "COMPOSER_BAT="

call :detect_laragon
call :detect_php
call :detect_mysql
call :detect_composer
call :apply_path
goto :eof

:detect_laragon
if exist "C:\laragon\laragon.exe" (
  set "LARAGON_EXE=C:\laragon\laragon.exe"
  set "LARAGON_ROOT=C:\laragon"
  goto :eof
)
if exist "%USERPROFILE%\laragon\laragon.exe" (
  set "LARAGON_EXE=%USERPROFILE%\laragon\laragon.exe"
  set "LARAGON_ROOT=%USERPROFILE%\laragon"
  goto :eof
)
if exist "D:\laragon\laragon.exe" (
  set "LARAGON_EXE=D:\laragon\laragon.exe"
  set "LARAGON_ROOT=D:\laragon"
  goto :eof
)
if exist "E:\laragon\laragon.exe" (
  set "LARAGON_EXE=E:\laragon\laragon.exe"
  set "LARAGON_ROOT=E:\laragon"
  goto :eof
)
goto :eof

:detect_php
REM Prefer PHP 8.4+ (required by Laravel app). Fall back to other Laragon builds, then system.
if defined LARAGON_ROOT if exist "%LARAGON_ROOT%\bin\php" (
  for /f "delims=" %%D in ('dir /b /ad /o-n "%LARAGON_ROOT%\bin\php\php-8.4*" 2^>nul') do (
    if exist "%LARAGON_ROOT%\bin\php\%%D\php.exe" (
      set "PHP_DIR=%LARAGON_ROOT%\bin\php\%%D"
      goto :php_version_ok_or_fallback
    )
  )
)

REM System PHP 8.4+ (common if installed outside Laragon)
REM Note: no parentheses around php -r (cmd would misparse ')' inside the string)
if exist "C:\Program Files\PHP\php.exe" goto :check_system_php
goto :php_fallback

:check_system_php
"C:\Program Files\PHP\php.exe" -r "exit(version_compare(PHP_VERSION,'8.4.0','>=')?0:1);" >nul 2>&1
if errorlevel 1 goto :php_fallback
set "PHP_DIR=C:\Program Files\PHP"
goto :eof

:php_fallback

if defined LARAGON_ROOT if exist "%LARAGON_ROOT%\bin\php" (
  for /f "delims=" %%D in ('dir /b /ad /o-n "%LARAGON_ROOT%\bin\php\php-*" 2^>nul') do (
    if exist "%LARAGON_ROOT%\bin\php\%%D\php.exe" (
      set "PHP_DIR=%LARAGON_ROOT%\bin\php\%%D"
      goto :eof
    )
  )
)
goto :eof

:php_version_ok_or_fallback
goto :eof

:detect_mysql
if not defined LARAGON_ROOT goto :eof
if exist "%LARAGON_ROOT%\bin\mysql" (
  for /f "delims=" %%D in ('dir /b /ad /o-n "%LARAGON_ROOT%\bin\mysql\mysql-*" 2^>nul') do (
    if exist "%LARAGON_ROOT%\bin\mysql\%%D\bin\mysql.exe" (
      set "MYSQL_EXE=%LARAGON_ROOT%\bin\mysql\%%D\bin\mysql.exe"
      goto :eof
    )
  )
)
goto :eof

:detect_composer
if defined LARAGON_ROOT (
  if exist "%LARAGON_ROOT%\bin\composer\composer.bat" (
    set "COMPOSER_BAT=%LARAGON_ROOT%\bin\composer\composer.bat"
    goto :eof
  )
  if exist "%LARAGON_ROOT%\bin\composer\composer.phar" (
    set "COMPOSER_BAT=%LARAGON_ROOT%\bin\composer\composer.phar"
    goto :eof
  )
)
where composer >nul 2>&1
if not errorlevel 1 (
  for /f "delims=" %%C in ('where composer 2^>nul') do (
    set "COMPOSER_BAT=%%C"
    goto :eof
  )
)
goto :eof

:apply_path
if defined PHP_DIR set "PATH=%PHP_DIR%;%PATH%"
if defined LARAGON_ROOT (
  if exist "%LARAGON_ROOT%\bin\composer" set "PATH=%LARAGON_ROOT%\bin\composer;%PATH%"
  if exist "%LARAGON_ROOT%\bin\nodejs" set "PATH=%LARAGON_ROOT%\bin\nodejs;%PATH%"
  if exist "%LARAGON_ROOT%\bin\git\bin" set "PATH=%LARAGON_ROOT%\bin\git\bin;%PATH%"
)
goto :eof
