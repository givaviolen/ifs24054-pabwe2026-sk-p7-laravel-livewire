<div>
    <!-- Toast UI Editor CDN Styles & Scripts -->
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
    <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>

    <!-- Top Navigation Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center space-x-3">
            <a href="{{ route('stocks.index') }}" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-200 transition" title="Kembali ke Daftar Stok">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <div class="flex items-center space-x-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 truncate max-w-md sm:max-w-xl">{{ $stock->name }}</h1>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ \App\Features\Stock\Enums\StockStatus::tryFrom($stock->status)?->badgeClass() ?? 'bg-slate-100 text-slate-700' }}">
                        {{ \App\Features\Stock\Enums\StockStatus::tryFrom($stock->status)?->label() ?? ucfirst($stock->status) }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Dibuat pada {{ $stock->created_at ? $stock->created_at->format('d M Y H:i') : '-' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Edit Stock Button -->
            <button wire:click="openEditModal" wire:loading.attr="disabled" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-75 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                <svg wire:loading.remove wire:target="openEditModal" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <svg wire:loading wire:target="openEditModal" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Edit Stok</span>
            </button>

            <!-- Adjust Quantity Controls (+/-) -->
            <div class="inline-flex items-center border border-slate-300 rounded-lg overflow-hidden bg-white shadow-sm">
                <button type="button" wire:click="adjustQuantity(-1)" wire:loading.attr="disabled" class="px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold transition border-r border-slate-200" title="Kurangi Stok (-1)">
                    −
                </button>
                <span class="px-3 py-1.5 text-xs font-bold text-slate-800">
                    {{ $stock->quantity }} {{ $stock->unit }}
                </span>
                <button type="button" wire:click="adjustQuantity(1)" wire:loading.attr="disabled" class="px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold transition border-l border-slate-200" title="Tambah Stok (+1)">
                    +
                </button>
            </div>

            <!-- Delete Button -->
            <button wire:click="confirmDeleteStock" wire:loading.attr="disabled" class="inline-flex items-center space-x-1 px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-lg transition disabled:opacity-75">
                <svg wire:loading.remove wire:target="confirmDeleteStock" class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <svg wire:loading wire:target="confirmDeleteStock" class="animate-spin w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Hapus</span>
            </button>
        </div>
    </div>

    <!-- Cover Image Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <input type="file" wire:model="coverPhoto" id="coverPhotoInput" aria-label="Upload Cover Foto" accept="image/*" class="hidden">

        @if ($stock->cover_image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($stock->cover_image_path))
            <div class="relative group h-64 sm:h-80 w-full bg-slate-900 overflow-hidden">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($stock->cover_image_path) }}?v={{ time() }}" alt="Cover Stok" class="w-full h-full object-cover">
                
                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-3">
                    <button type="button" onclick="document.getElementById('coverPhotoInput').click()" class="px-4 py-2 bg-white text-slate-800 text-xs font-semibold rounded-xl shadow-lg hover:bg-slate-50 transition flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Ganti Cover</span>
                    </button>
                    <button type="button" wire:click="confirmDeleteCover" wire:loading.attr="disabled" class="px-4 py-2 bg-rose-600 text-white text-xs font-semibold rounded-xl shadow-lg hover:bg-rose-700 disabled:opacity-75 transition flex items-center space-x-1.5">
                        <svg wire:loading.remove wire:target="confirmDeleteCover" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <svg wire:loading wire:target="confirmDeleteCover" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Hapus Cover</span>
                    </button>
                </div>
            </div>
        @else
            <div class="p-8 text-center bg-gradient-to-b from-slate-50 to-white flex flex-col items-center justify-center border-b border-slate-100">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 mb-3 shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-sm font-bold text-slate-800">Tambahkan Cover Gambar untuk Stok Ini</h2>
                <p class="text-xs text-slate-500 mt-1 mb-4">Format gambar JPG, PNG, atau WebP hingga 5MB</p>
                <button type="button" onclick="document.getElementById('coverPhotoInput').click()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                    Upload Cover Foto
                </button>
            </div>
        @endif
        @error('coverPhoto') <span class="text-xs text-rose-600 block p-3 text-center">{{ $message }}</span> @enderror
    </div>

    <!-- Metadata Card & Markdown Description -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Stock Details Meta -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600">Informasi Stok</h2>

            <div>
                <span class="text-xs text-slate-500 block">Kode SKU</span>
                <span class="text-sm font-mono font-semibold text-slate-800">{{ $stock->sku ?? '-' }}</span>
            </div>

            <div>
                <span class="text-xs text-slate-500 block">Jumlah Stok Saat Ini</span>
                <span class="text-lg font-bold text-slate-900">{{ $stock->quantity }} {{ $stock->unit }}</span>
            </div>

            <div>
                <span class="text-xs text-slate-500 block">Ambang Batas Minimum</span>
                <span class="text-sm font-semibold text-slate-700">{{ $stock->min_stock }} {{ $stock->unit }}</span>
            </div>

            <div>
                <span class="text-xs text-slate-500 block">Harga Satuan</span>
                <span class="text-sm font-semibold text-slate-800">
                    {{ $stock->price !== null ? 'Rp ' . number_format($stock->price, 0, ',', '.') : '-' }}
                </span>
            </div>

            <div>
                <span class="text-xs text-slate-500 block">Status Inventaris</span>
                <span class="inline-flex mt-1 px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ \App\Features\Stock\Enums\StockStatus::tryFrom($stock->status)?->badgeClass() ?? 'bg-slate-100 text-slate-700' }}">
                    {{ \App\Features\Stock\Enums\StockStatus::tryFrom($stock->status)?->label() ?? ucfirst($stock->status) }}
                </span>
            </div>
        </div>

        <!-- Markdown Description Viewer -->
        <div class="md:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600 mb-4">Deskripsi Barang (Markdown)</h2>
            @if ($stock->description)
                <div class="prose max-w-none text-sm text-slate-700 leading-relaxed"
                     x-data="{
                        init() {
                            if (typeof toastui !== 'undefined') {
                                new toastui.Editor.factory({
                                    el: this.$el,
                                    viewer: true,
                                    initialValue: @js($stock->description)
                                });
                            }
                        }
                     }">
                    {!! nl2br(e($stock->description)) !!}
                </div>
            @else
                <p class="text-xs text-slate-600 italic">Belum ada deskripsi untuk barang ini. Klik "Edit Stok" untuk menambahkan deskripsi.</p>
            @endif
        </div>
    </div>

    <!-- Edit Stock Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 overflow-y-auto modal-scroll-lock"
            x-data="{
                editor: null,
                init() {
                    this.$nextTick(() => {
                        const container = this.$refs.editorContainer;
                        if (container && typeof toastui !== 'undefined') {
                            this.editor = new toastui.Editor({
                                el: container,
                                height: '220px',
                                initialEditType: 'markdown',
                                previewStyle: 'tab',
                                initialValue: $wire.description || '',
                                events: {
                                    change: () => {
                                        $wire.set('description', this.editor.getMarkdown(), false);
                                    }
                                }
                            });
                        }
                    });
                }
            }">
            <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6 my-8 border border-slate-200">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900">Edit Data Stok</h3>
                    <button wire:click="closeEditModal" aria-label="Tutup modal edit" class="p-1 text-slate-500 hover:text-slate-700 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveStock" class="space-y-4 mt-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="detailName" class="block text-xs font-semibold uppercase text-slate-600 mb-1">Nama Barang *</label>
                            <input wire:model="name" id="detailName" type="text" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="detailSku" class="block text-xs font-semibold uppercase text-slate-600 mb-1">SKU</label>
                            <input wire:model="sku" id="detailSku" type="text" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('sku') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label for="detailQuantity" class="block text-xs font-semibold uppercase text-slate-600 mb-1">Quantity *</label>
                            <input wire:model="quantity" id="detailQuantity" type="number" min="0" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('quantity') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="detailUnit" class="block text-xs font-semibold uppercase text-slate-600 mb-1">Unit *</label>
                            <input wire:model="unit" id="detailUnit" type="text" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('unit') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="detailMinStock" class="block text-xs font-semibold uppercase text-slate-600 mb-1">Min. Stok *</label>
                            <input wire:model="minStock" id="detailMinStock" type="number" min="0" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('minStock') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="detailPrice" class="block text-xs font-semibold uppercase text-slate-600 mb-1">Harga Satuan</label>
                            <input wire:model="price" id="detailPrice" type="number" step="0.01" min="0" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('price') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="detailStatus" class="block text-xs font-semibold uppercase text-slate-600 mb-1">Status *</label>
                        <select wire:model="status" id="detailStatus" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            @foreach ($statuses as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                        @error('status') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div wire:ignore>
                        <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Deskripsi Markdown (Toast UI)</label>
                        <div x-ref="editorContainer" class="border border-slate-300 rounded-lg"></div>
                    </div>
                    @error('description') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror

                    <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-75 rounded-lg shadow-sm transition">
                            <span wire:loading.remove wire:target="saveStock">Simpan Perubahan</span>
                            <span wire:loading wire:target="saveStock">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($confirmingStockDeletion)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 modal-scroll-lock">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Konfirmasi Hapus Stok</h3>
                <p class="text-sm text-slate-600 mb-6">Apakah Anda yakin ingin menghapus data stok "{{ $stock->name }}"? Tindakan ini bersifat permanen.</p>
                <div class="flex justify-end space-x-2">
                    <button wire:click="cancelDeleteStock" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition">Batal</button>
                    <button wire:click="deleteStock" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition">Ya, Hapus Stok</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Cover Confirmation Modal -->
    @if ($confirmingCoverDeletion)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 modal-scroll-lock">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Hapus Cover Foto</h3>
                <p class="text-sm text-slate-600 mb-6">Apakah Anda yakin ingin menghapus cover foto stok ini?</p>
                <div class="flex justify-end space-x-2">
                    <button wire:click="cancelDeleteCover" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition">Batal</button>
                    <button wire:click="deleteCoverPhoto" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition">Hapus Cover</button>
                </div>
            </div>
        </div>
    @endif
</div>
