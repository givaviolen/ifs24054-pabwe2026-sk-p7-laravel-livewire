<?php
namespace App\Features\Stock\Services;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Contracts\StockRepositoryInterface;

class StockService {
    protected $repository;

    public function __construct(StockRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function adjustQuantity(Stock $stock, int $adjustment): void {
        $newQuantity = max(0, $stock->quantity + $adjustment);
        
        if ($newQuantity === 0) {
            $status = StockStatus::OUT_OF_STOCK->value;
        } elseif ($newQuantity <= $stock->min_stock) {
            $status = StockStatus::LOW_STOCK->value;
        } else {
            $status = StockStatus::AVAILABLE->value;
        }

        $this->repository->update($stock, [
            'quantity' => $newQuantity,
            'status' => $status
        ]);
    }
}
