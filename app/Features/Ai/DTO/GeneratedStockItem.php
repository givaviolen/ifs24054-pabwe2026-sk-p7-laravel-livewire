<?php

namespace App\Features\Ai\DTO;

final readonly class GeneratedStockItem
{
    public function __construct(
        public string $name,
        public ?string $sku,
        public ?string $description,
        public int $quantity,
        public string $unit,
        public ?float $price,
        public int $minStock,
    ) {}

    /** Bangun dari array mentah (respons AI); null bila nama kosong. Nilai dibatasi agar aman. */
    public static function fromArray(array $data): ?self
    {
        $name = self::text($data['name'] ?? null, 255);

        if ($name === null) {
            return null;
        }

        return new self(
            name: $name,
            sku: self::text($data['sku'] ?? null, 64),
            description: self::text($data['description'] ?? null, 2000),
            quantity: max(0, min(1_000_000_000, (int) ($data['quantity'] ?? 0))),
            unit: self::text($data['unit'] ?? null, 20) ?? 'pcs',
            price: is_numeric($data['price'] ?? null)
                ? max(0.0, min(9999999999.99, round((float) $data['price'], 2)))
                : null,
            minStock: max(0, min(1_000_000_000, (int) ($data['min_stock'] ?? 5))),
        );
    }

    /** Format yang diterima StockService::create(). */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'min_stock' => $this->minStock,
        ];
    }

    private static function text(mixed $value, int $max): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
