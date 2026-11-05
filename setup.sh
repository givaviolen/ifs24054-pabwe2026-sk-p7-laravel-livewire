#!/usr/bin/env bash
# Membuat proyek Laravel 13 + Livewire dan menimpa dengan kode Stock dari folder ini.
# Jalankan DARI folder induk tempat Anda ingin proyek dibuat:  bash <path>/setup.sh
set -euo pipefail

SRC="$(cd "$(dirname "$0")" && pwd)"
NAME="${1:-ifs24054-pabwe2026-sk-p7-laravel-livewire}"
DB="${DB_NAME:-pabwe_p7_stock}"

laravel new "$NAME" --database=mysql --phpunit --no-node --no-boost --no-interaction
cd "$NAME"

composer require livewire/livewire phpoffice/phpspreadsheet
[ -f package.json ] && npm install --ignore-scripts || true

# hapus contoh bawaan & timpa dengan kode Stock
rm -f tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php
rsync -a --exclude setup.sh --exclude README.md "$SRC"/ ./

# config/app.php: tambah force_https
if ! grep -q "force_https" config/app.php; then
  sed -i "0,/'name' => env('APP_NAME', 'Laravel'),/s//&\n\n    'force_https' => (bool) env('FORCE_HTTPS', false),/" config/app.php
fi
grep -q force_https config/app.php || { echo "GAGAL menambah force_https ke config/app.php — tambahkan manual"; exit 1; }

# composer scripts
php -r '
$c = json_decode(file_get_contents("composer.json"), true);
$c["scripts"]["test"] = ["@php artisan config:clear --ansi", "@php vendor/bin/phpunit"];
$c["scripts"]["test:coverage"] = ["@php artisan config:clear --ansi", "@php vendor/bin/phpunit --coverage-text"];
file_put_contents("composer.json", json_encode($c, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL);
'

# .env
[ -f .env ] || cp .env.example .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB/" .env
grep -q '^FORCE_HTTPS' .env || printf '\nFORCE_HTTPS=false\nOPENAI_API_KEY=\nOPENAI_BASE_URL=https://api.openai.com/v1\nOPENAI_MODEL=gpt-4o-mini\n' >> .env
grep -q '^APP_KEY=base64' .env || php artisan key:generate

mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS $DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed
php artisan storage:link || true

echo "Selesai. Jalankan: cd $NAME && composer test && composer audit && php artisan serve"
