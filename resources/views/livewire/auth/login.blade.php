<div class="w-full max-w-sm rounded-lg bg-white p-6 shadow">
    <h1 class="text-xl font-semibold">Masuk</h1>
    <p class="mb-5 text-sm text-slate-500">Kelola inventaris stok barang Anda.</p>

    <form wire:submit="submit" class="space-y-4">
        <div>
            <label for="login-username" class="mb-1 block text-sm font-medium">Username / Email</label>
            <input id="login-username" type="text" wire:model="login" autocomplete="username" autofocus
                   class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            @error('login') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="login-password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="login-password" type="password" wire:model="password" autocomplete="current-password"
                   class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember" class="rounded border-slate-300"> Ingat saya
        </label>
        <button id="login-submit" type="submit" class="w-full rounded bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
            <span wire:loading.remove wire:target="submit">Masuk</span>
            <span wire:loading wire:target="submit">Memproses…</span>
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-500">Belum punya akun?
        <a href="{{ route('register') }}" class="text-teal-700 underline">Daftar</a></p>
</div>
