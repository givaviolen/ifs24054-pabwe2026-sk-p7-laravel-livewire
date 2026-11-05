<?php

namespace Tests\Unit\Repositories;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private StockRepository $repo;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StockRepository;
        $this->user = User::factory()->create();
    }

    public function test_paginate_is_scoped_to_user_and_newest_first(): void
    {
        $first = Stock::factory()->for($this->user)->create();
        $second = Stock::factory()->for($this->user)->create();
        Stock::factory()->create();

        $page = $this->repo->paginateForUser($this->user->id, null, null, 10);

        $this->assertSame(2, $page->total());
        $this->assertSame([$second->id, $first->id], $page->pluck('id')->all());
    }

    public function test_search_matches_name_or_sku_and_filters_status(): void
    {
        Stock::factory()->for($this->user)->create(['name' => 'Kertas HVS', 'sku' => 'AAA', 'quantity' => 50, 'status' => 'available']);
        Stock::factory()->for($this->user)->create(['name' => 'Pulpen', 'sku' => 'KRT-9', 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->for($this->user)->create(['name' => 'Lakban', 'sku' => 'ZZZ', 'quantity' => 2, 'status' => 'low_stock']);

        $this->assertSame(2, $this->repo->paginateForUser($this->user->id, 'k', null)->total()); // Kertas (name) + KRT-9 (sku)
        $this->assertSame(1, $this->repo->paginateForUser($this->user->id, 'Lakban', null)->total());
        $this->assertSame(1, $this->repo->paginateForUser($this->user->id, null, 'out_of_stock')->total());
        $this->assertSame(0, $this->repo->paginateForUser($this->user->id, 'Pulpen', 'available')->total());
        $this->assertSame(3, $this->repo->paginateForUser($this->user->id, '', '')->total());
    }

    public function test_pagination_size(): void
    {
        Stock::factory()->count(7)->for($this->user)->create();

        $page = $this->repo->paginateForUser($this->user->id, null, null, 5);

        $this->assertCount(5, $page->items());
        $this->assertSame(2, $page->lastPage());
    }

    public function test_all_for_user_is_sorted_by_name(): void
    {
        Stock::factory()->for($this->user)->create(['name' => 'B']);
        Stock::factory()->for($this->user)->create(['name' => 'A']);
        Stock::factory()->create(['name' => 'C']);

        $this->assertSame(['A', 'B'], $this->repo->allForUser($this->user->id)->pluck('name')->all());
    }

    public function test_find_or_fail(): void
    {
        $stock = Stock::factory()->create();

        $this->assertTrue($this->repo->findOrFail($stock->id)->is($stock));

        $this->expectException(ModelNotFoundException::class);
        $this->repo->findOrFail(999999);
    }

    public function test_create_update_delete(): void
    {
        $stock = $this->repo->create(['user_id' => $this->user->id, 'name' => 'Baru', 'quantity' => 1, 'status' => 'low_stock']);
        $this->assertDatabaseHas('stocks', ['name' => 'Baru']);

        $updated = $this->repo->update($stock, ['name' => 'Ubah']);
        $this->assertSame('Ubah', $updated->name);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'name' => 'Ubah']);

        $this->repo->delete($stock);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_summary(): void
    {
        Stock::factory()->count(2)->for($this->user)->create(['quantity' => 50, 'min_stock' => 5, 'status' => 'available']);
        Stock::factory()->for($this->user)->create(['quantity' => 2, 'min_stock' => 5, 'status' => 'low_stock']);
        Stock::factory()->for($this->user)->create(['quantity' => 0, 'min_stock' => 5, 'status' => 'out_of_stock']);
        Stock::factory()->create();

        $this->assertSame(
            ['total' => 4, 'available' => 2, 'low_stock' => 1, 'out_of_stock' => 1],
            $this->repo->summaryForUser($this->user->id)
        );
        $this->assertSame(
            ['total' => 0, 'available' => 0, 'low_stock' => 0, 'out_of_stock' => 0],
            $this->repo->summaryForUser(User::factory()->create()->id)
        );
    }
}
