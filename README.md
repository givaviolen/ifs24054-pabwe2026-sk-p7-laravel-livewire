# ifs24054-pabwe2026-sk-p7-laravel-livewire

Aplikasi inventaris stok barang (Laravel 13 + Livewire + PhpSpreadsheet), Feature-First.

## Isi paket
Ini adalah **overlay**: seluruh kode aplikasi (app/, routes/, bootstrap/app.php, migrations, views, tests,
Jenkinsfile, phpunit.xml, .gitignore, .env.example). Kerangka Laravel (artisan, vendor, config/*, dst.)
dibuat oleh `laravel new`.

## Cara pakai
    bash setup.sh            # membuat proyek, install dependensi, menimpa kode, migrate --seed
    cd ifs24054-pabwe2026-sk-p7-laravel-livewire
    composer test            # harus hijau, coverage app/ 100% (butuh Xdebug/PCOV: composer test:coverage)
    composer audit
    php artisan serve        # login: admin/admin atau member/member

Manual: lihat isi `setup.sh` (langkah sama).

## Catatan desain
- Status stok **selalu dihitung** dari quantity vs min_stock (habis ≤0, menipis ≤min_stock, selain itu tersedia),
  baik saat create/edit/adjust/import/AI.
- Admin hanya membaca: `AdminUserService` tidak punya method tulis, `StockPolicy` menolak update/delete untuk admin.
- Komponen Livewire memeriksa policy di setiap aksi (middleware route tidak diterapkan ulang pada request /livewire/update).
- Tailwind & Toast UI Editor dimuat dari CDN (tanpa langkah build).
- AI: `OpenAiStockGeneratorService` mengimplementasikan `AiClientInterface`; isi OPENAI_API_KEY di .env.
