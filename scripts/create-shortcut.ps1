# Creates PGFE.lnk pointing to start-pgfe.bat
# Usage:
#   .\create-shortcut.ps1
#   .\create-shortcut.ps1 -AlsoDesktop

param(
    [switch]$AlsoDesktop,
    [switch]$AskDesktop
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

function New-PgfeShortcut([string]$TargetPath) {
    $shell = New-Object -ComObject WScript.Shell
    $sc = $shell.CreateShortcut($TargetPath)
    $sc.TargetPath = $startBat
    $sc.WorkingDirectory = $root
    $sc.WindowStyle = 1
    $sc.Description = "Demarrer PGFE (backend + frontend)"
    $icon = Get-PgfeIcon
    if ($icon) {
        if ($icon -like "*.dll") {
            $sc.IconLocation = "$icon,13"
        } else {
            $sc.IconLocation = $icon
        }
    }
    $sc.Save()
    Write-Host "Raccourci cree : $TargetPath"
}

New-PgfeShortcut -TargetPath $shortcutPath

$copyDesktop = $AlsoDesktop.IsPresent
if ($AskDesktop -and -not $AlsoDesktop) {
    $answer = Read-Host "Copier aussi le raccourci sur le Bureau ? (O/N)"
    if ($answer -match '^[OoYy]') { $copyDesktop = $true }
}

if ($copyDesktop) {
    $desktop = [Environment]::GetFolderPath("Desktop")
    $desktopLnk = Join-Path $desktop "PGFE.lnk"
    New-PgfeShortcut -TargetPath $desktopLnk
}

Write-Host ""
Write-Host "Double-cliquez sur PGFE.lnk pour lancer l'application."
exit 0
