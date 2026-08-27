# PGFE - Detection et installation automatique de Git / Node.js / Laragon
# Appele depuis _install-tools.bat (eventuellement eleve).
# Exit codes: 0 = OK, 1 = echec partiel, 2 = elevation requise (non eleve)

param(
    [switch]$Elevated,
    [switch]$CheckOnly
)

$ErrorActionPreference = "Continue"
$ProgressPreference = "SilentlyContinue"

$TempDir = Join-Path $env:TEMP "pgfe-setup"
New-Item -ItemType Directory -Force -Path $TempDir | Out-Null

function Write-Fr([string]$Msg, [string]$Color = "White") {
    Write-Host $Msg -ForegroundColor $Color
}

function Test-IsAdmin {
    $id = [Security.Principal.WindowsIdentity]::GetCurrent()
    $p = New-Object Security.Principal.WindowsPrincipal($id)
    return $p.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Refresh-SessionPath {
    $machine = [Environment]::GetEnvironmentVariable("Path", "Machine")
    $user = [Environment]::GetEnvironmentVariable("Path", "User")
    $extra = @(
        "C:\Program Files\Git\cmd",
        "C:\Program Files\Git\bin",
        "C:\Program Files\nodejs",
        "C:\laragon\bin",
        "C:\laragon\bin\php",
        "C:\laragon\bin\composer",
        "C:\laragon\bin\nodejs",
        "C:\laragon\bin\git\bin",
        (Join-Path $env:USERPROFILE "laragon\bin"),
        (Join-Path $env:USERPROFILE "laragon\bin\composer"),
        (Join-Path $env:USERPROFILE "laragon\bin\nodejs")
    ) | Where-Object { $_ -and (Test-Path $_) }
    $env:Path = (@($machine, $user) + $extra + @($env:Path) | Where-Object { $_ }) -join ";"
}

function Find-LaragonRoot {
    $candidates = @(
        "C:\laragon",
        (Join-Path $env:USERPROFILE "laragon"),
        "D:\laragon",
        "E:\laragon"
    )
    foreach ($c in $candidates) {
        if (Test-Path (Join-Path $c "laragon.exe")) { return $c }
    }
    return $null
}

function Test-GitOk {
    Refresh-SessionPath
    $g = Get-Command git -ErrorAction SilentlyContinue
    return [bool]$g
}

function Test-NodeOk {
    Refresh-SessionPath
    $n = Get-Command node -ErrorAction SilentlyContinue
    if (-not $n) { return $false }
    try {
        $v = (& node -v 2>$null)
        if ($v -match '^v?(\d+)\.') {
            return [int]$Matches[1] -ge 20
        }
    } catch {}
    return $false
}

function Test-LaragonOk {
    return [bool](Find-LaragonRoot)
}

function Test-PhpOk {
    Refresh-SessionPath
    $laragon = Find-LaragonRoot
    if ($laragon) {
        $phpRoot = Join-Path $laragon "bin\php"
        if (Test-Path $phpRoot) {
            $dirs = Get-ChildItem $phpRoot -Directory -ErrorAction SilentlyContinue |
                Where-Object { $_.Name -like "php-8.4*" -or $_.Name -like "php-8.*" } |
                Sort-Object Name -Descending
            foreach ($d in $dirs) {
                $exe = Join-Path $d.FullName "php.exe"
                if (Test-Path $exe) {
                    $env:Path = "$($d.FullName);$env:Path"
                    return $true
                }
            }
        }
    }
    $p = Get-Command php -ErrorAction SilentlyContinue
    return [bool]$p
}

function Download-File([string]$Url, [string]$OutFile) {
    Write-Fr "  Telechargement : $Url" "Cyan"
    try {
        if (Get-Command curl.exe -ErrorAction SilentlyContinue) {
            & curl.exe -L --retry 3 --retry-delay 2 -o $OutFile $Url
            if ($LASTEXITCODE -eq 0 -and (Test-Path $OutFile) -and ((Get-Item $OutFile).Length -gt 10000)) {
                return $true
            }
        }
    } catch {}
    try {
        Invoke-WebRequest -Uri $Url -OutFile $OutFile -UseBasicParsing
        if ((Test-Path $OutFile) -and ((Get-Item $OutFile).Length -gt 10000)) {
            return $true
        }
    } catch {
        Write-Fr "  [ERREUR] Telechargement echoue : $_" "Red"
    }
    return $false
}

function Get-GitInstallerUrl {
    try {
        $rel = Invoke-RestMethod -Uri "https://api.github.com/repos/git-for-windows/git/releases/latest" -Headers @{ "User-Agent" = "PGFE-Setup" }
        $asset = $rel.assets | Where-Object { $_.name -match '^Git-.*-64-bit\.exe$' } | Select-Object -First 1
        if ($asset) { return $asset.browser_download_url }
    } catch {}
    # Fallback connu (mettre a jour si obsolete) — Git 2.47.1
    return "https://github.com/git-for-windows/git/releases/download/v2.47.1.windows.1/Git-2.47.1-64-bit.exe"
}

function Get-NodeMsiUrl {
    try {
        $html = (Invoke-WebRequest -Uri "https://nodejs.org/dist/latest-v20.x/" -UseBasicParsing).Content
        if ($html -match 'href="(node-v20\.[^"]+-x64\.msi)"') {
            return "https://nodejs.org/dist/latest-v20.x/$($Matches[1])"
        }
    } catch {}
    return "https://nodejs.org/dist/latest-v20.x/node-v20.20.2-x64.msi"
}

function Get-LaragonInstallerUrl {
    try {
        $rel = Invoke-RestMethod -Uri "https://api.github.com/repos/leokhoa/laragon/releases/latest" -Headers @{ "User-Agent" = "PGFE-Setup" }
        $asset = $rel.assets | Where-Object { $_.name -match 'laragon-(wamp|full)\.exe$' } | Select-Object -First 1
        if (-not $asset) {
            $asset = $rel.assets | Where-Object { $_.name -like "*.exe" -and $_.name -notlike "*Updater*" } | Select-Object -First 1
        }
        if ($asset) { return $asset.browser_download_url }
    } catch {}
    # Fallback — Laragon 8.7.0 (mettre a jour via https://github.com/leokhoa/laragon/releases si obsolete)
    return "https://github.com/leokhoa/laragon/releases/download/8.7.0/laragon-wamp.exe"
}

function Try-Winget([string]$Id) {
    $wg = Get-Command winget -ErrorAction SilentlyContinue
    if (-not $wg) { return $false }
    Write-Fr "  Tentative via winget ($Id)..." "Cyan"
    & winget install --id $Id -e --silent --accept-package-agreements --accept-source-agreements --disable-interactivity
    return ($LASTEXITCODE -eq 0)
}

function Install-Git {
    Write-Fr "Telechargement / installation de Git for Windows..." "Yellow"
    if (Try-Winget "Git.Git") {
        Refresh-SessionPath
        if (Test-GitOk) { Write-Fr "  Git installe via winget." "Green"; return $true }
    }
    $url = Get-GitInstallerUrl
    $out = Join-Path $TempDir "Git-64-bit.exe"
    if (-not (Download-File $url $out)) { return $false }
    Write-Fr "Installation de Git (mode silencieux)..." "Yellow"
    $p = Start-Process -FilePath $out -ArgumentList "/VERYSILENT","/NORESTART","/NOCANCEL","/SP-","/CLOSEAPPLICATIONS","/COMPONENTS=icons,ext\reg\shellhere,assoc,assoc_sh" -Wait -PassThru
    Refresh-SessionPath
    if (Test-GitOk) { Write-Fr "  Git installe." "Green"; return $true }
    Write-Fr "  [ATTENTION] Git peut necessiter une nouvelle invite de commandes. Code=$($p.ExitCode)" "Yellow"
    return (Test-Path "C:\Program Files\Git\cmd\git.exe")
}

function Install-Node {
    Write-Fr "Telechargement / installation de Node.js 20 LTS..." "Yellow"
    if (Try-Winget "OpenJS.NodeJS.LTS") {
        Refresh-SessionPath
        if (Test-NodeOk) { Write-Fr "  Node.js installe via winget." "Green"; return $true }
    }
    $url = Get-NodeMsiUrl
    $out = Join-Path $TempDir "node-x64.msi"
    if (-not (Download-File $url $out)) { return $false }
    Write-Fr "Installation de Node.js (msiexec /qn)..." "Yellow"
    $p = Start-Process -FilePath "msiexec.exe" -ArgumentList "/i `"$out`" /qn /norestart" -Wait -PassThru
    Refresh-SessionPath
    if (Test-NodeOk -or (Test-Path "C:\Program Files\nodejs\node.exe")) {
        Write-Fr "  Node.js installe." "Green"
        return $true
    }
    Write-Fr "  [ERREUR] Installation Node echouee (code $($p.ExitCode))." "Red"
    return $false
}

function Install-Laragon {
    Write-Fr "Telechargement / installation de Laragon..." "Yellow"
    if (Try-Winget "LeNgocKhoa.Laragon") {
        Refresh-SessionPath
        if (Test-LaragonOk) { Write-Fr "  Laragon installe via winget." "Green"; return $true }
    }
    $url = Get-LaragonInstallerUrl
    $out = Join-Path $TempDir "laragon-installer.exe"
    if (-not (Download-File $url $out)) {
        Write-Fr "  [ERREUR] Telechargement Laragon echoue." "Red"
        Write-Fr "  Telechargez manuellement : https://laragon.org/download/ ou" "Yellow"
        Write-Fr "  https://github.com/leokhoa/laragon/releases" "Yellow"
        return $false
    }
    Write-Fr "Installation de Laragon (mode silencieux)..." "Yellow"
    $argsSilent = "/VERYSILENT /SUPPRESSMSGBOXES /NORESTART /DIR=`"C:\laragon`""
    $p = Start-Process -FilePath $out -ArgumentList $argsSilent -Wait -PassThru
    Refresh-SessionPath
    if (Test-LaragonOk) {
        Write-Fr "  Laragon installe (silencieux)." "Green"
        return $true
    }
    Write-Fr "  Mode silencieux echoue (code $($p.ExitCode)). Ouverture de l installateur..." "Yellow"
    Write-Fr "  Terminez l installation manuellement, puis revenez ici." "Yellow"
    $p2 = Start-Process -FilePath $out -Wait -PassThru
    Refresh-SessionPath
    if (Test-LaragonOk) {
        Write-Fr "  Laragon detecte apres installation manuelle." "Green"
        return $true
    }
    Write-Fr "  [ERREUR] Laragon toujours introuvable. Code=$($p2.ExitCode)" "Red"
    return $false
}

# --- Main ---
Refresh-SessionPath

$needGit = -not (Test-GitOk)
$needNode = -not (Test-NodeOk)
$needLaragon = -not (Test-LaragonOk)

Write-Fr ""
Write-Fr "=== Verification des outils ===" "Cyan"
Write-Fr ("  Git     : " + $(if ($needGit) { "MANQUANT" } else { "OK" }))
Write-Fr ("  Node 20+: " + $(if ($needNode) { "MANQUANT" } else { "OK ($(& node -v 2>$null))" }))
Write-Fr ("  Laragon : " + $(if ($needLaragon) { "MANQUANT" } else { "OK ($(Find-LaragonRoot))" }))

if ($CheckOnly) {
    if ($needGit -or $needNode -or $needLaragon) { exit 1 }
    exit 0
}

if (-not ($needGit -or $needNode -or $needLaragon)) {
    Write-Fr "Tous les outils requis sont deja presents." "Green"
    exit 0
}

$needAdmin = $needGit -or $needNode -or $needLaragon
if ($needAdmin -and -not (Test-IsAdmin) -and -not $Elevated) {
    Write-Fr ""
    Write-Fr "Des installateurs necessitent des droits administrateur." "Yellow"
    Write-Fr "Une fenetre UAC va s ouvrir - acceptez pour continuer." "Yellow"
    exit 2
}

$ok = $true
if ($needGit) {
    if (-not (Install-Git)) { $ok = $false }
}
if ($needNode) {
    if (-not (Install-Node)) { $ok = $false }
}
if ($needLaragon) {
    if (-not (Install-Laragon)) { $ok = $false }
}

Refresh-SessionPath
Write-Fr ""
if ($ok) {
    Write-Fr "Installation des outils terminee." "Green"
    exit 0
}
Write-Fr "Certains outils n ont pas pu etre installes automatiquement." "Red"
exit 1
