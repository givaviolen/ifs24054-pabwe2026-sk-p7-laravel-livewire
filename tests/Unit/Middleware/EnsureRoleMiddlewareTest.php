<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureRoleMiddlewareTest extends TestCase
{
    private function dispatch(?User $user, string ...$roles): Response
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => $user);

        return (new EnsureRole)->handle($request, fn () => new Response('ok'), ...$roles);
    }

    public function test_allows_matching_role(): void
    {
        $this->assertSame('ok', $this->dispatch(User::factory()->make(), 'member')->getContent());
        $this->assertSame('ok', $this->dispatch(User::factory()->admin()->make(), 'admin', 'member')->getContent());
    }

    public function test_rejects_other_role(): void
    {
        try {
            $this->dispatch(User::factory()->make(), 'admin');
            $this->fail('Seharusnya 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_rejects_guest(): void
    {
        $this->expectException(HttpException::class);
        $this->dispatch(null, 'member');
    }
}
