<?php

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Profile\Livewire\Profile;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Livewire\StockList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:1000-per-second'])->group(function () {
    // Root: selalu HTTP 200 (tanpa redirect) untuk guest maupun user yang sudah login.
    // - Guest   -> form login
    // - Admin   -> daftar user
    // - Member  -> daftar stok
    Route::get('/', function () {
        $user = Auth::user();

        $page = match (true) {
            $user === null => Login::class,
            $user->isAdmin() => UserList::class,
            default => StockList::class,
        };

        return view('home', ['page' => $page]);
    })->name('home');

    // Static assets fallbacks to prevent 404 in console
    Route::get('/favicon.svg', function () {
        return response()->file(public_path('favicon.svg'), [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    });

    Route::get('/favicon.ico', function () {
        $path = public_path('favicon.ico');
        if (file_exists($path) && filesize($path) > 0) {
            return response()->file($path, [
                'Content-Type' => 'image/x-icon',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
        return response()->file(public_path('favicon.svg'), [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    });

    Route::get('/robots.txt', function () {
        return response("User-agent: *\nDisallow:", 200, ['Content-Type' => 'text/plain']);
    });

    // Guest Authentication Routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', Login::class)->name('login');
        Route::get('/register', Register::class)->name('register');
    });

    // Authenticated Routes
    Route::middleware('auth')->group(function () {
        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login');
        })->name('logout');

        // Member Stock Routes protected by ownership middleware
        Route::get('/stocks', StockList::class)
            ->middleware('owns.stock')
            ->name('stocks.index');
        Route::get('/stocks/{stock}', StockDetail::class)
            ->middleware('owns.stock')
            ->name('stocks.show');

        // User Profile Route
        Route::get('/profile', Profile::class)->name('profile');

        // Admin Routes protected by Role middleware
        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
            Route::get('/users', UserList::class)->name('users');
            Route::get('/users/{user}/stocks', AdminUserStockList::class)->name('users.stocks');
        });
    });
});