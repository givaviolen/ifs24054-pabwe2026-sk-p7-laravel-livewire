<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Stok')]
class StockDetail extends Component
{
    use WithFileUploads;

    #[Locked]
    public Stock $stock;

    public bool $editing = false;

    public $name = '';

    public $sku = '';

    public $description = '';

    public $quantity = 0;

    public $unit = 'pcs';

    public $price = null;

    public $min_stock = 5;

    public $adjustStep = 1;

    public $coverFile = null;

    public function mount(Stock $stock): void
    {
        $this->authorize('view', $stock);
        $this->stock = $stock;
    }

    public function edit(): void
    {
        $this->authorize('update', $this->stock);

        $this->name = $this->stock->name;
        $this->sku = $this->stock->sku;
        $this->description = $this->stock->description;
        $this->quantity = $this->stock->quantity;
        $this->unit = $this->stock->unit;
        $this->price = $this->stock->price;
        $this->min_stock = $this->stock->min_stock;
        $this->resetErrorBag();
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;
    }

    public function save(): void
    {
        $this->authorize('update', $this->stock);
        $data = $this->validate(StockService::rules());

        $this->service()->update($this->stock, $data);
        $this->editing = false;
        session()->flash('status', 'Stok berhasil diperbarui.');
    }

    /** $direction: 1 = tambah, -1 = kurangi; besar langkah dari $adjustStep. */
    public function adjust(int $direction): void
    {
        $this->authorize('update', $this->stock);

        $step = max(1, (int) $this->adjustStep);
        $this->service()->adjustQuantity($this->stock, $direction < 0 ? -$step : $step);
    }

    public function updatedCoverFile(): void
    {
        $this->authorize('update', $this->stock);
        $this->validateOnly('coverFile', [
            'coverFile' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->service()->storeCover($this->stock, $this->coverFile);
        $this->reset('coverFile');
        session()->flash('status', 'Cover berhasil diperbarui.');
    }

    public function removeCover(): void
    {
        $this->authorize('update', $this->stock);
        $this->service()->removeCover($this->stock);
        session()->flash('status', 'Cover dihapus.');
    }

    public function render()
    {
        return view('livewire.stock.stock-detail');
    }

    private function service(): StockService
    {
        return app(StockService::class);
    }
}
