<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_dto_properties_and_to_array(): void
    {
        $dto = new GeneratedStockItem(
            name: 'Kertas A4',
            sku: 'KRT-A4-01',
            description: 'Kertas HVS 80gr',
            quantity: 50,
            unit: 'rim',
            price: 45000.0,
            minStock: 10,
            status: 'available'
        );

        $this->assertEquals('Kertas A4', $dto->name);
        $this->assertEquals('KRT-A4-01', $dto->sku);
        $this->assertEquals('Kertas HVS 80gr', $dto->description);
        $this->assertEquals(50, $dto->quantity);
        $this->assertEquals('rim', $dto->unit);
        $this->assertEquals(45000.0, $dto->price);
        $this->assertEquals(10, $dto->minStock);
        $this->assertEquals('available', $dto->status);

        $array = $dto->toArray();
        $this->assertEquals([
            'name' => 'Kertas A4',
            'sku' => 'KRT-A4-01',
            'description' => 'Kertas HVS 80gr',
            'quantity' => 50,
            'unit' => 'rim',
            'price' => 45000.0,
            'min_stock' => 10,
            'status' => 'available',
        ], $array);
    }
}
