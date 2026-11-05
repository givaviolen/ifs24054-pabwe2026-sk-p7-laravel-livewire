<div class="mx-auto max-w-2xl space-y-6">
    <h1 class="text-2xl font-semibold">Profil</h1>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-medium">Foto profil</h2>
        <div class="flex items-center gap-4">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt="Foto profil" class="h-16 w-16 rounded-full object-cover">
            @else
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-200 text-xl">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            @endif
            <form wire:submit="updatePhoto" class="flex flex-wrap items-center gap-2">
                <input type="file" wire:model="photo" accept="image/png,image/jpeg,image/webp" class="text-sm">
                <button type="submit" class="rounded bg-teal-600 px-3 py-1.5 text-sm text-white">Unggah</button>
                @if ($user->profile_photo_path)
                    <button type="button" wire:click="removePhoto" class="rounded border border-slate-300 px-3 py-1.5 text-sm">Hapus</button>
                @endif
            </form>
        </div>
        @error('photo') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    </section>

    <section class="rounded-lg bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-medium">Data akun</h2>
        <form wire:submit="updateProfile" class="space-y-3">
            @foreach ([['name','Nama','text'],['username','Username','text'],['email','Email','email']] as [$f,$l,$t])
                <div>
                    <label class="mb-1 block text-sm">{{ $l }}</label>
                    <input type="{{ $t }}" wire:model="{{ $f }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    @error($f) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <button type="submit" class="rounded bg-teal-600 px-4 py-2 text-sm text-white">Simpan</button>
        </form>
    </section>

    <section class="rounded-lg bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-medium">Ubah password</h2>
        <form wire:submit="updatePassword" class="space-y-3">
            @foreach ([['current_password','Password saat ini'],['password','Password baru'],['password_confirmation','Konfirmasi password baru']] as [$f,$l])
                <div>
                    <label class="mb-1 block text-sm">{{ $l }}</label>
                    <input type="password" wire:model="{{ $f }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    @if ($f !== 'password_confirmation') @error($f) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror @endif
                </div>
            @endforeach
            <button type="submit" class="rounded bg-teal-600 px-4 py-2 text-sm text-white">Ubah password</button>
        </form>
    </section>
</div>
