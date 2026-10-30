<#
.SYNOPSIS
    Builds the METE CLINIC WordPress theme and plugin archives.

.DESCRIPTION
    The script is intentionally ASCII-only: Windows PowerShell 5.1 reads
    .ps1 files without a BOM as the system ANSI code page, so non-ASCII
    text would break parsing.

    1. Copies brand raster assets (logos, favicon) from the source static
       project into the theme.
    2. Runs php -l when a PHP CLI is available.
    3. Creates mete-clinic-theme.zip and mete-clinic-content.zip with the
       theme/plugin files at the archive root (no wrapper project folder)
       and forward slashes inside the archive.
    4. Verifies the archive layout and prints a report.

.PARAMETER Source
    Source static site folder. Defaults to ..\mete-clinic-demo-main.

.PARAMETER SkipLint
    Skip php -l.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\build-zips.ps1
#>
[CmdletBinding()]
param(
    [string]$Source = '',
    [switch]$SkipLint
)

$ErrorActionPreference = 'Stop'

# ZIP types live in two different assemblies on .NET Framework:
#   System.IO.Compression            -> ZipArchive, ZipArchiveMode
#   System.IO.Compression.FileSystem -> ZipFile, ZipFileExtensions
$zipAssemblies = @(
    'System.IO.Compression',
    'System.IO.Compression.FileSystem'
)

foreach ($assembly in $zipAssemblies) {
    try {
        Add-Type -AssemblyName $assembly -ErrorAction Stop
    } catch {
        try {
            [System.Reflection.Assembly]::LoadWithPartialName($assembly) | Out-Null
        } catch {
            # Checked right below.
        }
    }
}

if (-not ('System.IO.Compression.ZipArchiveMode' -as [type])) {
    throw 'Cannot load System.IO.Compression (ZipArchiveMode). Requires .NET Framework 4.5+ or PowerShell 7.'
}

if (-not ('System.IO.Compression.ZipFile' -as [type])) {
    throw 'Cannot load System.IO.Compression.FileSystem (ZipFile). Requires .NET Framework 4.5+ or PowerShell 7.'
}

$root      = $PSScriptRoot
$themeDir  = Join-Path $root 'mete-clinic-theme'
$pluginDir = Join-Path $root 'mete-clinic-content'
$themeZip  = Join-Path $root 'mete-clinic-theme.zip'
$pluginZip = Join-Path $root 'mete-clinic-content.zip'

if (-not (Test-Path -LiteralPath $themeDir)) { throw "Theme folder not found: $themeDir" }
if (-not (Test-Path -LiteralPath $pluginDir)) { throw "Plugin folder not found: $pluginDir" }

if (-not $Source) {
    $Source = Join-Path (Split-Path $root -Parent) 'mete-clinic-demo-main'
}

Write-Host "Source project: $Source" -ForegroundColor Cyan

# --- 1. Brand assets --------------------------------------------------------

$brandTarget = Join-Path $themeDir 'assets\brand'
$iconsTarget = Join-Path $themeDir 'assets\icons'

New-Item -ItemType Directory -Force -Path $brandTarget | Out-Null
New-Item -ItemType Directory -Force -Path $iconsTarget | Out-Null

$brandFiles = @(
    'mete-clinic-emblem.webp',
    'mete-clinic-emblem.png',
    'mete-clinic-logo-full.webp',
    'mete-clinic-logo-full.png'
)

$brandCopied = 0

foreach ($name in $brandFiles) {
    $from = Join-Path (Join-Path $Source 'assets') $name
    if (Test-Path -LiteralPath $from) {
        Copy-Item -LiteralPath $from -Destination (Join-Path $brandTarget $name) -Force
        $brandCopied++
    }
}

if ($brandCopied -eq 0) {
    Write-Warning "Logos not found in $Source\assets. The theme will show a text mark instead."
} else {
    Write-Host "Brand files copied: $brandCopied" -ForegroundColor Green
}

$iconsSource = Join-Path (Join-Path $Source 'assets') 'icons'
if (Test-Path -LiteralPath $iconsSource) {
    Copy-Item -Path (Join-Path $iconsSource '*') -Destination $iconsTarget -Force
    Write-Host 'Favicon and icons copied.' -ForegroundColor Green
}

# --- 2. PHP syntax check ----------------------------------------------------

if (-not $SkipLint) {
    $php = Get-Command php -ErrorAction SilentlyContinue

    if ($php) {
        $phpFiles = @(Get-ChildItem -Path $themeDir, $pluginDir -Recurse -Filter '*.php' -File)
        $failed   = @()

        foreach ($file in $phpFiles) {
            $output = & $php.Source -l $file.FullName 2>&1
            if ($LASTEXITCODE -ne 0) {
                $failed += "$($file.FullName)`n$output"
            }
        }

        if ($failed.Count -gt 0) {
            $failed | ForEach-Object { Write-Host $_ -ForegroundColor Red }
            throw 'PHP syntax check failed. Archives were not created.'
        }

        Write-Host "PHP lint: OK, files: $($phpFiles.Count)" -ForegroundColor Green
    } else {
        Write-Warning 'php CLI not found. PHP syntax was NOT checked (php -l).'
    }
}

# --- 3. Build archives ------------------------------------------------------

function New-MeteZip {
    param(
        [Parameter(Mandatory = $true)][string]$SourceDir,
        [Parameter(Mandatory = $true)][string]$ZipPath
    )

    if (Test-Path -LiteralPath $ZipPath) {
        Remove-Item -LiteralPath $ZipPath -Force
    }

    $base  = (Resolve-Path -LiteralPath $SourceDir).Path
    $files = @(Get-ChildItem -LiteralPath $SourceDir -Recurse -File)
    $zip   = [System.IO.Compression.ZipFile]::Open($ZipPath, [System.IO.Compression.ZipArchiveMode]::Create)

    try {
        foreach ($file in $files) {
            $relative = $file.FullName.Substring($base.Length).TrimStart('\', '/')
            $entry    = $relative -replace '\\', '/'

            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $zip,
                $file.FullName,
                $entry,
                [System.IO.Compression.CompressionLevel]::Optimal
            ) | Out-Null
        }
    } finally {
        $zip.Dispose()
    }
}

New-MeteZip -SourceDir $themeDir -ZipPath $themeZip
New-MeteZip -SourceDir $pluginDir -ZipPath $pluginZip

# --- 4. Verify archive layout -----------------------------------------------

function Test-ZipLayout {
    param(
        [Parameter(Mandatory = $true)][string]$ZipPath,
        [Parameter(Mandatory = $true)][string]$OuterFolder
    )

    $archive = [System.IO.Compression.ZipFile]::OpenRead($ZipPath)

    try {
        $names = @($archive.Entries | ForEach-Object { $_.FullName })
    } finally {
        $archive.Dispose()
    }

    $nested = @($names | Where-Object { $_ -like "$OuterFolder/*" })
    $bad    = @($names | Where-Object { $_ -like '*\*' })
    $root   = @($names | Where-Object { $_ -notlike '*/*' } | Select-Object -First 5)

    [pscustomobject]@{
        Zip       = (Split-Path $ZipPath -Leaf)
        Entries   = $names.Count
        Nested    = $nested.Count
        Backslash = $bad.Count
        Root      = ($root -join ', ')
    }
}

$reports = @(
    Test-ZipLayout -ZipPath $themeZip -OuterFolder 'mete-clinic-theme'
    Test-ZipLayout -ZipPath $pluginZip -OuterFolder 'mete-clinic-content'
)

$reports | Format-Table -AutoSize | Out-String | Write-Host

$broken = @($reports | Where-Object { $_.Nested -gt 0 -or $_.Backslash -gt 0 })

if ($broken.Count -gt 0) {
    throw 'Bad archive layout: wrapper folder or backslash separators found.'
}

$themeSize  = [math]::Round((Get-Item -LiteralPath $themeZip).Length / 1KB, 1)
$pluginSize = [math]::Round((Get-Item -LiteralPath $pluginZip).Length / 1KB, 1)

Write-Host ''
Write-Host "mete-clinic-theme.zip   - $themeSize KB" -ForegroundColor Green
Write-Host "mete-clinic-content.zip - $pluginSize KB" -ForegroundColor Green
Write-Host 'Done. Install both archives in WordPress admin (Appearance - Themes, Plugins - Add New).' -ForegroundColor Cyan
