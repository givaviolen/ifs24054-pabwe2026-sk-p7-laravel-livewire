<?php

namespace App\Http\Middleware;

use App\Features\Stock\Models\Stock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $stock = $request->route('stock');

        if ($stock === null) {
            return $next($request);
        }

        if (! $stock instanceof Stock) {
            $stock = Stock::query()->find($stock);
            abort_if($stock === null, 404);
        }

        abort_unless($request->user() !== null && $stock->user_id === $request->user()->id, 403, 'Stok ini bukan milik Anda.');

        return $next($request);
    }
}
