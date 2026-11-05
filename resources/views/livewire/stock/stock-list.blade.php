<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Stok Barang</h1>
        <div class="flex flex-wrap gap-2">
            <button wire:click="openCreate" class="rounded bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700">+ Tambah stok</button>
            <button wire:click="openAi" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-100">Generate AI</button>
            <button wire:click="openImport" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-100">Import Excel</button>
            <button wire:click="exportExcel" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-100">Export Excel</button>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU…"
               class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm sm:w-72">
        <select wire:model.live="statusFilter" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Barang</th>
                    <th class="px-4 py-3">Quantity</th>
                    <th class="px-4 py-3">Harga</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($stocks as $stock)
                    <tr wire:key="stock-{{ $stock->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('stocks.show', $stock) }}" class="font-medium text-teal-700 hover:underline">{{ $stock->name }}</a>
                            <p class="text-xs text-slate-500">{{ $stock->sku ?: 'Tanpa SKU' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="inline-flex items-center overflow-hidden rounded border border-slate-300">
                                <button wire:click="adjust({{ $stock->id }}, -1)" class="px-2 py-1 hover:bg-slate-100" aria-label="Kurangi">−</button>
                                <span class="min-w-[4.5rem] px-2 text-center">{{ number_format($stock->quantity) }} {{ $stock->unit }}</span>
                                <button wire:click="adjust({{ $stock->id }}, 1)" class="px-2 py-1 hover:bg-slate-100" aria-label="Tambah">+</button>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Min. {{ $stock->min_stock }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '—' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="openEdit({{ $stock->id }})" class="text-teal-700 hover:underline">Edit</button>
                            <button wire:click="confirmDelete({{ $stock->id }})" class="ml-3 text-red-600 hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada stok yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $stocks->links() }}

    {{-- Modal create / edit --}}
    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <form wire:submit="save" class="max-h-full w-full max-w-lg space-y-3 overflow-y-auto rounded-lg bg-white p-5 shadow-xl">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit stok' : 'Tambah stok' }}</h2>

                <div>
                    <label class="mb-1 block text-sm">Nama barang</label>
                    <input type="text" wire:model="name" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm">SKU</label>
                    <input type="text" wire:model="sku" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-sm">Quantity</label>
                        <input type="number" min="0" wire:model="quantity" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm">Satuan</label>
                        <input type="text" wire:model="unit" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm">Min. stok</label>
                        <input type="number" min="0" wire:model="min_stock" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        @error('min_stock') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm">Harga (Rp)</label>
                    <input type="number" min="0" step="0.01" wire:model="price" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm">Deskripsi (Markdown)</label>
                    <textarea wire:model="description" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 font-mono text-sm"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <p class="text-xs text-slate-500">Status (tersedia / menipis / habis) dihitung otomatis dari quantity dan min. stok.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeForm" class="rounded border border-slate-300 px-4 py-2 text-sm">Batal</button>
                    <button type="submit" class="rounded bg-teal-600 px-4 py-2 text-sm text-white">Simpan</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Konfirmasi hapus --}}
    @if ($confirmDeleteId)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-sm rounded-lg bg-white p-5 shadow-xl">
                <h2 class="text-lg font-semibold">Hapus stok?</h2>
                <p class="mt-1 text-sm text-slate-600">Data stok dan cover-nya akan dihapus permanen.</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="cancelDelete" class="rounded border border-slate-300 px-4 py-2 text-sm">Batal</button>
                    <button wire:click="delete" class="rounded bg-red-600 px-4 py-2 text-sm text-white">Hapus</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal import --}}
    @if ($showImport)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <form wire:submit="import" class="w-full max-w-md space-y-3 rounded-lg bg-white p-5 shadow-xl">
                <h2 class="text-lg font-semibold">Import dari Excel</h2>
                <p class="text-sm text-slate-600">File .xlsx dengan header: <code>name, sku, quantity, unit, price, min_stock, description</code>. Status dihitung otomatis. Baris tidak valid dilewati.</p>
                <input type="file" wire:model="importFile" accept=".xlsx" class="text-sm">
                @error('importFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeImport" class="rounded border border-slate-300 px-4 py-2 text-sm">Batal</button>
                    <button type="submit" class="rounded bg-teal-600 px-4 py-2 text-sm text-white">Import</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Modal AI --}}
    @if ($showAi)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="max-h-full w-full max-w-2xl space-y-3 overflow-y-auto rounded-lg bg-white p-5 shadow-xl">
                <h2 class="text-lg font-semibold">Generate item stok dengan AI</h2>

                <form wire:submit="generateAi" class="space-y-3">
                    <div>
                        <label class="mb-1 block text-sm">Deskripsikan kebutuhan inventaris</label>
                        <textarea wire:model="aiPrompt" rows="3" placeholder="Contoh: perlengkapan kantor untuk 20 karyawan"
                                  class="w-full rounded border border-slate-300 px-3 py-2 text-sm"></textarea>
                        @error('aiPrompt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end gap-3">
                        <div>
                            <label class="mb-1 block text-sm">Jumlah item (1–10)</label>
                            <input type="number" min="1" max="10" wire:model="aiCount" class="w-24 rounded border border-slate-300 px-3 py-2 text-sm">
                            @error('aiCount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="rounded bg-teal-600 px-4 py-2 text-sm text-white">
                            <span wire:loading.remove wire:target="generateAi">Generate</span>
                            <span wire:loading wire:target="generateAi">Menghasilkan…</span>
                        </button>
                    </div>
                </form>

                @if ($aiItems)
                    <div class="rounded border border-slate-200">
                        <p class="border-b border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium">Preview — pilih item yang akan disimpan</p>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($aiItems as $i => $item)
                                <li class="flex gap-3 px-3 py-2 text-sm" wire:key="ai-{{ $i }}">
                                    <input type="checkbox" wire:model="aiSelected" value="{{ $i }}" class="mt-1">
                                    <div>
                                        <p class="font-medium">{{ $item['name'] }} <span class="font-normal text-slate-500">({{ $item['sku'] ?? 'tanpa SKU' }})</span></p>
                                        <p class="text-xs text-slate-500">{{ $item['quantity'] }} {{ $item['unit'] }} · min. {{ $item['min_stock'] }} · {{ $item['price'] !== null ? 'Rp '.number_format($item['price'], 0, ',', '.') : 'tanpa harga' }}</p>
                                        @if ($item['description'])<p class="mt-0.5 text-xs text-slate-600">{{ $item['description'] }}</p>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @error('aiSelected') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @endif

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeAi" class="rounded border border-slate-300 px-4 py-2 text-sm">Tutup</button>
                    @if ($aiItems)
                        <button type="button" wire:click="saveAi" class="rounded bg-teal-600 px-4 py-2 text-sm text-white">Simpan terpilih</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
