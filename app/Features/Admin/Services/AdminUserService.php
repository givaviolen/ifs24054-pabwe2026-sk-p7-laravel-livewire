<?php

namespace App\Features\Admin\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Layanan admin: hanya BACA. Tidak ada method yang mengubah/menghapus stok. */
class AdminUserService
{
    public function __construct(private readonly StockRepositoryInterface $stocks) {}

    public function paginateUsers(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->withCount([
                'stocks',
                'stocks as low_stock_count' => fn ($q) => $q->where('status', StockStatus::LowStock->value),
                'stocks as out_of_stock_count' => fn ($q) => $q->where('status', StockStatus::OutOfStock->value),
            ])
            ->when(filled($search), fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateStocks(User $user, ?string $search, ?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stocks->paginateForUser($user->id, $search, $status, $perPage);
    }

    public function summary(User $user): array
    {
        return $this->stocks->summaryForUser($user->id);
    }
}
