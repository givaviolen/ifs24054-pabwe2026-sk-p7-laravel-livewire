<?php

namespace App\Features\Stock\Contracts;

use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockRepositoryInterface
{
    public function paginateForUser(int $userId, ?string $search, ?string $status, int $perPage = 10): LengthAwarePaginator;

    /** @return Collection<int, Stock> */
    public function allForUser(int $userId): Collection;

    public function findOrFail(int $id): Stock;

    public function create(array $data): Stock;

    public function update(Stock $stock, array $data): Stock;

    public function delete(Stock $stock): void;

    /** @return array{total:int, available:int, low_stock:int, out_of_stock:int} */
    public function summaryForUser(int $userId): array;
}
