<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Daftar stok milik satu user — hanya baca (tanpa aksi ubah/hapus). */
#[Layout('components.layouts.app')]
#[Title('Stok User')]
class AdminUserStockList extends Component
{
    use WithPagination;

    #[Locked]
    public User $user;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $service = app(AdminUserService::class);

        return view('livewire.admin.admin-user-stock-list', [
            'stocks' => $service->paginateStocks($this->user, $this->search, $this->statusFilter),
            'summary' => $service->summary($this->user),
        ]);
    }
}
