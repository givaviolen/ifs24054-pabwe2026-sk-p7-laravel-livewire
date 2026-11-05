<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
        $this->actingAs($this->admin);
    }

    public function test_user_list_shows_users_with_stock_counts(): void
    {
        $member = User::factory()->create(['name' => 'Budi Member']);
        Stock::factory()->count(3)->for($member)->create();

        $this->get('/admin/users')->assertOk()->assertSeeLivewire(UserList::class);

        Livewire::test(UserList::class)
            ->assertSee('Budi Member')
            ->assertSee('Admin Utama')
            ->assertSee(route('admin.users.stocks', $member));
    }

    public function test_user_list_search_and_empty_state(): void
    {
        User::factory()->create(['name' => 'Citra Lestari']);

        Livewire::test(UserList::class)
            ->set('search', 'Citra')
            ->assertSee('Citra Lestari')
            ->assertDontSee('Admin Utama')
            ->set('search', 'tidak-ada-orang-ini')
            ->assertSee('Tidak ada user.');
    }

    public function test_user_list_paginates(): void
    {
        User::factory()->count(12)->create();

        Livewire::test(UserList::class)
            ->assertViewHas('users', fn ($users) => $users->count() === 10 && $users->total() === 13)
            ->set('search', 'a'); // memicu resetPage
    }

    public function test_stock_list_of_user_is_read_only_view(): void
    {
        $member = User::factory()->create(['name' => 'Budi Member']);
        Stock::factory()->for($member)->create(['name' => 'Kertas Milik Budi', 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->create(['name' => 'Barang Orang Lain']);

        $this->get('/admin/users/'.$member->id.'/stocks')->assertOk()->assertSeeLivewire(AdminUserStockList::class);

        Livewire::test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Budi Member')
            ->assertSee('Kertas Milik Budi')
            ->assertDontSee('Barang Orang Lain')
            ->assertDontSee('Hapus')
            ->assertDontSee('Edit')
            ->assertViewHas('summary', fn ($s) => $s['total'] === 1 && $s['out_of_stock'] === 1);
    }

    public function test_stock_list_of_user_search_filter_and_empty(): void
    {
        $member = User::factory()->create();
        Stock::factory()->for($member)->create(['name' => 'Alpha', 'quantity' => 50, 'status' => 'available']);
        Stock::factory()->for($member)->create(['name' => 'Beta', 'quantity' => 0, 'status' => 'out_of_stock']);

        Livewire::test(AdminUserStockList::class, ['user' => $member])
            ->set('search', 'Alpha')->assertSee('Alpha')->assertDontSee('Beta')
            ->set('search', '')->set('statusFilter', 'out_of_stock')->assertSee('Beta')->assertDontSee('Alpha')
            ->set('statusFilter', '')->set('search', 'zzz')->assertSee('User ini belum punya stok.');
    }

    public function test_admin_component_has_no_mutating_actions(): void
    {
        foreach (['save', 'delete', 'adjust', 'openEdit', 'openCreate', 'import', 'saveAi'] as $method) {
            $this->assertFalse(method_exists(AdminUserStockList::class, $method), "AdminUserStockList tidak boleh punya {$method}()");
        }
    }
}
