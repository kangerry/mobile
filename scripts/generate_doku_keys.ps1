$root = Split-Path -Parent $PSScriptRoot
$repoRoot = Split-Path -Parent $root
$dir = Join-Path $repoRoot 'doku-keys'
New-Item -ItemType Directory -Path $dir -Force | Out-Null
$rsa = [System.Security.Cryptography.RSA]::Create(2048)
$priv = $rsa.ExportPkcs8PrivateKey()
$pub = $rsa.ExportSubjectPublicKeyInfo()
$privPem = "-----BEGIN PRIVATE KEY-----`n" + [Convert]::ToBase64String($priv,'InsertLineBreaks') + "`n-----END PRIVATE KEY-----`n"
$pubPem = "-----BEGIN PUBLIC KEY-----`n" + [Convert]::ToBase64String($pub,'InsertLineBreaks') + "`n-----END PUBLIC KEY-----`n"
$privPath = Join-Path $dir 'merchant_private_key.pem'
$pubPath = Join-Path $dir 'merchant_public_key.pem'
Set-Content -Path $privPath -Value $privPem -NoNewline
Set-Content -Path $pubPath -Value $pubPem -NoNewline
Write-Output $privPath
Write-Output $pubPath
