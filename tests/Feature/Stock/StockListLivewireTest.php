<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class StockListLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->member = User::factory()->create();
        $this->actingAs($this->member);
    }

    private function mine(array $attrs = []): Stock
    {
        return Stock::factory()->for($this->member)->create($attrs);
    }

    /* ---------- akses & list ---------- */

    public function test_admin_cannot_use_member_component(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(StockList::class)->assertForbidden();
    }

    public function test_lists_only_own_stocks(): void
    {
        $this->mine(['name' => 'Kertas Saya']);
        Stock::factory()->create(['name' => 'Barang Orang Lain']);

        Livewire::test(StockList::class)->assertSee('Kertas Saya')->assertDontSee('Barang Orang Lain');
    }

    public function test_empty_state(): void
    {
        Livewire::test(StockList::class)->assertSee('Belum ada stok yang cocok.');
    }

    public function test_search_and_status_filter(): void
    {
        $this->mine(['name' => 'Kertas HVS', 'sku' => 'KRT', 'quantity' => 50, 'status' => 'available']);
        $this->mine(['name' => 'Pulpen', 'sku' => 'PLP', 'quantity' => 0, 'status' => 'out_of_stock']);

        Livewire::test(StockList::class)
            ->set('search', 'Kertas')->assertSee('Kertas HVS')->assertDontSee('Pulpen')
            ->set('search', 'PLP')->assertSee('Pulpen')->assertDontSee('Kertas HVS')
            ->set('search', '')->set('statusFilter', 'out_of_stock')->assertSee('Pulpen')->assertDontSee('Kertas HVS')
            ->set('statusFilter', 'tidak-valid')->assertSee('Pulpen')->assertSee('Kertas HVS');
    }

    public function test_pagination(): void
    {
        Stock::factory()->count(12)->for($this->member)->create();

        Livewire::test(StockList::class)
            ->assertViewHas('stocks', fn ($p) => $p->count() === 10 && $p->total() === 12)
            ->call('gotoPage', 2)
            ->assertViewHas('stocks', fn ($p) => $p->count() === 2);
    }

    /* ---------- CRUD ---------- */

    public function test_create_stock(): void
    {
        Livewire::test(StockList::class)
            ->call('openCreate')
            ->assertSet('showForm', true)
            ->set('name', 'Lakban')->set('sku', 'LKB-1')->set('description', '**tebal**')
            ->set('quantity', 3)->set('unit', 'roll')->set('price', '7500.5')->set('min_stock', 5)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertSee('Stok berhasil ditambahkan.');

        $this->assertDatabaseHas('stocks', [
            'user_id' => $this->member->id, 'name' => 'Lakban', 'sku' => 'LKB-1',
            'unit' => 'roll', 'min_stock' => 5, 'status' => 'low_stock',
        ]);
    }

    public function test_create_validation(): void
    {
        Livewire::test(StockList::class)
            ->call('openCreate')
            ->set('quantity', -1)->set('min_stock', 'abc')->set('price', 'murah')->set('unit', '')
            ->call('save')
            ->assertHasErrors(['name', 'quantity', 'min_stock', 'price', 'unit']);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_edit_stock(): void
    {
        $stock = $this->mine(['name' => 'Lama', 'quantity' => 3, 'min_stock' => 5, 'status' => 'low_stock']);

        Livewire::test(StockList::class)
            ->call('openEdit', $stock->id)
            ->assertSet('editingId', $stock->id)
            ->assertSet('name', 'Lama')
            ->assertSet('showForm', true)
            ->set('name', 'Baru')->set('quantity', 100)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editingId', null)
            ->assertSee('Stok berhasil diperbarui.');

        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'name' => 'Baru', 'quantity' => 100, 'status' => 'available']);
    }

    public function test_close_form_resets_state(): void
    {
        Livewire::test(StockList::class)
            ->call('openCreate')->set('name', 'Draft')
            ->call('closeForm')
            ->assertSet('showForm', false)->assertSet('name', '');
    }

    public function test_cannot_edit_or_delete_or_adjust_other_users_stock(): void
    {
        $other = Stock::factory()->create(['quantity' => 10]);

        Livewire::test(StockList::class)->call('openEdit', $other->id)->assertForbidden();
        Livewire::test(StockList::class)->call('confirmDelete', $other->id)->assertForbidden();
        Livewire::test(StockList::class)->call('adjust', $other->id, 1)->assertForbidden();

        $this->assertDatabaseHas('stocks', ['id' => $other->id, 'quantity' => 10]);
    }

    public function test_delete_with_confirmation_removes_cover(): void
    {
        Storage::disk('public')->put('stock-covers/c.jpg', 'x');
        $stock = $this->mine(['cover_image_path' => 'stock-covers/c.jpg']);

        Livewire::test(StockList::class)
            ->call('confirmDelete', $stock->id)
            ->assertSet('confirmDeleteId', $stock->id)
            ->assertSee('Hapus stok?')
            ->call('delete')
            ->assertSet('confirmDeleteId', null)
            ->assertSee('Stok berhasil dihapus.');

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
        Storage::disk('public')->assertMissing('stock-covers/c.jpg');
    }

    public function test_cancel_delete(): void
    {
        $stock = $this->mine();

        Livewire::test(StockList::class)
            ->call('confirmDelete', $stock->id)->call('cancelDelete')
            ->assertSet('confirmDeleteId', null);

        $this->assertDatabaseHas('stocks', ['id' => $stock->id]);
    }

    /* ---------- adjust quantity ---------- */

    public function test_adjust_quantity_updates_status(): void
    {
        $stock = $this->mine(['quantity' => 6, 'min_stock' => 5, 'status' => 'available']);
        $component = Livewire::test(StockList::class);

        $component->call('adjust', $stock->id, -1);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 5, 'status' => 'low_stock']);

        $component->call('adjust', $stock->id, -5);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 0, 'status' => 'out_of_stock']);

        $component->call('adjust', $stock->id, -1); // tidak negatif
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 0]);

        $component->call('adjust', $stock->id, 10);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 10, 'status' => 'available']);
    }

    /* ---------- Excel ---------- */

    public function test_export_downloads_xlsx(): void
    {
        $this->mine();

        Livewire::test(StockList::class)->call('exportExcel')->assertFileDownloaded();
    }

    public function test_import_xlsx(): void
    {
        $binary = app(StockExcelService::class)->export(collect([
            Stock::factory()->make(['name' => 'Impor A', 'quantity' => 5, 'min_stock' => 5]),
            Stock::factory()->make(['name' => 'Impor B', 'quantity' => 50, 'min_stock' => 5]),
        ]));

        Livewire::test(StockList::class)
            ->call('openImport')
            ->assertSet('showImport', true)
            ->set('importFile', UploadedFile::fake()->createWithContent('stok.xlsx', $binary))
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('showImport', false)
            ->assertSee('Import selesai: 2 baris masuk, 0 dilewati.');

        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'Impor A', 'status' => 'low_stock']);
        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'Impor B', 'status' => 'available']);
    }

    public function test_import_rejects_corrupt_file(): void
    {
        Livewire::test(StockList::class)
            ->call('openImport')
            ->set('importFile', UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan excel'))
            ->call('import')
            ->assertHasErrors(['importFile'])
            ->assertSet('showImport', true);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_import_validation(): void
    {
        Livewire::test(StockList::class)->call('import')->assertHasErrors(['importFile' => 'required']);

        Livewire::test(StockList::class)
            ->set('importFile', UploadedFile::fake()->create('stok.txt', 5, 'text/plain'))
            ->call('import')
            ->assertHasErrors(['importFile']);
    }

    public function test_close_import(): void
    {
        Livewire::test(StockList::class)->call('openImport')->call('closeImport')->assertSet('showImport', false);
    }

    /* ---------- AI ---------- */

    private function fakeAi(array|RuntimeException $result): void
    {
        $stub = $this->createStub(AiClientInterface::class);

        if ($result instanceof RuntimeException) {
            $stub->method('generate')->willThrowException($result);
        } else {
            $stub->method('generate')->willReturn($result);
        }

        $this->app->instance(AiClientInterface::class, $stub);
    }

    private function aiItems(): array
    {
        return [
            new GeneratedStockItem('Kabel USB', null, 'Kabel data', 5, 'pcs', 15000.0, 3),
            new GeneratedStockItem('Mouse', 'MS-1', null, 50, 'pcs', null, 5),
        ];
    }

    public function test_ai_generate_preview_select_and_save(): void
    {
        $this->fakeAi($this->aiItems());

        Livewire::test(StockList::class)
            ->call('openAi')
            ->assertSet('showAi', true)
            ->set('aiPrompt', 'perlengkapan kantor')
            ->call('generateAi')
            ->assertHasNoErrors()
            ->assertSet('aiSelected', ['0', '1'])
            ->assertSee('Kabel USB')
            ->assertSee('Mouse')
            ->set('aiSelected', ['1'])
            ->call('saveAi')
            ->assertSet('showAi', false)
            ->assertSee('1 item dari AI berhasil disimpan.');

        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'Mouse', 'status' => 'available']);
        $this->assertDatabaseMissing('stocks', ['name' => 'Kabel USB']);
    }

    public function test_ai_failure_is_shown(): void
    {
        $this->fakeAi(new RuntimeException('Layanan AI gagal merespons (HTTP 500).'));

        Livewire::test(StockList::class)
            ->call('openAi')->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt'])
            ->assertSet('aiItems', []);
    }

    public function test_ai_empty_result(): void
    {
        $this->fakeAi([]);

        Livewire::test(StockList::class)
            ->call('openAi')->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt']);
    }

    public function test_ai_validation(): void
    {
        $this->fakeAi($this->aiItems());

        Livewire::test(StockList::class)
            ->call('openAi')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt' => 'required']);

        Livewire::test(StockList::class)
            ->set('aiPrompt', 'ok oke')->set('aiCount', 99)
            ->call('generateAi')
            ->assertHasErrors(['aiCount']);
    }

    public function test_ai_save_requires_selection(): void
    {
        $this->fakeAi($this->aiItems());

        Livewire::test(StockList::class)->call('saveAi')->assertHasErrors(['aiSelected']);

        Livewire::test(StockList::class)
            ->set('aiPrompt', 'kantor')->call('generateAi')
            ->set('aiSelected', [])
            ->call('saveAi')
            ->assertHasErrors(['aiSelected']);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_close_ai_resets_preview(): void
    {
        $this->fakeAi($this->aiItems());

        Livewire::test(StockList::class)
            ->set('aiPrompt', 'kantor')->call('generateAi')
            ->call('closeAi')
            ->assertSet('showAi', false)->assertSet('aiItems', [])->assertSet('aiPrompt', '');
    }

    public function test_admin_cannot_trigger_member_actions_directly(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(StockList::class)->assertForbidden();
        $this->assertFalse($admin->can('create', Stock::class));
    }
}
