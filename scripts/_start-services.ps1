# Demarre backend + frontend en fenetres cachees, logs dans scripts\logs\
# Les processus sont DETACHES du lanceur (survivent a la fermeture du raccourci).
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

# Native CreateProcess: break away from console + job so shortcut close does not kill services
if (-not ("PgfeDetach.Start" -as [type])) {
    Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;
namespace PgfeDetach {
  public static class Start {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public struct STARTUPINFO {
      public int cb;
      public string lpReserved, lpDesktop, lpTitle;
      public int dwX, dwY, dwXSize, dwYSize, dwXCountChars, dwYCountChars, dwFillAttribute, dwFlags;
      public short wShowWindow, cbReserved2;
      public IntPtr lpReserved2, hStdInput, hStdOutput, hStdError;
    }
    [StructLayout(LayoutKind.Sequential)]
    public struct PROCESS_INFORMATION {
      public IntPtr hProcess, hThread;
      public int dwProcessId, dwThreadId;
    }
    [DllImport("kernel32.dll", CharSet = CharSet.Unicode, SetLastError = true)]
    static extern bool CreateProcess(string appName, System.Text.StringBuilder cmdLine, IntPtr procAttr, IntPtr threadAttr,
      bool inherit, uint flags, IntPtr env, string dir, ref STARTUPINFO si, out PROCESS_INFORMATION pi);
    [DllImport("kernel32.dll")] static extern bool CloseHandle(IntPtr h);
    const uint CREATE_NEW_PROCESS_GROUP = 0x00000200;
    const uint CREATE_NO_WINDOW = 0x08000000;
    const uint CREATE_BREAKAWAY_FROM_JOB = 0x01000000;

    public static int Detached(string commandLine, string workingDir) {
      var si = new STARTUPINFO();
      si.cb = Marshal.SizeOf(typeof(STARTUPINFO));
      PROCESS_INFORMATION pi;
      // Prefer breakaway from job (Explorer/shortcut/terminal job objects)
      uint flags = CREATE_NO_WINDOW | CREATE_NEW_PROCESS_GROUP | CREATE_BREAKAWAY_FROM_JOB;
      // CreateProcessW may mutate the command line buffer
      var cmd = new System.Text.StringBuilder(commandLine);
      if (!CreateProcess(null, cmd, IntPtr.Zero, IntPtr.Zero, false, flags, IntPtr.Zero, workingDir, ref si, out pi)) {
        flags = CREATE_NO_WINDOW | CREATE_NEW_PROCESS_GROUP;
        cmd = new System.Text.StringBuilder(commandLine);
        if (!CreateProcess(null, cmd, IntPtr.Zero, IntPtr.Zero, false, flags, IntPtr.Zero, workingDir, ref si, out pi))
          throw new System.ComponentModel.Win32Exception(Marshal.GetLastWin32Error());
      }
      CloseHandle(pi.hThread);
      CloseHandle(pi.hProcess);
      return pi.dwProcessId;
    }
  }
}
"@
}

function Test-PortInUse([int]$Port) {
    # Vite peut ecouter sur ::1 seulement ; artisan sur 127.0.0.1 — tester les deux.
    foreach ($hostName in @("127.0.0.1", "::1")) {
        try {
            $c = New-Object System.Net.Sockets.TcpClient
            $c.Connect($hostName, $Port)
            $c.Close()
            return $true
        } catch {
            # try next
        }
    }
    return $false
}

function Start-HiddenCmd([string]$WorkDir, [string]$CommandLine, [string]$LogFile, [string]$PidFile) {
    # cmd.exe /c ... — CreateProcess with CREATE_NEW_PROCESS_GROUP + BREAKAWAY_FROM_JOB.
    # Without that, closing start-pgfe.bat kills php/npm (CTRL_CLOSE / Kill-on-Job-Close).
    $cmdLine = "$($env:ComSpec) /c cd /d `"$WorkDir`" && ($CommandLine) >> `"$LogFile`" 2>&1"
    $procId = [PgfeDetach.Start]::Detached($cmdLine, $WorkDir)
    if ($PidFile -and $procId) {
        Set-Content -Path $PidFile -Value $procId -Encoding ASCII
    }
    return $procId
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
        # --host 127.0.0.1 : evite IPv6-only (::1) qui casse les checks et certains clients
        $null = Start-HiddenCmd $frontend "npm run dev -- --host 127.0.0.1 --port 5173" $log $pidFile
        $started += "frontend"
    }
}

if ($started.Count -gt 0) {
    Write-Host ("Services lances en arriere-plan : " + ($started -join ", "))
}
exit 0
