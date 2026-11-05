<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_from_array_maps_all_fields(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => ' Kabel USB ', 'sku' => 'USB-1', 'description' => '**Kabel**',
            'quantity' => '12', 'unit' => 'pcs', 'price' => '15000.555', 'min_stock' => 4,
        ]);

        $this->assertSame('Kabel USB', $item->name);
        $this->assertSame('USB-1', $item->sku);
        $this->assertSame('**Kabel**', $item->description);
        $this->assertSame(12, $item->quantity);
        $this->assertSame(15000.56, $item->price);
        $this->assertSame(4, $item->minStock);
        $this->assertSame([
            'name' => 'Kabel USB', 'sku' => 'USB-1', 'description' => '**Kabel**',
            'quantity' => 12, 'unit' => 'pcs', 'price' => 15000.56, 'min_stock' => 4,
        ], $item->toArray());
    }

    public function test_defaults_and_sanitising(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => 'Item', 'sku' => '', 'description' => ['x'], 'quantity' => -5,
            'unit' => '  ', 'price' => 'abc', 'min_stock' => -1,
        ]);

        $this->assertNull($item->sku);
        $this->assertNull($item->description);
        $this->assertSame(0, $item->quantity);
        $this->assertSame('pcs', $item->unit);
        $this->assertNull($item->price);
        $this->assertSame(0, $item->minStock);
    }

    public function test_values_are_clamped(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => str_repeat('a', 400), 'quantity' => 9_999_999_999, 'price' => 1e15, 'min_stock' => 9_999_999_999,
        ]);

        $this->assertSame(255, mb_strlen($item->name));
        $this->assertSame(1_000_000_000, $item->quantity);
        $this->assertSame(9999999999.99, $item->price);
        $this->assertSame(1_000_000_000, $item->minStock);
        $this->assertSame(5, GeneratedStockItem::fromArray(['name' => 'x'])->minStock);
    }

    public function test_returns_null_without_name(): void
    {
        $this->assertNull(GeneratedStockItem::fromArray([]));
        $this->assertNull(GeneratedStockItem::fromArray(['name' => '   ']));
        $this->assertNull(GeneratedStockItem::fromArray(['name' => ['a']]));
    }
}
