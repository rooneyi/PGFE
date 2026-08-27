@echo off
setlocal EnableExtensions
chcp 65001 >nul
title PGFE - Installateur

REM ============================================================================
REM  INSTALLER-PGFE.bat
REM  - Installe Git / Node / Laragon si manquants
REM  - Si lance HORS d'un clone PGFE : clone vers %%USERPROFILE%%\Desktop\PGFE
REM    (repli : %%USERPROFILE%%\PGFE)
REM  - Si deja dans un clone (PGFEv2-ENABEL present) : utilise ce dossier
REM  - Lance scripts\setup-pgfe.bat puis cree le raccourci Bureau
REM ============================================================================

set "REPO_URL=https://github.com/rooneyi/PGFE.git"
set "DEST="
set "HERE=%~dp0"
if "%HERE:~-1%"=="\" set "HERE=%HERE:~0,-1%"

echo.
echo ========================================
echo   PGFE - Installateur automatique
echo ========================================
echo.

REM Detecter si on est deja dans (ou a cote de) un monorepo PGFE
set "ROOT="
if exist "%HERE%\PGFEv2-ENABEL\artisan" if exist "%HERE%\PGFEv2-ENABEL-FRONT\package.json" (
  set "ROOT=%HERE%"
  goto :have_root
)
if exist "%HERE%\..\PGFEv2-ENABEL\artisan" if exist "%HERE%\..\PGFEv2-ENABEL-FRONT\package.json" (
  pushd "%HERE%\.." >nul
  set "ROOT=%CD%"
  popd >nul
  goto :have_root
)

REM Pas de clone local : preparer destination
set "DEST=%USERPROFILE%\Desktop\PGFE"
if not exist "%USERPROFILE%\Desktop\" (
  if exist "%USERPROFILE%\OneDrive\Desktop\" (
    set "DEST=%USERPROFILE%\OneDrive\Desktop\PGFE"
  ) else (
    set "DEST=%USERPROFILE%\PGFE"
  )
)

echo Le projet sera clone dans :
echo   %DEST%
echo   ^(URL : %REPO_URL%^)
echo.

REM Besoin de Git — installer outils d'abord (copie minimale dans TEMP si pas de scripts)
goto :need_tools_then_clone

:have_root
echo Projet detecte : %ROOT%
echo ^(pas de clone — utilisation du dossier existant^)
echo.
set "SCRIPTS=%ROOT%\scripts"
goto :install_tools

:need_tools_then_clone
REM Si ce bat est a la racine du repo avec scripts\, utiliser ces scripts pour installer
if exist "%HERE%\scripts\_install-tools.bat" (
  set "SCRIPTS=%HERE%\scripts"
  call "%SCRIPTS%\_install-tools.bat"
) else if exist "%HERE%\_install-tools.bat" (
  set "SCRIPTS=%HERE%"
  call "%SCRIPTS%\_install-tools.bat"
) else (
  echo Installation des outils via PowerShell embarque...
  powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "winget install --id Git.Git -e --silent --accept-package-agreements --accept-source-agreements 2>$null; winget install --id OpenJS.NodeJS.LTS -e --silent --accept-package-agreements --accept-source-agreements 2>$null"
)

REM Refresh PATH pour git
for /f "tokens=2*" %%A in ('reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v Path 2^>nul') do set "PATH=%%B;%PATH%"
for /f "tokens=2*" %%A in ('reg query "HKCU\Environment" /v Path 2^>nul') do set "PATH=%%B;%PATH%"
if exist "C:\Program Files\Git\cmd" set "PATH=C:\Program Files\Git\cmd;%PATH%"

where git >nul 2>&1
if errorlevel 1 (
  echo [ERREUR] Git introuvable. Installez Git puis relancez.
  pause
  exit /b 1
)

if exist "%DEST%\PGFEv2-ENABEL\artisan" (
  echo Dossier deja present : %DEST%
  set "ROOT=%DEST%"
) else (
  if exist "%DEST%\" (
    echo [ATTENTION] %DEST% existe mais n'est pas un clone PGFE complet.
    set "DEST=%USERPROFILE%\PGFE"
    echo Repli vers : %DEST%
  )
  echo Telechargement du projet ^(git clone^)...
  if exist "%DEST%\" (
    if exist "%DEST%\PGFEv2-ENABEL\artisan" (
      set "ROOT=%DEST%"
      goto :after_clone
    )
  )
  git clone "%REPO_URL%" "%DEST%"
  if errorlevel 1 (
    echo [ERREUR] git clone a echoue.
    pause
    exit /b 1
  )
  set "ROOT=%DEST%"
)

:after_clone
set "SCRIPTS=%ROOT%\scripts"
goto :run_setup

:install_tools
call "%SCRIPTS%\_install-tools.bat"
if errorlevel 1 (
  echo [ATTENTION] Outils incomplets — poursuite si possible.
)

:run_setup
echo.
echo --- Lancement de setup-pgfe.bat ---
call "%SCRIPTS%\setup-pgfe.bat"
set "RC=%ERRORLEVEL%"

echo.
if "%RC%"=="0" (
  echo Installateur termine. Utilisez PGFE.lnk sur le Bureau.
) else (
  echo Installateur termine avec des alertes ^(code %RC%^).
)
exit /b %RC%
