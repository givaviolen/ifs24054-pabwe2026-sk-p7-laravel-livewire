<x-layouts.app :title="auth()->check() ? (auth()->user()->isAdmin() ? 'Daftar User' : 'Daftar Stok') : 'Login'">
    @livewire($page)
</x-layouts.app>