<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;

#[Layout('components.layouts.app')]
#[Title('Stok Barang')]
class StockList extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    // Form create/edit (modal)
    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    public $name = '';

    public $sku = '';

    public $description = '';

    public $quantity = 0;

    public $unit = 'pcs';

    public $price = null;

    public $min_stock = 5;

    // Hapus
    #[Locked]
    public ?int $confirmDeleteId = null;

    // Import Excel
    public bool $showImport = false;

    public $importFile = null;

    // Generator AI
    public bool $showAi = false;

    public string $aiPrompt = '';

    public $aiCount = 5;

    #[Locked]
    public array $aiItems = [];

    public array $aiSelected = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Stock::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /* ---------- CRUD ---------- */

    public function openCreate(): void
    {
        $this->authorize('create', Stock::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $stock = $this->ownedStock($id);

        $this->resetForm();
        $this->editingId = $stock->id;
        $this->name = $stock->name;
        $this->sku = $stock->sku;
        $this->description = $stock->description;
        $this->quantity = $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price;
        $this->min_stock = $stock->min_stock;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate(StockService::rules());

        if ($this->editingId !== null) {
            $stock = $this->ownedStock($this->editingId);
            $this->service()->update($stock, $data);
            session()->flash('status', 'Stok berhasil diperbarui.');
        } else {
            $this->authorize('create', Stock::class);
            $this->service()->create(auth()->user(), $data);
            session()->flash('status', 'Stok berhasil ditambahkan.');
        }

        $this->closeForm();
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $this->ownedStock($id)->id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(): void
    {
        $stock = $this->ownedStock((int) $this->confirmDeleteId);
        $this->service()->delete($stock);
        $this->confirmDeleteId = null;
        session()->flash('status', 'Stok berhasil dihapus.');
    }

    /** Tambah/kurangi quantity; status ikut disesuaikan. */
    public function adjust(int $id, int $delta): void
    {
        $this->service()->adjustQuantity($this->ownedStock($id), $delta);
    }

    /* ---------- Excel ---------- */

    public function exportExcel(StockExcelService $excel)
    {
        $this->authorize('viewAny', Stock::class);

        $content = $excel->export($this->service()->allForUser(auth()->id()));

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'stok-'.now()->format('Ymd-His').'.xlsx');
    }

    public function openImport(): void
    {
        $this->authorize('create', Stock::class);
        $this->reset('importFile');
        $this->resetErrorBag();
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->reset('importFile', 'showImport');
    }

    public function import(StockExcelService $excel): void
    {
        $this->authorize('create', Stock::class);
        $this->validate(['importFile' => ['required', 'file', 'extensions:xlsx', 'max:2048']]);

        try {
            $result = $excel->import(auth()->user(), $this->importFile->getRealPath());
        } catch (InvalidArgumentException $e) {
            $this->addError('importFile', $e->getMessage());

            return;
        }

        $this->resetPage();
        $this->closeImport();
        session()->flash('status', "Import selesai: {$result['imported']} baris masuk, {$result['skipped']} dilewati.");
    }

    /* ---------- AI ---------- */

    public function openAi(): void
    {
        $this->authorize('create', Stock::class);
        $this->reset('aiPrompt', 'aiItems', 'aiSelected');
        $this->resetErrorBag();
        $this->showAi = true;
    }

    public function closeAi(): void
    {
        $this->reset('showAi', 'aiPrompt', 'aiItems', 'aiSelected');
    }

    public function generateAi(AiClientInterface $ai): void
    {
        $this->authorize('create', Stock::class);
        $this->validate([
            'aiPrompt' => ['required', 'string', 'min:3', 'max:500'],
            'aiCount' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $items = $ai->generate($this->aiPrompt, (int) $this->aiCount);
        } catch (RuntimeException $e) {
            $this->addError('aiPrompt', $e->getMessage());

            return;
        }

        if ($items === []) {
            $this->addError('aiPrompt', 'AI tidak menghasilkan item. Coba perjelas deskripsi.');

            return;
        }

        $this->aiItems = array_map(fn ($item) => $item->toArray(), $items);
        $this->aiSelected = array_map('strval', array_keys($this->aiItems));
    }

    public function saveAi(): void
    {
        $this->authorize('create', Stock::class);

        $chosen = array_intersect_key($this->aiItems, array_flip($this->aiSelected));

        if ($chosen === []) {
            $this->addError('aiSelected', 'Pilih minimal satu item untuk disimpan.');

            return;
        }

        $count = $this->service()->createMany(auth()->user(), $chosen);
        $this->closeAi();
        $this->resetPage();
        session()->flash('status', "{$count} item dari AI berhasil disimpan.");
    }

    public function render()
    {
        $this->authorize('viewAny', Stock::class);

        return view('livewire.stock.stock-list', [
            'stocks' => $this->service()->paginate(
                auth()->id(),
                $this->search,
                StockStatus::tryFrom($this->statusFilter)?->value,
            ),
            'statuses' => StockStatus::cases(),
        ]);
    }

    private function service(): StockService
    {
        return app(StockService::class);
    }

    /** Ambil stok + pastikan pemilik (policy update). */
    private function ownedStock(int $id): Stock
    {
        $stock = $this->service()->find($id);
        $this->authorize('update', $stock);

        return $stock;
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock');
        $this->resetErrorBag();
    }
}
