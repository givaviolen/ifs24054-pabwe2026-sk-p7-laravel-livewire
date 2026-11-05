<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    public const MAX_ATTEMPTS = 5;

    /** @throws ValidationException bila kredensial salah / terlalu banyak percobaan. */
    public function execute(string $login, string $password, bool $remember = false): void
    {
        $key = Str::lower($login).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'login' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $login, 'password' => $password], $remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['login' => 'Username/email atau password salah.']);
        }

        RateLimiter::clear($key);
    }
}
