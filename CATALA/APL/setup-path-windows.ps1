# Ajoute au PATH utilisateur les outils requis par Catala/clerk sous Windows :
# le dossier bin d'opam, les DLL runtime MinGW, et ninja.
# A lancer une seule fois (idempotent), puis ferme et rouvre ton terminal / VS Code.

$ErrorActionPreference = "Stop"

$opamBin = "$env:LOCALAPPDATA\opam\default\bin"
$mingwDll = "$env:LOCALAPPDATA\opam\.cygwin\root\usr\x86_64-w64-mingw32\sys-root\mingw\bin"

if (-not (Test-Path $opamBin)) {
    Write-Error "opam introuvable a '$opamBin'. Installe d'abord Catala : opam install catala"
}

$ninjaDir = $null
if (Get-Command ninja -ErrorAction SilentlyContinue) {
    Write-Output "ninja est deja accessible, rien a ajouter pour lui."
} else {
    $ninjaPkg = Get-ChildItem "$env:LOCALAPPDATA\Microsoft\WinGet\Packages" -Directory -Filter "Ninja-build.Ninja_*" -ErrorAction SilentlyContinue |
        Select-Object -First 1
    if ($ninjaPkg) {
        $ninjaDir = $ninjaPkg.FullName
    } else {
        Write-Warning "ninja introuvable. Installe-le avec : winget install Ninja-build.Ninja (puis relance ce script)."
    }
}

$currentPath = [Environment]::GetEnvironmentVariable("Path", "User")
$candidates = @($opamBin, $mingwDll, $ninjaDir) | Where-Object { $_ }
$toAdd = $candidates | Where-Object { $currentPath -notlike "*$_*" }

if ($toAdd.Count -eq 0) {
    Write-Output "PATH deja configure correctement, rien a faire."
} else {
    $newPath = ($toAdd -join ";") + ";" + $currentPath
    [Environment]::SetEnvironmentVariable("Path", $newPath, "User")
    Write-Output "PATH utilisateur mis a jour avec : $($toAdd -join ', ')"
    Write-Output "Ferme et rouvre ton terminal (ou VS Code) pour que le changement prenne effet."
}
