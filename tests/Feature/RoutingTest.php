<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_is_accessible_for_guest(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk ke Akun Anda');
    }

    public function test_root_is_accessible_for_member(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get('/')
            ->assertOk();
    }

    public function test_root_is_accessible_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/')
            ->assertOk();
    }

    public function test_health_check_endpoint(): void
    {
        $this->get('/up')
            ->assertStatus(200);
    }
}