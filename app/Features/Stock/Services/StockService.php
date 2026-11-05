<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StockService
{
    public const MAX_QUANTITY = 1_000_000_000;

    public function __construct(private readonly StockRepositoryInterface $repository) {}

    /** Aturan validasi bersama (form list, form detail, import Excel). */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:'.self::MAX_QUANTITY],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:'.self::MAX_QUANTITY],
        ];
    }

    public function paginate(int $userId, ?string $search, ?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($userId, $search, $status, $perPage);
    }

    /** @return Collection<int, Stock> */
    public function allForUser(int $userId): Collection
    {
        return $this->repository->allForUser($userId);
    }

    public function summary(int $userId): array
    {
        return $this->repository->summaryForUser($userId);
    }

    public function find(int $id): Stock
    {
        return $this->repository->findOrFail($id);
    }

    public function create(User $user, array $data): Stock
    {
        return $this->repository->create(['user_id' => $user->id] + $this->prepare($data));
    }

    public function update(Stock $stock, array $data): Stock
    {
        return $this->repository->update($stock, $this->prepare($data));
    }

    public function delete(Stock $stock): void
    {
        $this->deleteCoverFile($stock);
        $this->repository->delete($stock);
    }

    /** Tambah/kurangi quantity (tidak pernah < 0) dan sinkronkan status. */
    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $delta = max(-self::MAX_QUANTITY, min(self::MAX_QUANTITY, $delta));
        $quantity = min(self::MAX_QUANTITY, max(0, $stock->quantity + $delta));

        return $this->repository->update($stock, [
            'quantity' => $quantity,
            'status' => StockStatus::fromQuantity($quantity, $stock->min_stock)->value,
        ]);
    }

    /** @param  array<int, array<string, mixed>>  $items */
    public function createMany(User $user, array $items): int
    {
        DB::transaction(function () use ($user, $items) {
            foreach ($items as $item) {
                $this->create($user, $item);
            }
        });

        return count($items);
    }

    public function storeCover(Stock $stock, UploadedFile $file): Stock
    {
        $path = $file->store('stock-covers', 'public');
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, ['cover_image_path' => $path]);
    }

    public function removeCover(Stock $stock): Stock
    {
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, ['cover_image_path' => null]);
    }

    private function deleteCoverFile(Stock $stock): void
    {
        if ($stock->cover_image_path) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }

    /** Normalisasi input + status diturunkan dari quantity vs min_stock. */
    private function prepare(array $data): array
    {
        $quantity = max(0, (int) ($data['quantity'] ?? 0));
        $minStock = max(0, (int) ($data['min_stock'] ?? 5));

        return [
            'name' => trim((string) $data['name']),
            'sku' => $this->nullable($data['sku'] ?? null),
            'description' => $this->nullable($data['description'] ?? null),
            'quantity' => $quantity,
            'unit' => $this->nullable($data['unit'] ?? null) ?? 'pcs',
            'price' => $this->nullable($data['price'] ?? null),
            'min_stock' => $minStock,
            'status' => StockStatus::fromQuantity($quantity, $minStock)->value,
        ];
    }

    private function nullable(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
