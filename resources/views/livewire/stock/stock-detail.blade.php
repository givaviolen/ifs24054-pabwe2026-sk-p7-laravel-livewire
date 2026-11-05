<div class="mx-auto max-w-3xl space-y-4">
    <a href="{{ route('stocks.index') }}" class="text-sm text-teal-700 hover:underline">← Kembali ke daftar</a>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        @if ($stock->coverUrl())
            <img src="{{ $stock->coverUrl() }}" alt="Cover {{ $stock->name }}" class="h-56 w-full object-cover">
        @endif

        <div class="space-y-4 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold">{{ $stock->name }}</h1>
                    <p class="text-sm text-slate-500">SKU: {{ $stock->sku ?: '—' }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-sm font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-4 rounded bg-slate-50 p-3">
                <div>
                    <p class="text-xs text-slate-500">Quantity</p>
                    <p class="text-xl font-semibold">{{ number_format($stock->quantity) }} <span class="text-sm font-normal text-slate-500">{{ $stock->unit }}</span></p>
                </div>
                <div class="inline-flex items-center gap-2">
                    <button wire:click="adjust(-1)" class="h-9 w-9 rounded border border-slate-300 bg-white hover:bg-slate-100" aria-label="Kurangi">−</button>
                    <input type="number" min="1" wire:model="adjustStep" class="w-20 rounded border border-slate-300 px-2 py-1.5 text-center text-sm" aria-label="Besar langkah">
                    <button wire:click="adjust(1)" class="h-9 w-9 rounded border border-slate-300 bg-white hover:bg-slate-100" aria-label="Tambah">+</button>
                </div>
                <div class="text-sm text-slate-600">
                    <p>Min. stok: {{ $stock->min_stock }}</p>
                    <p>Harga: {{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '—' }}</p>
                </div>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-medium text-slate-600">Cover</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <input type="file" wire:model="coverFile" accept="image/png,image/jpeg,image/webp" class="text-sm">
                    @if ($stock->cover_image_path)
                        <button wire:click="removeCover" class="rounded border border-slate-300 px-3 py-1.5 text-sm">Hapus cover</button>
                    @endif
                </div>
                @error('coverFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if (! $editing)
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <h2 class="text-sm font-medium text-slate-600">Deskripsi</h2>
                        <button wire:click="edit" class="text-sm text-teal-700 hover:underline">Edit stok</button>
                    </div>
                    @if ($stock->descriptionHtml())
                        <div class="md-body text-sm">{!! $stock->descriptionHtml() !!}</div>
                    @else
                        <p class="text-sm text-slate-500">Belum ada deskripsi.</p>
                    @endif
                </div>
            @else
                <form wire:submit="save" class="space-y-3 border-t border-slate-100 pt-4">
                    <div class="grid gap-3 sm:grid-cols-2">
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
                        <div>
                            <label class="mb-1 block text-sm">Harga (Rp)</label>
                            <input type="number" min="0" step="0.01" wire:model="price" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm">Deskripsi (Markdown)</label>
                        <div wire:ignore x-data x-init="
                            const editor = new toastui.Editor({
                                el: $refs.editor, height: '300px', initialEditType: 'markdown',
                                previewStyle: 'tab', usageStatistics: false,
                                initialValue: $wire.description ?? '',
                            });
                            editor.on('change', () => { $wire.description = editor.getMarkdown(); });
                        ">
                            <div x-ref="editor"></div>
                        </div>
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cancel" class="rounded border border-slate-300 px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="rounded bg-teal-600 px-4 py-2 text-sm text-white">Simpan</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
    @push('head')
        <link rel="stylesheet" href="https://uicdn.toast.com/editor/3.2.2/toastui-editor.min.css">
    @endpush
    @push('scripts')
        <script src="https://uicdn.toast.com/editor/3.2.2/toastui-editor-all.min.js"></script>
    @endpush
</div>
