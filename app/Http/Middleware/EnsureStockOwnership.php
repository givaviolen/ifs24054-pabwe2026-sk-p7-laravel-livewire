<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStockOwnership {
    public function handle(Request $request, Closure $next) {
        $stock = $request->route('stock');
        if ($stock && $stock->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }
}
