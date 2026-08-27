# Cree PGFE.lnk (racine projet + Bureau utilisateur, OneDrive inclus)
# Usage:
#   .\create-shortcut.ps1
#   .\create-shortcut.ps1 -AlsoDesktop
#   .\create-shortcut.ps1 -AlsoDesktop -OpenDesktop

param(
    [switch]$AlsoDesktop,
    [switch]$AskDesktop,
    [switch]$OpenDesktop
)

$ErrorActionPreference = "Stop"

$scriptsDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = (Resolve-Path (Join-Path $scriptsDir "..")).Path
$startBat = Join-Path $scriptsDir "start-pgfe.bat"
$shortcutPath = Join-Path $root "PGFE.lnk"

if (-not (Test-Path $startBat)) {
    Write-Host "[ERREUR] start-pgfe.bat introuvable : $startBat" -ForegroundColor Red
    exit 1
}

function Get-PgfeIcon {
    $candidates = @(
        (Join-Path $root "PGFEv2-ENABEL\public\favicon.ico"),
        (Join-Path $root "PGFEv2-ENABEL-FRONT\public\favicon.ico"),
        "C:\laragon\laragon.exe",
        (Join-Path $env:USERPROFILE "laragon\laragon.exe"),
        "$env:SystemRoot\System32\shell32.dll"
    )
    foreach ($c in $candidates) {
        if (Test-Path $c) { return $c }
    }
    return $null
}

function Get-UserDesktopPaths {
    $paths = New-Object System.Collections.Generic.List[string]
    $add = {
        param($p)
        if ($p -and (Test-Path $p) -and -not $paths.Contains($p)) { [void]$paths.Add($p) }
    }

    & $add ([Environment]::GetFolderPath("Desktop"))
    & $add ([Environment]::GetFolderPath("DesktopDirectory"))

    # OneDrive / dossiers connus
    @(
        (Join-Path $env:USERPROFILE "Desktop"),
        (Join-Path $env:USERPROFILE "OneDrive\Desktop"),
        (Join-Path $env:USERPROFILE "OneDrive\Bureau")
    ) | ForEach-Object { & $add $_ }

    Get-ChildItem -Path $env:USERPROFILE -Directory -Filter "OneDrive*" -ErrorAction SilentlyContinue | ForEach-Object {
        & $add (Join-Path $_.FullName "Desktop")
        & $add (Join-Path $_.FullName "Bureau")
    }

    # Registry User Shell Folders (Desktop redirige)
    try {
        $reg = Get-ItemProperty -Path "HKCU:\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders" -ErrorAction SilentlyContinue
        if ($reg -and $reg.Desktop) {
            $expanded = [Environment]::ExpandEnvironmentVariables($reg.Desktop)
            & $add $expanded
        }
    } catch {
        # ignore registry errors
    }

    return $paths
}

function New-PgfeShortcut([string]$TargetPath) {
    $dir = Split-Path -Parent $TargetPath
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
    }
    $shell = New-Object -ComObject WScript.Shell
    $sc = $shell.CreateShortcut($TargetPath)
    $sc.TargetPath = $startBat
    $sc.WorkingDirectory = $scriptsDir
    $sc.WindowStyle = 7  # Minimized - le lanceur quitte vite
    $sc.Description = "Demarrer PGFE (backend + frontend caches)"
    $icon = Get-PgfeIcon
    if ($icon) {
        if ($icon -like "*.dll") {
            $sc.IconLocation = "$icon,13"
        } else {
            $sc.IconLocation = $icon
        }
    }
    $sc.Save()

    if (-not (Test-Path $TargetPath)) {
        throw "Echec ecriture raccourci : $TargetPath"
    }
    Write-Host "Raccourci cree : $TargetPath" -ForegroundColor Green
    return $TargetPath
}

$created = @()
$created += New-PgfeShortcut -TargetPath $shortcutPath

$copyDesktop = $AlsoDesktop.IsPresent
if ($AskDesktop -and -not $AlsoDesktop) {
    $answer = Read-Host "Copier aussi le raccourci sur le Bureau ? (O/N)"
    if ($answer -match '^[OoYy]') { $copyDesktop = $true }
}

# Par defaut lors du setup : toujours Bureau
if (-not $AskDesktop) { $copyDesktop = $true }

$desktopCreated = $null
if ($copyDesktop) {
    $desktops = Get-UserDesktopPaths
    if ($desktops.Count -eq 0) {
        Write-Host "[ATTENTION] Aucun dossier Bureau detecte." -ForegroundColor Yellow
    }
    foreach ($desktop in $desktops) {
        try {
            $lnk = New-PgfeShortcut -TargetPath (Join-Path $desktop "PGFE.lnk")
            if (-not $desktopCreated) { $desktopCreated = $lnk }
            $created += $lnk
        } catch {
            Write-Host "[ATTENTION] Impossible d ecrire sur : $desktop - $_" -ForegroundColor Yellow
        }
    }

    # Public Desktop (souvent visible pour tous les users)
    $publicDesktop = [Environment]::GetFolderPath("CommonDesktopDirectory")
    if ($publicDesktop -and (Test-Path $publicDesktop)) {
        try {
            $pubLnk = Join-Path $publicDesktop "PGFE.lnk"
            # Peut necessiter admin - best effort
            $created += New-PgfeShortcut -TargetPath $pubLnk
            if (-not $desktopCreated) { $desktopCreated = $pubLnk }
        } catch {
            Write-Host "[INFO] Bureau public non accessible (normal sans admin)." -ForegroundColor DarkGray
        }
    }
}

Write-Host ""
if ($desktopCreated) {
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  Raccourci Bureau :" -ForegroundColor Cyan
    Write-Host "  $desktopCreated" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    if ($OpenDesktop) {
        Start-Process explorer.exe -ArgumentList "/select,`"$desktopCreated`""
    }
} else {
    Write-Host "Raccourci projet : $shortcutPath"
}

Write-Host "Double-cliquez sur PGFE.lnk pour lancer l'application."
exit 0
