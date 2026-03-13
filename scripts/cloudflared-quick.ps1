Param(
  [string]$LaravelUrl = "http://127.0.0.1:8000"
)
$ErrorActionPreference = "SilentlyContinue"
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = Resolve-Path (Join-Path $here "..")
Set-Location $root
if (-not (Test-Path ".\bin")) { New-Item -ItemType Directory ".\bin" | Out-Null }
$exe = Join-Path $root "bin\cloudflared.exe"
$downloadUrl = "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe"
if (-not (Test-Path $exe)) {
  try { Invoke-WebRequest -Uri $downloadUrl -OutFile $exe -UseBasicParsing } catch { }
}
if (-not (Test-Path $exe)) { Write-Output "cloudflared tidak dapat diunduh"; exit 1 }
$logDir = Join-Path $root "storage\logs"
if (-not (Test-Path $logDir)) { New-Item -ItemType Directory $logDir | Out-Null }
$log = Join-Path $logDir "cloudflared.log"
if (Test-Path $log) { Remove-Item $log -Force }
$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName = $exe
$psi.WorkingDirectory = $root
$psi.Arguments = "tunnel --url $LaravelUrl --no-autoupdate --logfile `"$log`" --loglevel info"
$psi.CreateNoWindow = $true
$psi.UseShellExecute = $false
$proc = [System.Diagnostics.Process]::Start($psi)
Start-Sleep -Seconds 2
$publicUrl = $null
for ($i=0; $i -lt 60; $i++) {
  if (Test-Path $log) {
    try {
      $content = Get-Content $log -Raw
      if ($content -match "https://[a-zA-Z0-9\-]+\.trycloudflare\.com") {
        $publicUrl = $Matches[0]
        break
      }
    } catch { }
  }
  Start-Sleep -Seconds 1
}
if ($null -eq $publicUrl -or $publicUrl -eq "") {
  Write-Output "Gagal menemukan URL tunnel"
  exit 2
}
$php = "php"
try {
  & $php artisan app:url $publicUrl | Out-Null
} catch { }
Write-Output $publicUrl
Start-Sleep -Seconds 1
Wait-Process -Id $proc.Id
