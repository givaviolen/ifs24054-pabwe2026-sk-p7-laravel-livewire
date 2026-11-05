<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertOk()->assertSeeLivewire(Register::class);
    }

    public function test_register_creates_member_and_logs_in(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Budi')
            ->set('username', 'budi_01')
            ->set('email', 'budi@example.com')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $user = User::where('username', 'budi_01')->first();
        $this->assertSame('member', $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_validation_errors(): void
    {
        Livewire::test(Register::class)
            ->call('submit')
            ->assertHasErrors(['name', 'username', 'email', 'password']);

        Livewire::test(Register::class)
            ->set('name', 'A')->set('username', 'bad name!')->set('email', 'bukan-email')
            ->set('password', 'pendek')->set('password_confirmation', 'beda')
            ->call('submit')
            ->assertHasErrors(['username', 'email', 'password']);
    }

    public function test_username_and_email_must_be_unique(): void
    {
        User::factory()->create(['username' => 'ada', 'email' => 'ada@example.com']);

        Livewire::test(Register::class)
            ->set('name', 'X')->set('username', 'ada')->set('email', 'ada@example.com')
            ->set('password', 'rahasia123')->set('password_confirmation', 'rahasia123')
            ->call('submit')
            ->assertHasErrors(['username', 'email']);
    }

    public function test_role_cannot_be_injected(): void
    {
        $action = new \App\Features\Auth\Actions\RegisterUserAction;

        $user = $action->execute([
            'name' => 'Z', 'username' => 'z', 'email' => 'z@x.test', 'password' => 'rahasia123', 'role' => 'admin',
        ]);

        $this->assertSame('member', $user->role);
    }
}
