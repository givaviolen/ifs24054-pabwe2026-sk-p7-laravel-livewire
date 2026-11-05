<?php

namespace Tests\Feature;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_by_role(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect(route('admin.users'));
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('stocks.index'));
    }

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        foreach (['/stocks', '/stocks/1', '/profile', '/admin/users', '/admin/users/1/stocks'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_member_pages(): void
    {
        $member = User::factory()->create();
        $stock = Stock::factory()->for($member)->create();

        $this->actingAs($member)->get('/stocks')->assertOk();
        $this->actingAs($member)->get('/stocks/'.$stock->id)->assertOk()->assertSee($stock->name);
        $this->actingAs($member)->get('/profile')->assertOk();
    }

    public function test_member_cannot_open_other_members_stock(): void
    {
        $stock = Stock::factory()->create();

        $this->actingAs(User::factory()->create())->get('/stocks/'.$stock->id)->assertForbidden();
    }

    public function test_unknown_stock_is_404(): void
    {
        $this->actingAs(User::factory()->create())->get('/stocks/999999')->assertNotFound();
    }

    public function test_member_cannot_access_admin_pages(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get('/admin/users/'.$member->id.'/stocks')->assertForbidden();
    }

    public function test_admin_cannot_access_member_stock_pages(): void
    {
        $stock = Stock::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/stocks')->assertForbidden();
        $this->actingAs($admin)->get('/stocks/'.$stock->id)->assertForbidden();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/profile')->assertOk();
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }
}
