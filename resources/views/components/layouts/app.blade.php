<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Stok Barang' }} — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .md-body h1,.md-body h2,.md-body h3{font-weight:600;margin:.75rem 0 .35rem}
        .md-body p{margin:.4rem 0}
        .md-body ul{list-style:disc;padding-left:1.25rem;margin:.4rem 0}
        .md-body ol{list-style:decimal;padding-left:1.25rem;margin:.4rem 0}
        .md-body a{color:#0f766e;text-decoration:underline}
        .md-body code{background:#f1f5f9;padding:.05rem .3rem;border-radius:.25rem}
        .md-body pre{background:#f1f5f9;padding:.6rem;border-radius:.375rem;overflow:auto}
    </style>
    @stack('head')
    @livewireStyles
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased">
@auth
    @php($me = auth()->user())
    <div class="min-h-full md:flex">
        <aside class="bg-slate-900 text-slate-200 md:w-60 md:min-h-screen md:shrink-0">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-800">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded bg-teal-500 font-bold text-slate-900">S</span>
                <span class="font-semibold">{{ config('app.name') }}</span>
            </div>
            <nav class="flex gap-1 overflow-x-auto p-3 md:flex-col">
                @if($me->isAdmin())
                    <a href="{{ route('admin.users') }}" class="rounded px-3 py-2 text-sm hover:bg-slate-800 {{ request()->routeIs('admin.*') ? 'bg-slate-800 text-white' : '' }}">Daftar User</a>
                @else
                    <a href="{{ route('stocks.index') }}" class="rounded px-3 py-2 text-sm hover:bg-slate-800 {{ request()->routeIs('stocks.*') ? 'bg-slate-800 text-white' : '' }}">Stok Barang</a>
                @endif
                <a href="{{ route('profile') }}" class="rounded px-3 py-2 text-sm hover:bg-slate-800 {{ request()->routeIs('profile') ? 'bg-slate-800 text-white' : '' }}">Profil</a>
            </nav>
            <div class="mt-auto hidden border-t border-slate-800 p-4 md:block">
                <div class="flex items-center gap-3">
                    @if($me->profilePhotoUrl())
                        <img src="{{ $me->profilePhotoUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                    @else
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-700 text-sm">{{ strtoupper(substr($me->name, 0, 1)) }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-white">{{ $me->name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $me->role }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full rounded bg-slate-800 px-3 py-2 text-sm hover:bg-slate-700">Keluar</button>
                </form>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="p-3 md:hidden">
                @csrf
                <button type="submit" class="rounded bg-slate-800 px-3 py-2 text-sm">Keluar</button>
            </form>
        </aside>
        <main class="min-w-0 flex-1 p-4 md:p-8">{{ $slot }}</main>
    </div>
@else
    <main class="flex min-h-screen items-center justify-center p-4">{{ $slot }}</main>
@endauth
@stack('scripts')
@livewireScripts
</body>
</html>
