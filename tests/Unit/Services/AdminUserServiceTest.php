<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AdminUserService::class);
    }

    public function test_users_include_stock_counts(): void
    {
        $member = User::factory()->create(['name' => 'Budi']);
        Stock::factory()->for($member)->create(['quantity' => 50, 'status' => 'available']);
        Stock::factory()->for($member)->create(['quantity' => 2, 'status' => 'low_stock']);
        Stock::factory()->for($member)->create(['quantity' => 0, 'status' => 'out_of_stock']);
        User::factory()->admin()->create(['name' => 'Adi']);

        $page = $this->service->paginateUsers(null);
        $budi = $page->firstWhere('name', 'Budi');

        $this->assertSame(2, $page->total());
        $this->assertSame(3, $budi->stocks_count);
        $this->assertSame(1, $budi->low_stock_count);
        $this->assertSame(1, $budi->out_of_stock_count);
        $this->assertSame(['Adi', 'Budi'], $page->pluck('name')->all());
    }

    public function test_user_search(): void
    {
        User::factory()->create(['name' => 'Citra', 'username' => 'citra', 'email' => 'c@x.test']);
        User::factory()->create(['name' => 'Dodi', 'username' => 'dodi', 'email' => 'd@x.test']);

        $this->assertSame(1, $this->service->paginateUsers('citra')->total());
        $this->assertSame(1, $this->service->paginateUsers('d@x')->total());
        $this->assertSame(2, $this->service->paginateUsers('')->total());
    }

    public function test_stocks_and_summary_of_user(): void
    {
        $member = User::factory()->create();
        Stock::factory()->for($member)->create(['name' => 'Kertas', 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->for($member)->create(['name' => 'Pulpen', 'quantity' => 50, 'status' => 'available']);
        Stock::factory()->create();

        $this->assertSame(2, $this->service->paginateStocks($member, null, null)->total());
        $this->assertSame(1, $this->service->paginateStocks($member, 'Kertas', null)->total());
        $this->assertSame(1, $this->service->paginateStocks($member, null, 'available')->total());
        $this->assertSame(['total' => 2, 'available' => 1, 'low_stock' => 0, 'out_of_stock' => 1], $this->service->summary($member));
    }

    public function test_service_exposes_no_mutating_methods(): void
    {
        $methods = get_class_methods(AdminUserService::class);

        $this->assertSame([], array_values(array_filter($methods, fn ($m) => preg_match('/^(create|update|delete|adjust|store|remove)/', $m))));
    }
}
