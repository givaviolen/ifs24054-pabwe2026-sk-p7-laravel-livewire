<?php

namespace Tests\Unit\Providers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_interfaces_are_bound(): void
    {
        $this->assertInstanceOf(StockRepository::class, app(StockRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiStockGeneratorService::class, app(AiClientInterface::class));
    }

    public function test_rate_limiter_is_1000_per_second(): void
    {
        $limiter = RateLimiter::limiter('1000-per-second');

        $byIp = $limiter(Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.9']));
        $this->assertSame(1000, $byIp->maxAttempts);
        $this->assertSame(1, $byIp->decaySeconds);
        $this->assertSame('10.0.0.9', $byIp->key);

        $user = User::factory()->make(['id' => 77]);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $this->assertSame(77, $limiter($request)->key);
    }

    public function test_force_https_flag(): void
    {
        config(['app.force_https' => true]);
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', URL::to('/login'));
    }
}
