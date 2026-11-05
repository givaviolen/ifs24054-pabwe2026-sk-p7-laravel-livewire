<?php

namespace Tests\Unit\Middleware;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureStockOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** @param  mixed  $param  null = route tanpa parameter; Stock = model ter-binding; string = id mentah */
    private function dispatch(?User $user, string $uri, mixed $param = null): Response
    {
        $request = Request::create($uri);
        $request->setUserResolver(fn () => $user);

        $route = new Route('GET', $uri === '/stocks' ? '/stocks' : '/stocks/{stock}', fn () => null);
        $route->bind($request);
        if ($param instanceof Stock) {
            $route->setParameter('stock', $param);
        }
        $request->setRouteResolver(fn () => $route);

        return (new EnsureStockOwnership)->handle($request, fn () => new Response('ok'));
    }

    private function statusOf(callable $fn): int
    {
        try {
            $fn();
        } catch (HttpException $e) {
            return $e->getStatusCode();
        }

        return 200;
    }

    public function test_passes_without_stock_parameter(): void
    {
        $this->assertSame('ok', $this->dispatch(User::factory()->make(), '/stocks')->getContent());
    }

    public function test_owner_with_bound_model_passes(): void
    {
        $owner = User::factory()->create();
        $stock = Stock::factory()->for($owner)->create();

        $this->assertSame('ok', $this->dispatch($owner, '/stocks/'.$stock->id, $stock)->getContent());
    }

    public function test_other_user_is_forbidden(): void
    {
        $stock = Stock::factory()->create();
        $other = User::factory()->create();

        $this->assertSame(403, $this->statusOf(fn () => $this->dispatch($other, '/stocks/'.$stock->id, $stock)));
    }

    public function test_raw_id_is_resolved(): void
    {
        $owner = User::factory()->create();
        $stock = Stock::factory()->for($owner)->create();

        $this->assertSame('ok', $this->dispatch($owner, '/stocks/'.$stock->id)->getContent());
        $this->assertSame(403, $this->statusOf(fn () => $this->dispatch(User::factory()->create(), '/stocks/'.$stock->id)));
    }

    public function test_unknown_id_is_404(): void
    {
        $this->assertSame(404, $this->statusOf(fn () => $this->dispatch(User::factory()->create(), '/stocks/999999')));
    }

    public function test_guest_is_forbidden(): void
    {
        $stock = Stock::factory()->create();

        $this->assertSame(403, $this->statusOf(fn () => $this->dispatch(null, '/stocks/'.$stock->id, $stock)));
    }
}
