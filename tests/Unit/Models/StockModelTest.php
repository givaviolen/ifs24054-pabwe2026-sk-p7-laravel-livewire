<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_and_defaults(): void
    {
        $stock = Stock::factory()->create(['quantity' => 3, 'min_stock' => 5, 'price' => 12.5, 'status' => 'low_stock'])->fresh();

        $this->assertSame(3, $stock->quantity);
        $this->assertSame('12.50', $stock->price);
        $this->assertSame(StockStatus::LowStock, $stock->status);
        $this->assertInstanceOf(User::class, $stock->user);
    }

    public function test_factory_quantity_state_syncs_status(): void
    {
        $this->assertSame(StockStatus::OutOfStock, Stock::factory()->quantity(0)->make()->status);
        $this->assertSame(StockStatus::Available, Stock::factory()->quantity(1000)->make()->status);
    }

    public function test_cover_url(): void
    {
        $this->assertNull(Stock::factory()->make()->coverUrl());
        $this->assertStringEndsWith('stock-covers/x.jpg', Stock::factory()->make(['cover_image_path' => 'stock-covers/x.jpg'])->coverUrl());
    }

    public function test_description_html_is_markdown_and_safe(): void
    {
        $this->assertNull(Stock::factory()->make(['description' => null])->descriptionHtml());
        $this->assertNull(Stock::factory()->make(['description' => ''])->descriptionHtml());

        $html = Stock::factory()->make([
            'description' => "**tebal**\n\n<script>alert(1)</script>\n\n[x](javascript:alert(1))",
        ])->descriptionHtml();

        $this->assertStringContainsString('<strong>tebal</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }
}
