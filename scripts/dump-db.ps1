# Backup database MySQL Penta Tri Saka.
# Pakai: powershell -File scripts/dump-db.ps1
param(
    [string]$Database = 'penta_trisaka',
    [string]$User = 'root',
    [string]$Password = '',
    [string]$OutDir = 'backups',
    [string]$MysqlDump = 'C:\xampp\mysql\bin\mysqldump.exe'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$outDirPath = Join-Path $root $OutDir
New-Item -ItemType Directory -Force -Path $outDirPath | Out-Null

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$file = Join-Path $outDirPath "penta-$Database-$stamp.sql"

$dumpArgs = @("--user=$User", '--single-transaction', '--routines', '--triggers')
if ($Password -ne '') {
    $dumpArgs += "--password=$Password"
}
$dumpArgs += $Database

& $MysqlDump @dumpArgs | Out-File -Encoding utf8 $file
Write-Host "Backup selesai: $file"
