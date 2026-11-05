<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->member = User::factory()->create();
        $this->stock = Stock::factory()->for($this->member)->create([
            'name' => 'Kertas A4', 'quantity' => 10, 'min_stock' => 5, 'status' => 'available',
            'description' => '**Tebal** dan putih',
        ]);
        $this->actingAs($this->member);
    }

    private function component()
    {
        return Livewire::test(StockDetail::class, ['stock' => $this->stock]);
    }

    public function test_shows_detail_with_rendered_markdown(): void
    {
        $this->component()
            ->assertSee('Kertas A4')
            ->assertSeeHtml('<strong>Tebal</strong>');
    }

    public function test_shows_placeholder_without_description(): void
    {
        $this->stock->update(['description' => null]);

        $this->component()->assertSee('Belum ada deskripsi.');
    }

    public function test_other_member_is_forbidden(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(StockDetail::class, ['stock' => $this->stock])
            ->assertForbidden();
    }

    public function test_admin_can_view_but_not_modify(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(StockDetail::class, ['stock' => $this->stock])->assertOk()->assertSee('Kertas A4');
        Livewire::actingAs($admin)->test(StockDetail::class, ['stock' => $this->stock])->call('edit')->assertForbidden();
        Livewire::actingAs($admin)->test(StockDetail::class, ['stock' => $this->stock])->call('adjust', 1)->assertForbidden();
        Livewire::actingAs($admin)->test(StockDetail::class, ['stock' => $this->stock])->call('removeCover')->assertForbidden();

        $this->assertSame(10, $this->stock->fresh()->quantity);
    }

    public function test_edit_and_save(): void
    {
        $this->component()
            ->call('edit')
            ->assertSet('editing', true)
            ->assertSet('name', 'Kertas A4')
            ->assertSet('quantity', 10)
            ->set('name', 'Kertas F4')->set('quantity', 3)->set('price', '12000')->set('description', '# Judul')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editing', false)
            ->assertSee('Stok berhasil diperbarui.');

        $fresh = $this->stock->fresh();
        $this->assertSame('Kertas F4', $fresh->name);
        $this->assertSame('low_stock', $fresh->status->value);
        $this->assertSame('# Judul', $fresh->description);
    }

    public function test_save_validation_and_cancel(): void
    {
        $this->component()->call('edit')->set('name', '')->call('save')->assertHasErrors(['name'])->assertSet('editing', true);

        $this->component()->call('edit')->call('cancel')->assertSet('editing', false);
    }

    public function test_adjust_with_step(): void
    {
        $component = $this->component();

        $component->call('adjust', 1);
        $this->assertSame(11, $this->stock->fresh()->quantity);

        $component->set('adjustStep', 5)->call('adjust', -1);
        $this->assertSame(6, $this->stock->fresh()->quantity);
        $this->assertSame('available', $this->stock->fresh()->status->value);

        $component->set('adjustStep', 5)->call('adjust', -1);
        $this->assertSame(1, $this->stock->fresh()->quantity);
        $this->assertSame('low_stock', $this->stock->fresh()->status->value);

        $component->set('adjustStep', 0)->call('adjust', -1); // langkah minimal 1
        $this->assertSame(0, $this->stock->fresh()->quantity);
        $this->assertSame('out_of_stock', $this->stock->fresh()->status->value);
    }

    public function test_cover_upload_replace_and_remove(): void
    {
        $component = $this->component()
            ->set('coverFile', UploadedFile::fake()->image('one.jpg'))
            ->assertHasNoErrors()
            ->assertSee('Cover berhasil diperbarui.');

        $first = $this->stock->fresh()->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $component->set('coverFile', UploadedFile::fake()->image('two.png'));
        $second = $this->stock->fresh()->cover_image_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $component->assertSeeHtml('Cover Kertas A4')->call('removeCover')->assertSee('Cover dihapus.');
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($this->stock->fresh()->cover_image_path);
    }

    public function test_cover_must_be_image(): void
    {
        $this->component()
            ->set('coverFile', UploadedFile::fake()->create('berkas.pdf', 10, 'application/pdf'))
            ->assertHasErrors(['coverFile']);

        $this->assertNull($this->stock->fresh()->cover_image_path);
    }
}
