<?php

namespace App\Features\Stock\Repositories;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function paginateForUser(int $userId, ?string $search, ?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return Stock::query()
            ->where('user_id', $userId)
            ->when(filled($search), fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")
            ))
            ->when(filled($status), fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate($perPage);
    }

    public function allForUser(int $userId): Collection
    {
        return Stock::query()->where('user_id', $userId)->orderBy('name')->get();
    }

    public function findOrFail(int $id): Stock
    {
        return Stock::query()->findOrFail($id);
    }

    public function create(array $data): Stock
    {
        return Stock::query()->create($data);
    }

    public function update(Stock $stock, array $data): Stock
    {
        $stock->fill($data)->save();

        return $stock;
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }

    public function summaryForUser(int $userId): array
    {
        $counts = Stock::query()
            ->where('user_id', $userId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $available = (int) ($counts[StockStatus::Available->value] ?? 0);
        $low = (int) ($counts[StockStatus::LowStock->value] ?? 0);
        $out = (int) ($counts[StockStatus::OutOfStock->value] ?? 0);

        return [
            'total' => $available + $low + $out,
            'available' => $available,
            'low_stock' => $low,
            'out_of_stock' => $out,
        ];
    }
}
