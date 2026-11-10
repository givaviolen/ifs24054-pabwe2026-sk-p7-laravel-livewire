<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowBackForwardCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Livewire menambahkan "no-store" ke setiap halaman; header itu memblokir bfcache.
        if ($request->isMethod('GET') && ! $request->hasHeader('X-Livewire')) {
            $response->headers->set('Cache-Control', 'no-cache, private');
            $response->headers->remove('Pragma');
            $response->headers->remove('Expires');
        }

        return $response;
    }
}