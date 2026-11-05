<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_required_element_ids(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSeeLivewire(Login::class)
            ->assertSee('id="login-username"', false)
            ->assertSee('id="login-password"', false)
            ->assertSee('id="login-submit"', false);
    }

    public function test_login_with_username(): void
    {
        User::factory()->create(['username' => 'member', 'password' => 'member']);

        Livewire::test(Login::class)
            ->set('login', 'member')
            ->set('password', 'member')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_login_with_email_and_remember(): void
    {
        User::factory()->create(['email' => 'budi@example.com', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('login', 'budi@example.com')
            ->set('password', 'rahasia123')
            ->set('remember', true)
            ->call('submit')
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_wrong_password_shows_error(): void
    {
        User::factory()->create(['username' => 'member', 'password' => 'member']);

        Livewire::test(Login::class)
            ->set('login', 'member')
            ->set('password', 'salah')
            ->call('submit')
            ->assertHasErrors(['login'])
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_fields_are_required(): void
    {
        Livewire::test(Login::class)->call('submit')->assertHasErrors(['login', 'password']);
    }

    public function test_too_many_attempts_are_throttled(): void
    {
        User::factory()->create(['username' => 'member', 'password' => 'member']);
        $component = Livewire::test(Login::class)->set('login', 'member')->set('password', 'salah');

        for ($i = 0; $i < 5; $i++) {
            $component->call('submit');
        }

        $component->set('password', 'member')->call('submit')->assertHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_successful_login_clears_attempts(): void
    {
        User::factory()->create(['username' => 'member', 'password' => 'member']);

        Livewire::test(Login::class)->set('login', 'member')->set('password', 'salah')->call('submit');
        Livewire::test(Login::class)->set('login', 'member')->set('password', 'member')->call('submit')->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect(route('home'));
    }
}
