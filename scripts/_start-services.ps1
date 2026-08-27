# Demarre backend + frontend en fenetres cachees, logs dans scripts\logs\
param(
    [ValidateSet("backend", "frontend", "all")]
    [string]$Target = "all"
)

$ErrorActionPreference = "Continue"
$scriptsDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = (Resolve-Path (Join-Path $scriptsDir "..")).Path
$backend = Join-Path $root "PGFEv2-ENABEL"
$frontend = Join-Path $root "PGFEv2-ENABEL-FRONT"
$logDir = Join-Path $scriptsDir "logs"
New-Item -ItemType Directory -Force -Path $logDir | Out-Null

# Recharge PATH (Machine + User + chemins connus)
$machine = [Environment]::GetEnvironmentVariable("Path", "Machine")
$user = [Environment]::GetEnvironmentVariable("Path", "User")
$extra = New-Object System.Collections.Generic.List[string]
@(
    "C:\Program Files\Git\cmd",
    "C:\Program Files\nodejs",
    "C:\laragon\bin",
    "C:\laragon\bin\composer",
    "C:\laragon\bin\nodejs",
    "C:\laragon\bin\git\bin"
) | ForEach-Object { if (Test-Path $_) { [void]$extra.Add($_) } }

# PHP Laragon 8.x
foreach ($base in @("C:\laragon", (Join-Path $env:USERPROFILE "laragon"), "D:\laragon", "E:\laragon")) {
    if (-not $base) { continue }
    if (-not (Test-Path -LiteralPath $base)) { continue }
    $phpBin = Join-Path $base "bin\php"
    if (Test-Path -LiteralPath $phpBin) {
        $dirs = Get-ChildItem $phpBin -Directory -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -like "php-8.*" } |
            Sort-Object Name -Descending
        foreach ($d in $dirs) { [void]$extra.Add($d.FullName) }
        foreach ($sub in @("bin\composer", "bin\nodejs", "bin\git\bin")) {
            $p = Join-Path $base $sub
            if (Test-Path -LiteralPath $p) { [void]$extra.Add($p) }
        }
    }
}
$env:Path = (@($machine, $user) + $extra.ToArray() + @($env:Path) | Where-Object { $_ }) -join ";"
$ErrorActionPreference = "Stop"

function Test-PortInUse([int]$Port) {
    try {
        $c = New-Object System.Net.Sockets.TcpClient("127.0.0.1", $Port)
        $c.Close()
        return $true
    } catch {
        return $false
    }
}

function Start-HiddenCmd([string]$WorkDir, [string]$CommandLine, [string]$LogFile, [string]$PidFile) {
    # cmd.exe cache : redirige stdout/stderr vers le fichier log
    $arg = "/c cd /d `"$WorkDir`" && ($CommandLine) >> `"$LogFile`" 2>&1"
    $p = Start-Process -FilePath "$env:ComSpec" -ArgumentList $arg -WindowStyle Hidden -PassThru
    if ($PidFile) {
        Set-Content -Path $PidFile -Value $p.Id -Encoding ASCII
    }
    return $p
}

$started = @()

if ($Target -eq "backend" -or $Target -eq "all") {
    if (Test-PortInUse 8000) {
        Write-Host "Backend deja actif (port 8000)."
    } else {
        $php = Get-Command php -ErrorAction SilentlyContinue
        if (-not $php) { throw "php introuvable dans le PATH" }
        $log = Join-Path $logDir "backend.log"
        $pidFile = Join-Path $logDir "backend.pid"
        "--- $(Get-Date -Format o) demarrage backend ---" | Out-File -FilePath $log -Append -Encoding utf8
        $null = Start-HiddenCmd $backend "php artisan serve --host=127.0.0.1 --port=8000" $log $pidFile
        $started += "backend"
    }
}

if ($Target -eq "frontend" -or $Target -eq "all") {
    if (Test-PortInUse 5173) {
        Write-Host "Frontend deja actif (port 5173)."
    } else {
        $npm = Get-Command npm.cmd -ErrorAction SilentlyContinue
        if (-not $npm) { $npm = Get-Command npm -ErrorAction SilentlyContinue }
        if (-not $npm) { throw "npm introuvable dans le PATH" }
        $log = Join-Path $logDir "frontend.log"
        $pidFile = Join-Path $logDir "frontend.pid"
        "--- $(Get-Date -Format o) demarrage frontend ---" | Out-File -FilePath $log -Append -Encoding utf8
        $null = Start-HiddenCmd $frontend "npm run dev" $log $pidFile
        $started += "frontend"
    }
}

if ($started.Count -gt 0) {
    Write-Host ("Services lances en arriere-plan : " + ($started -join ", "))
}
exit 0
