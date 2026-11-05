<div class="w-full max-w-sm rounded-lg bg-white p-6 shadow">
    <h1 class="text-xl font-semibold">Daftar</h1>
    <p class="mb-5 text-sm text-slate-500">Buat akun member baru.</p>

    <form wire:submit="submit" class="space-y-4">
        @foreach ([['name','Nama','text','name'],['username','Username','text','username'],['email','Email','email','email'],['password','Password','password','new-password'],['password_confirmation','Konfirmasi Password','password','new-password']] as [$field,$label,$type,$auto])
            <div>
                <label for="reg-{{ $field }}" class="mb-1 block text-sm font-medium">{{ $label }}</label>
                <input id="reg-{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" autocomplete="{{ $auto }}"
                       class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                @error($field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @endforeach
        <button id="register-submit" type="submit" class="w-full rounded bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Daftar</button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-500">Sudah punya akun?
        <a href="{{ route('login') }}" class="text-teal-700 underline">Masuk</a></p>
</div>
