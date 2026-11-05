<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Akun demo (admin/admin, member/member) — hanya untuk pengembangan lokal. */
    public function run(): void
    {
        User::updateOrCreate(['username' => 'admin'], [
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => 'admin',
            'role' => User::ROLE_ADMIN,
        ]);

        $member = User::updateOrCreate(['username' => 'member'], [
            'name' => 'Member Demo',
            'email' => 'member@example.com',
            'password' => 'member',
            'role' => User::ROLE_MEMBER,
        ]);

        if ($member->stocks()->exists()) {
            return;
        }

        $samples = [
            ['Kertas A4 80gsm', 'KRT-A4-80', 120, 'rim', 52000, 20],
            ['Pulpen Gel Hitam', 'PLP-GEL-BLK', 8, 'pcs', 3500, 10],
            ['Tinta Printer Hitam', 'TNT-PRN-BLK', 0, 'botol', 85000, 3],
            ['Stapler Besar', 'STP-BIG-01', 15, 'pcs', 45000, 5],
            ['Map Plastik Folio', 'MAP-FOL-01', 4, 'pack', 18000, 5],
            ['Spidol Whiteboard', 'SPD-WB-01', 30, 'pcs', 9000, 8],
        ];

        foreach ($samples as [$name, $sku, $qty, $unit, $price, $min]) {
            Stock::create([
                'user_id' => $member->id,
                'name' => $name,
                'sku' => $sku,
                'description' => "**{$name}** — contoh data awal.\n\n- Lokasi: Gudang A",
                'quantity' => $qty,
                'unit' => $unit,
                'price' => $price,
                'min_stock' => $min,
                'status' => StockStatus::fromQuantity($qty, $min)->value,
            ]);
        }
    }
}
