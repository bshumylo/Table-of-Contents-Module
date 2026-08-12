# Builds the installable extension zip into dist\ (Windows).
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

[xml]$manifest = Get-Content 'mod_toc.xml'
$version = $manifest.extension.version
$out = "dist\mod_toc-$version.zip"

New-Item -ItemType Directory -Force -Path dist | Out-Null
if (Test-Path $out) { Remove-Item $out }

# Compress-Archive writes the entry names with backslashes, which a Linux
# host — and Joomla's own unpacker on it — reads as part of the file name
# instead of as directories, so the language files would land next to the
# manifest as "language\en-GB\mod_toc.ini" and never be found. Build the
# archive by hand and keep the separators forward slashes.
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$root  = (Get-Location).Path
$items = 'mod_toc.xml', 'services', 'src', 'tmpl', 'language', 'media', 'LICENSE'

$files = foreach ($item in $items) {
    $path = Join-Path $root $item
    if (Test-Path $path -PathType Container) {
        Get-ChildItem -Path $path -Recurse -File
    } else {
        Get-Item -Path $path
    }
}

$zip = [System.IO.Compression.ZipFile]::Open((Join-Path $root $out), 'Create')

try {
    foreach ($file in $files) {
        $entry = $file.FullName.Substring($root.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $entry) | Out-Null
    }
} finally {
    $zip.Dispose()
}

$hash = (Get-FileHash $out -Algorithm SHA256).Hash.ToLower()
Write-Host "Built $out"
Write-Host "sha256 $hash"
