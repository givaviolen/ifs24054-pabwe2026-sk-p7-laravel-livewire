<div class="space-y-4">
    <a href="{{ route('admin.users') }}" class="text-sm text-teal-700 hover:underline">← Kembali ke daftar user</a>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Stok milik {{ $user->name }}</h1>
            <p class="text-sm text-slate-500">{{ $user->username }} · {{ $user->email }} · tampilan hanya-baca</p>
        </div>
        <dl class="flex gap-4 text-sm">
            <div><dt class="text-slate-500">Total</dt><dd class="font-semibold">{{ $summary['total'] }}</dd></div>
            <div><dt class="text-slate-500">Menipis</dt><dd class="font-semibold text-yellow-700">{{ $summary['low_stock'] }}</dd></div>
            <div><dt class="text-slate-500">Habis</dt><dd class="font-semibold text-red-700">{{ $summary['out_of_stock'] }}</dd></div>
        </dl>
    </div>

    <div class="flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU…"
               class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm sm:w-72">
        <select wire:model.live="statusFilter" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">Semua status</option>
            <option value="available">Tersedia</option>
            <option value="low_stock">Stok Menipis</option>
            <option value="out_of_stock">Habis</option>
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
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($stocks as $stock)
                    <tr wire:key="stock-{{ $stock->id }}">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $stock->name }}</p>
                            <p class="text-xs text-slate-500">{{ $stock->sku ?: 'Tanpa SKU' }}</p>
                        </td>
                        <td class="px-4 py-3">{{ number_format($stock->quantity) }} {{ $stock->unit }} <span class="text-xs text-slate-500">(min. {{ $stock->min_stock }})</span></td>
                        <td class="px-4 py-3">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '—' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">User ini belum punya stok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $stocks->links() }}
</div>
