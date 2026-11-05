<?php
namespace App\Features\Stock\Enums;

enum StockStatus: string {
    case AVAILABLE = 'available';
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';

    public function label(): string {
        return match($this) {
            self::AVAILABLE => 'Tersedia',
            self::LOW_STOCK => 'Stok Menipis',
            self::OUT_OF_STOCK => 'Habis',
        };
    }

    public function badgeClass(): string {
        return match($this) {
            self::AVAILABLE => 'bg-green-500 text-white',
            self::LOW_STOCK => 'bg-yellow-500 text-black',
            self::OUT_OF_STOCK => 'bg-red-500 text-white',
        };
    }
}
