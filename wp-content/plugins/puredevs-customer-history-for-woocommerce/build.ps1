<#
    Two-build deploy for PureDevs Customer History for WooCommerce (Windows port).

    Windows companion to build.sh — keep the two IN SYNC (same .distignore excludes
    and the same sanity checks). build.sh uses rsync + zip (not present on Windows);
    this script uses robocopy + Compress-Archive instead.

    One source tree -> two clean ZIPs:
      - FREE build (pro/ stripped)   -> WordPress.org   (no premium code shipped)
      - PRO  build (pro/ included)   -> Freemius dashboard

    The free build self-identifies as free because PDWCHS_IS_PREMIUM is
    file_exists(pro/pro.php); with pro/ stripped that is false, so no per-build
    code edits are needed.

    Usage:
      powershell -File build.ps1            # build both (free + pro)
      powershell -File build.ps1 free       # free build only
      powershell -File build.ps1 pro        # pro build only

    Output:
      dist/puredevs-customer-history-for-woocommerce.zip       (FREE -> wp.org)
      dist/puredevs-customer-history-for-woocommerce-pro.zip   (PRO  -> Freemius)
#>

param([string]$Target = 'both')

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

# Compress-Archive on Windows PowerShell 5.1 writes ZIP entries with backslash
# separators, which Linux extractors (WordPress.org, Freemius) treat as literal
# filenames instead of folders. Build the archive manually with forward slashes.
function New-PluginZip {
    param([string]$SourceRoot, [string]$ZipPath)

    if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }
    $rootLen = $SourceRoot.TrimEnd('\').Length + 1
    $zip = [System.IO.Compression.ZipFile]::Open($ZipPath, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        Get-ChildItem -Path $SourceRoot -Recurse -File -Force | ForEach-Object {
            $entryName = $_.FullName.Substring($rootLen).Replace('\', '/')
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $zip, $_.FullName, $entryName,
                [System.IO.Compression.CompressionLevel]::Optimal)
        }
    } finally {
        $zip.Dispose()
    }
}

$PluginSlug = 'puredevs-customer-history-for-woocommerce'
$MainFile   = "$PluginSlug.php"
$PluginDir  = $PSScriptRoot
$DistDir    = Join-Path $PluginDir 'dist'

# Version from the main plugin file header (mirrors build.sh grep/sed).
$verLine = Select-String -Path (Join-Path $PluginDir $MainFile) -Pattern 'Version:\s*([0-9][0-9A-Za-z.\-]*)' | Select-Object -First 1
if ($null -eq $verLine) { Write-Error "Could not read Version from $MainFile"; exit 1 }
$Version = $verLine.Matches[0].Groups[1].Value

# Exclusions derived from .distignore, split for robocopy (/XD dirs, /XF files).
# build.ps1 itself is excluded in addition to build.sh.
$ExcludeDirs = @('.git', '.github', '.claude', 'tests', 'docs', 'vendor', 'dist', 'node_modules', '.wporg-assets')
$ExcludeFiles = @(
    '.gitignore', '.gitattributes', 'phpcs-security.xml', 'phpstan.neon',
    'phpstan-baseline.neon', 'phpunit.xml.dist', '.phpunit.result.cache',
    'CLAUDE.md', 'composer.json', 'composer.lock', 'build.sh', 'build.ps1',
    '.distignore', '.DS_Store'
)

function Build-Variant {
    param([string]$Variant)

    if ($Variant -eq 'free') {
        $zipName = "$PluginSlug.zip"
    } else {
        $zipName = "$PluginSlug-pro.zip"
    }

    $tmpDir   = Join-Path ([System.IO.Path]::GetTempPath()) ("pdwchs-build-" + [System.Guid]::NewGuid().ToString('N'))
    $buildDir = Join-Path $tmpDir $PluginSlug   # internal folder == slug for BOTH builds
    New-Item -ItemType Directory -Path $buildDir -Force | Out-Null

    Write-Host "-> $Variant build"

    # robocopy is the native rsync substitute. Free build also strips pro/.
    $xd = @($ExcludeDirs)
    if ($Variant -eq 'free') { $xd += 'pro' }   # strip premium folder for wp.org

    $roboArgs = @($PluginDir, $buildDir, '/E', '/NFL', '/NDL', '/NJH', '/NJS', '/NP', '/R:1', '/W:1')
    $roboArgs += '/XD'; $roboArgs += $xd
    $roboArgs += '/XF'; $roboArgs += $ExcludeFiles
    & robocopy @roboArgs | Out-Null
    # robocopy exit codes 0-7 are success; >= 8 is a real failure.
    if ($LASTEXITCODE -ge 8) { Write-Error "robocopy failed (exit $LASTEXITCODE)"; exit 1 }

    # Strip the premium-only uninstall block from the FREE build (mirrors the sed
    # range-delete in build.sh). Line-based: drops the marker lines and everything
    # between them.
    $mainPath = Join-Path $buildDir $MainFile
    if ($Variant -eq 'free') {
        $kept  = New-Object System.Collections.Generic.List[string]
        $skip  = $false
        $seen  = 0
        foreach ($line in [System.IO.File]::ReadAllLines($mainPath)) {
            if ($line -match 'pdwchs:pro-cleanup:start') { $skip = $true; $seen++; continue }
            if ($line -match 'pdwchs:pro-cleanup:end')   { $skip = $false;         continue }
            if (-not $skip) { $kept.Add($line) }
        }
        if ($skip)       { Write-Error "unterminated pdwchs:pro-cleanup marker in $MainFile"; exit 1 }
        if ($seen -ne 1) { Write-Error "expected exactly 1 pdwchs:pro-cleanup block, found $seen"; exit 1 }
        # UTF-8 WITHOUT BOM. Set-Content -Encoding utf8 on PS 5.1 emits a BOM, which
        # would land before "<?php" and fatal the plugin with "headers already sent".
        [System.IO.File]::WriteAllText(
            $mainPath, ($kept -join "`n") + "`n",
            (New-Object System.Text.UTF8Encoding($false)))
    }

    # Production-only deps (Freemius SDK), rebuilt fresh per build.
    Copy-Item (Join-Path $PluginDir 'composer.json') $buildDir
    Copy-Item (Join-Path $PluginDir 'composer.lock') $buildDir
    Push-Location $buildDir
    try {
        & composer install --no-dev --optimize-autoloader --no-interaction --quiet
        if ($LASTEXITCODE -ne 0) { Write-Error "composer install failed (exit $LASTEXITCODE)"; exit 1 }
    } finally {
        Pop-Location
    }
    Remove-Item (Join-Path $buildDir 'composer.json'), (Join-Path $buildDir 'composer.lock') -Force
    Get-ChildItem -Path $buildDir -Recurse -Force -Filter '.DS_Store' | Remove-Item -Force

    # Sanity checks (mirror build.sh).
    if (-not (Test-Path (Join-Path $buildDir $MainFile)))          { Write-Error "$MainFile missing."; exit 1 }
    if (-not (Test-Path (Join-Path $buildDir 'vendor\autoload.php'))) { Write-Error "vendor/autoload.php missing."; exit 1 }
    if ($Variant -eq 'free') {
        if (Test-Path (Join-Path $buildDir 'pro'))               { Write-Error "pro/ leaked into the FREE build."; exit 1 }
        if (Select-String -Path $mainPath -Pattern 'pdwchs:pro-cleanup' -Quiet) {
            Write-Error "pro-cleanup markers survived the FREE build."; exit 1
        }
        if (Select-String -Path $mainPath -Pattern 'pdwchs_cart_tracking_enabled' -Quiet) {
            Write-Error "premium option keys survived the FREE build."; exit 1
        }
    } else {
        if (-not (Test-Path (Join-Path $buildDir 'pro\pro.php'))) { Write-Error "pro/pro.php missing from the PRO build."; exit 1 }
        if (-not (Select-String -Path $mainPath -Pattern 'pdwchs:pro-cleanup:start' -Quiet)) {
            Write-Error "pro-cleanup block missing from the PRO build."; exit 1
        }
    }
    # A mismatched marker silently deletes to end of file — lint before zipping.
    & php -l $mainPath | Out-Null
    if ($LASTEXITCODE -ne 0) { Write-Error "$MainFile has a syntax error after the strip."; exit 1 }

    $zipPath = Join-Path $DistDir $zipName
    # Archive from tmpDir so the slug folder becomes the archive root (matches build.sh).
    New-PluginZip -SourceRoot $tmpDir -ZipPath $zipPath

    Remove-Item -Path $tmpDir -Recurse -Force
    $sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 1)
    Write-Host "   dist/$zipName ($sizeMb MB)"
}

Write-Host "Building $PluginSlug v$Version  [$Target]"
Write-Host "================================"
if (Test-Path $DistDir) { Remove-Item $DistDir -Recurse -Force }
New-Item -ItemType Directory -Path $DistDir -Force | Out-Null

switch ($Target) {
    'free' { Build-Variant 'free' }
    'pro'  { Build-Variant 'pro' }
    'both' { Build-Variant 'free'; Build-Variant 'pro' }
    default { Write-Error "unknown target '$Target' (use: free | pro | both)"; exit 1 }
}

Write-Host ""
Write-Host "Build complete (v$Version)."
