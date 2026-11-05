<?php

namespace Database\Seeders;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'username' => 'member',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        Stock::firstOrCreate(
            ['user_id' => $member->id, 'name' => 'Laptop ASUS ROG'],
            [
                'sku' => 'LAP-ROG-001',
                'description' => 'Laptop gaming performa tinggi untuk kebutuhan grafis dan komputasi berat.',
                'quantity' => 15,
                'unit' => 'unit',
                'price' => 25000000.00,
                'min_stock' => 5,
                'status' => 'available',
            ]
        );

        Stock::firstOrCreate(
            ['user_id' => $member->id, 'name' => 'Mouse Wireless Logitech'],
            [
                'sku' => 'MOU-LOG-002',
                'description' => 'Mouse ergonomis dengan konektivitas bluetooth dan wireless 2.4GHz.',
                'quantity' => 4,
                'unit' => 'pcs',
                'price' => 350000.00,
                'min_stock' => 5,
                'status' => 'low_stock',
            ]
        );

        Stock::firstOrCreate(
            ['user_id' => $member->id, 'name' => 'Keyboard Mechanical RGB'],
            [
                'sku' => 'KEY-RGB-003',
                'description' => 'Keyboard mechanical dengan switch biru dan backlit RGB customizable.',
                'quantity' => 0,
                'unit' => 'pcs',
                'price' => 750000.00,
                'min_stock' => 5,
                'status' => 'out_of_stock',
            ]
        );
    }
}
