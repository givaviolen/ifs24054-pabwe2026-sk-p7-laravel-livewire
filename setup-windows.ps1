# Jalankan dari folder hasil ekstrak overlay P7.
$ErrorActionPreference = "Stop"

$Source = $PSScriptRoot
$ProjectName = "ifs24054-pabwe2026-sk-p7-laravel-livewire-app"
$Target = Join-Path (Split-Path $Source -Parent) $ProjectName

Write-Host "== Pemeriksaan prasyarat ==" -ForegroundColor Cyan
if (-not (Get-Command php -ErrorAction SilentlyContinue)) { throw "PHP tidak ditemukan. Instal PHP 8.3+ dan tambahkan ke PATH." }
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) { throw "Composer tidak ditemukan. Instal Composer dan buka ulang terminal." }

$PhpVersion = (php -r "echo PHP_VERSION;").Trim()
Write-Host "PHP: $PhpVersion"
if ([version]$PhpVersion -lt [version]"8.3") { throw "Laravel 13 memerlukan PHP 8.3 atau lebih baru. Versi terdeteksi: $PhpVersion" }

if (Test-Path $Target) {
    throw "Folder tujuan sudah ada: $Target`nAgar data tidak tertimpa, hapus/pindahkan folder itu atau ubah `$ProjectName di script."
}

Write-Host "== Membuat kerangka Laravel 13 ==" -ForegroundColor Cyan
composer create-project laravel/laravel "^13.0" $Target
if ($LASTEXITCODE -ne 0) { throw "composer create-project gagal." }

Push-Location $Target
try {
    Write-Host "== Memasang dependensi aplikasi ==" -ForegroundColor Cyan
    composer require livewire/livewire phpoffice/phpspreadsheet
    if ($LASTEXITCODE -ne 0) { throw "composer require gagal." }

    Write-Host "== Menyalin source code P7 ==" -ForegroundColor Cyan
    $Exclude = @(".git", "README.md", "setup.sh", "setup-windows.ps1", "vendor", "node_modules")
    Get-ChildItem -Force $Source | Where-Object { $Exclude -notcontains $_.Name } | ForEach-Object {
        Copy-Item -Path $_.FullName -Destination $Target -Recurse -Force
    }

    # Pakai SQLite untuk memudahkan menjalankan aplikasi tanpa konfigurasi MySQL.
    $DatabaseFile = Join-Path $Target "database\database.sqlite"
    if (-not (Test-Path (Split-Path $DatabaseFile -Parent))) {
        New-Item -ItemType Directory -Path (Split-Path $DatabaseFile -Parent) -Force | Out-Null
    }
    if (-not (Test-Path $DatabaseFile)) { New-Item -ItemType File -Path $DatabaseFile | Out-Null }

    $EnvFile = Join-Path $Target ".env"
    if (-not (Test-Path $EnvFile)) { Copy-Item ".env.example" ".env" }
    $EnvText = Get-Content $EnvFile -Raw
    $EnvText = $EnvText -replace '(?m)^APP_NAME=.*$', 'APP_NAME="Stock Barang"'
    $EnvText = $EnvText -replace '(?m)^APP_URL=.*$', 'APP_URL=http://127.0.0.1:8000'
    $EnvText = $EnvText -replace '(?m)^DB_CONNECTION=.*$', 'DB_CONNECTION=sqlite'
    $EnvText = $EnvText -replace '(?m)^DB_DATABASE=.*$', 'DB_DATABASE=database/database.sqlite'
    $EnvText = $EnvText -replace '(?m)^SESSION_DRIVER=.*$', 'SESSION_DRIVER=file'
    $EnvText = $EnvText -replace '(?m)^CACHE_STORE=.*$', 'CACHE_STORE=file'
    if ($EnvText -notmatch '(?m)^FORCE_HTTPS=') { $EnvText += "`r`nFORCE_HTTPS=false" }
    if ($EnvText -notmatch '(?m)^OPENAI_API_KEY=') { $EnvText += "`r`nOPENAI_API_KEY=" }
    if ($EnvText -notmatch '(?m)^OPENAI_BASE_URL=') { $EnvText += "`r`nOPENAI_BASE_URL=https://api.openai.com/v1" }
    if ($EnvText -notmatch '(?m)^OPENAI_MODEL=') { $EnvText += "`r`nOPENAI_MODEL=gpt-4o-mini" }
    Set-Content -Path $EnvFile -Value $EnvText -Encoding UTF8

    # Tambahkan konfigurasi khusus aplikasi yang dibaca AppServiceProvider.
    $AppConfig = Join-Path $Target "config\app.php"
    $ConfigText = Get-Content $AppConfig -Raw
    if ($ConfigText -notmatch "'force_https'\s*=>") {
        $ConfigText = $ConfigText -replace "('name'\s*=>\s*env\('APP_NAME',\s*'Laravel'\),)", "`$1`r`n    'force_https' => (bool) env('FORCE_HTTPS', false),"
    }
    if ($ConfigText -notmatch "'force_https'\s*=>") { throw "Gagal menambahkan force_https ke config/app.php." }
    Set-Content -Path $AppConfig -Value $ConfigText -Encoding UTF8

    # Skrip Composer untuk test sesuai instruksi repository.
    $ComposerJson = Get-Content "composer.json" -Raw | ConvertFrom-Json
    if (-not $ComposerJson.scripts) { $ComposerJson | Add-Member -NotePropertyName scripts -NotePropertyValue ([pscustomobject]@{}) }
    $ComposerJson.scripts | Add-Member -Force -NotePropertyName test -NotePropertyValue @("@php artisan config:clear --ansi", "@php vendor/bin/phpunit")
    $ComposerJson.scripts | Add-Member -Force -NotePropertyName "test:coverage" -NotePropertyValue @("@php artisan config:clear --ansi", "@php vendor/bin/phpunit --coverage-text")
    $ComposerJson | ConvertTo-Json -Depth 100 | Set-Content "composer.json" -Encoding UTF8

    Write-Host "== Menyiapkan aplikasi ==" -ForegroundColor Cyan
    php artisan key:generate
    if ($LASTEXITCODE -ne 0) { throw "php artisan key:generate gagal." }
    php artisan config:clear
    php artisan migrate --seed
    if ($LASTEXITCODE -ne 0) { throw "Migration/seeder gagal. Periksa pesan error di atas." }
    php artisan storage:link
    Write-Host "`nSELESAI. Jalankan: cd `"$Target`" ; php artisan serve" -ForegroundColor Green
}
finally {
    Pop-Location
}
