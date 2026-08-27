@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE - Installation initiale

call "%~dp0_env.bat"

echo.
echo ========================================
echo   PGFE - Installation initiale (1 fois)
echo ========================================
echo   Projet : %ROOT%
echo.

if not exist "%BACKEND%\artisan" (
  echo [ERREUR] Backend introuvable : %BACKEND%
  echo Lancez INSTALLER-PGFE.bat depuis la racine, ou clonez le depot.
  pause
  exit /b 1
)
if not exist "%FRONTEND%\package.json" (
  echo [ERREUR] Frontend introuvable : %FRONTEND%
  echo Lancez INSTALLER-PGFE.bat depuis la racine, ou clonez le depot.
  pause
  exit /b 1
)

REM --- Etape 0 : outils manquants (Git / Node / Laragon) ---
echo.
echo --- Etape 0 : verification / installation des outils ---
call "%~dp0_install-tools.bat"
if errorlevel 1 (
  echo [ATTENTION] Installation auto incomplete. Poursuite si possible...
)
REM Recharger detection apres installs
call "%~dp0_env.bat"

REM Refresh PATH machine/user dans cette session
for /f "tokens=2*" %%A in ('reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v Path 2^>nul') do set "PATH=%%B;%PATH%"
for /f "tokens=2*" %%A in ('reg query "HKCU\Environment" /v Path 2^>nul') do set "PATH=%%B;%PATH%"
if exist "C:\Program Files\Git\cmd" set "PATH=C:\Program Files\Git\cmd;%PATH%"
if exist "C:\Program Files\nodejs" set "PATH=C:\Program Files\nodejs;%PATH%"

REM Start Laragon so MySQL is available for migrate/seed
if defined LARAGON_EXE (
  tasklist /FI "IMAGENAME eq laragon.exe" 2>nul | find /I "laragon.exe" >nul
  if errorlevel 1 (
    echo Demarrage de Laragon...
    start "" "%LARAGON_EXE%"
    echo Attente des services...
    ping -n 13 127.0.0.1 >nul
  ) else (
    echo Laragon deja actif.
  )
) else (
  echo [ATTENTION] Laragon non detecte. MySQL peut echouer.
)

where php >nul 2>&1
if errorlevel 1 (
  echo [ERREUR] php introuvable apres installation. Relancez setup-pgfe.bat ou ouvrez une nouvelle invite.
  pause
  exit /b 1
)

php -r "exit(version_compare(PHP_VERSION,'8.4.0','>=')?0:1);" >nul 2>&1
if errorlevel 1 (
  echo [ERREUR] PHP 8.4+ requis. Version actuelle :
  php -v
  echo          Laragon -^> Menu PHP -^> choisissez PHP 8.4.
  pause
  exit /b 1
)

where npm >nul 2>&1
if errorlevel 1 (
  echo [ERREUR] npm introuvable. Relancez setup-pgfe.bat apres install Node.
  pause
  exit /b 1
)

echo.
echo --- Backend Laravel ---
pushd "%BACKEND%"

if not exist "vendor\" (
  echo [1/6] composer install...
  if defined COMPOSER_BAT (
    call composer install --no-interaction
  ) else (
    composer install --no-interaction
  )
  if errorlevel 1 (
    echo [ERREUR] composer install a echoue.
    popd
    pause
    exit /b 1
  )
) else (
  echo [1/6] vendor/ deja present - composer install ignore.
)

if not exist ".env" (
  echo [2/6] Copie de .env.example vers .env...
  copy /Y ".env.example" ".env" >nul
) else (
  echo [2/6] .env deja present.
)

echo [3/6] Generation de la cle applicative...
php artisan key:generate --force >nul 2>&1

echo [4/6] Creation de la base de donnees "%DB_NAME%" ^(si possible^)...
if defined MYSQL_EXE (
  "%MYSQL_EXE%" -u root -e "CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
  if errorlevel 1 (
    echo       [ATTENTION] Impossible de creer la base via mysql CLI.
    echo       Creez manuellement "%DB_NAME%" dans phpMyAdmin / HeidiSQL.
  ) else (
    echo       Base "%DB_NAME%" OK.
  )
) else (
  echo       [ATTENTION] mysql.exe Laragon introuvable.
  echo       Creez manuellement la base "%DB_NAME%" si besoin.
)

echo [5/6] Migrations...
php artisan migrate --force
if errorlevel 1 (
  echo [ERREUR] migrate a echoue. Verifiez MySQL ^(Laragon Start All^) et .env
  popd
  pause
  exit /b 1
)

echo [6/6] Seeders...
php artisan db:seed --force
if errorlevel 1 (
  echo [ATTENTION] db:seed a echoue. Vous pouvez reessayer plus tard.
)

popd

echo.
echo --- Frontend Vue ---
pushd "%FRONTEND%"

if not exist "node_modules\" (
  echo [1/2] npm install...
  call npm install
  if errorlevel 1 (
    echo [ERREUR] npm install a echoue.
    popd
    pause
    exit /b 1
  )
) else (
  echo [1/2] node_modules/ deja present - npm install ignore.
)

if not exist ".env.local" (
  if exist ".env.example" (
    echo [2/2] Copie de .env.example vers .env.local...
    copy /Y ".env.example" ".env.local" >nul
  ) else (
    echo [2/2] Creation de .env.local par defaut...
    (
      echo VITE_API_BASE_URL=http://localhost:8000/api/v1/
      echo VITE_SANCTUM_BASE_URL=http://localhost:8000/
    ) > ".env.local"
  )
) else (
  echo [2/2] .env.local deja present.
)

popd

echo.
echo --- Raccourci Bureau ---
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0create-shortcut.ps1" -AlsoDesktop -OpenDesktop
if errorlevel 1 (
  echo [ATTENTION] Creation du raccourci echouee. Relancez create-shortcut.bat
)

echo.
echo ========================================
echo   Installation terminee.
echo.
echo   Usage quotidien :
echo     Double-cliquez sur PGFE.lnk sur le Bureau
echo.
echo   Ou : scripts\start-pgfe.bat
echo   ^(backend/frontend demarrent sans fenetre CMD^)
echo ========================================
echo.
pause
exit /b 0
