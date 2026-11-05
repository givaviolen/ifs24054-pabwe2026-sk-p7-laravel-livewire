<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Daftar User</h1>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama, username, email…"
               class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm sm:w-72">
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Total stok</th>
                    <th class="px-4 py-3">Menipis</th>
                    <th class="px-4 py-3">Habis</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->username }} · {{ $user->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $user->role }}</td>
                        <td class="px-4 py-3">{{ $user->stocks_count }}</td>
                        <td class="px-4 py-3">{{ $user->low_stock_count }}</td>
                        <td class="px-4 py-3">{{ $user->out_of_stock_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.users.stocks', $user) }}" class="text-teal-700 hover:underline">Lihat stok</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Tidak ada user.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
