<?php
use Illuminate\Support\Facades\Route;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Profile\Livewire\Profile;
use App\Features\Admin\Livewire\UserList;
use App\Features\Admin\Livewire\AdminUserStockList;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware(['auth', 'throttle:1000,1'])->group(function () {
    Route::post('/logout', function () {
        auth()->logout();
        return redirect('/login');
    })->name('logout');
    
    Route::get('/profile', Profile::class)->name('profile');

    Route::middleware('role:member')->group(function () {
        Route::get('/stocks', StockList::class)->name('stocks.index');
        Route::get('/stocks/{stock}', StockDetail::class)->middleware('owns.stock')->name('stocks.show');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', UserList::class)->name('users');
        Route::get('/users/{user}/stocks', AdminUserStockList::class)->name('users.stocks');
    });
});

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'admin' ? redirect('/admin/users') : redirect('/stocks');
    }
    return redirect('/login');
});
