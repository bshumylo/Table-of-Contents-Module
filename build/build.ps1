# Builds the installable extension zip into dist\ (Windows).
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

[xml]$manifest = Get-Content 'mod_toc.xml'
$version = $manifest.extension.version
$out = "dist\mod_toc-$version.zip"

New-Item -ItemType Directory -Force -Path dist | Out-Null
if (Test-Path $out) { Remove-Item $out }

Compress-Archive -Path mod_toc.xml, services, src, tmpl, language, media, LICENSE -DestinationPath $out

$hash = (Get-FileHash $out -Algorithm SHA256).Hash.ToLower()
Write-Host "Built $out"
Write-Host "sha256 $hash"
