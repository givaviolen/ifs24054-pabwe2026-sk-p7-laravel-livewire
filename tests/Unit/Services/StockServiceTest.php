<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = app(StockService::class);
        $this->user = User::factory()->create();
    }

    public function test_rules_cover_all_form_fields(): void
    {
        $this->assertSame(
            ['name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock'],
            array_keys(StockService::rules())
        );
    }

    public function test_create_derives_status_and_normalises(): void
    {
        $stock = $this->service->create($this->user, [
            'name' => '  Lakban  ', 'sku' => '', 'description' => ' ', 'quantity' => '3',
            'unit' => '', 'price' => '', 'min_stock' => '5',
        ]);

        $this->assertSame($this->user->id, $stock->user_id);
        $this->assertSame('Lakban', $stock->name);
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertNull($stock->price);
        $this->assertSame('pcs', $stock->unit);
        $this->assertSame('low_stock', $stock->status->value);

        $this->assertSame('out_of_stock', $this->service->create($this->user, ['name' => 'X', 'quantity' => 0, 'min_stock' => 5])->status->value);
        $this->assertSame('available', $this->service->create($this->user, ['name' => 'Y', 'quantity' => 9, 'min_stock' => 5, 'price' => '1500.5'])->status->value);
    }

    public function test_create_uses_defaults_when_keys_missing(): void
    {
        $stock = $this->service->create($this->user, ['name' => 'Z']);

        $this->assertSame(0, $stock->quantity);
        $this->assertSame(5, $stock->min_stock);
        $this->assertSame('out_of_stock', $stock->status->value);
    }

    public function test_update_recomputes_status(): void
    {
        $stock = Stock::factory()->for($this->user)->create(['quantity' => 50, 'min_stock' => 5, 'status' => 'available']);

        $updated = $this->service->update($stock, ['name' => 'Baru', 'quantity' => 4, 'min_stock' => 10, 'unit' => 'box']);

        $this->assertSame('Baru', $updated->fresh()->name);
        $this->assertSame('low_stock', $updated->fresh()->status->value);
        $this->assertSame('box', $updated->fresh()->unit);
    }

    public function test_adjust_quantity_syncs_status_and_never_negative(): void
    {
        $stock = Stock::factory()->for($this->user)->create(['quantity' => 7, 'min_stock' => 5, 'status' => 'available']);

        $this->assertSame('low_stock', $this->service->adjustQuantity($stock, -2)->status->value);
        $this->assertSame(5, $stock->quantity);
        $this->assertSame('out_of_stock', $this->service->adjustQuantity($stock, -100)->status->value);
        $this->assertSame(0, $stock->fresh()->quantity);
        $this->assertSame('low_stock', $this->service->adjustQuantity($stock, 1)->status->value);
        $this->assertSame('available', $this->service->adjustQuantity($stock, 10)->status->value);
    }

    public function test_adjust_quantity_is_capped(): void
    {
        $stock = Stock::factory()->for($this->user)->create(['quantity' => 10, 'min_stock' => 5]);

        $this->service->adjustQuantity($stock, PHP_INT_MAX);
        $this->assertSame(StockService::MAX_QUANTITY, $stock->fresh()->quantity);

        $this->service->adjustQuantity($stock, PHP_INT_MIN);
        $this->assertSame(0, $stock->fresh()->quantity);
    }

    public function test_delete_removes_cover_file_when_present(): void
    {
        Storage::disk('public')->put('stock-covers/a.jpg', 'x');
        $withCover = Stock::factory()->for($this->user)->create(['cover_image_path' => 'stock-covers/a.jpg']);
        $without = Stock::factory()->for($this->user)->create();

        $this->service->delete($withCover);
        $this->service->delete($without);

        Storage::disk('public')->assertMissing('stock-covers/a.jpg');
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_create_many(): void
    {
        $count = $this->service->createMany($this->user, [
            ['name' => 'A', 'quantity' => 1, 'min_stock' => 5],
            ['name' => 'B', 'quantity' => 20, 'min_stock' => 5],
        ]);

        $this->assertSame(2, $count);
        $this->assertSame(2, $this->user->stocks()->count());
    }

    public function test_store_and_replace_and_remove_cover(): void
    {
        $stock = Stock::factory()->for($this->user)->create();

        $this->service->storeCover($stock, UploadedFile::fake()->image('one.jpg'));
        $first = $stock->fresh()->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->service->storeCover($stock, UploadedFile::fake()->image('two.jpg'));
        $second = $stock->fresh()->cover_image_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->service->removeCover($stock);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($stock->fresh()->cover_image_path);
    }

    public function test_read_helpers(): void
    {
        $stock = Stock::factory()->for($this->user)->create(['quantity' => 0, 'status' => 'out_of_stock']);

        $this->assertTrue($this->service->find($stock->id)->is($stock));
        $this->assertCount(1, $this->service->allForUser($this->user->id));
        $this->assertSame(1, $this->service->paginate($this->user->id, null, null)->total());
        $this->assertSame(1, $this->service->summary($this->user->id)['out_of_stock']);
    }
}
