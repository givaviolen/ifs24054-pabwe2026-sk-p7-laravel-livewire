<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_helpers(): void
    {
        $this->assertTrue(User::factory()->admin()->make()->isAdmin());
        $this->assertFalse(User::factory()->admin()->make()->isMember());
        $this->assertTrue(User::factory()->make()->isMember());
    }

    public function test_stocks_relation_and_cascade_delete(): void
    {
        $user = User::factory()->create();
        Stock::factory()->count(2)->for($user)->create();

        $this->assertCount(2, $user->stocks);

        $user->delete();
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_password_is_hashed(): void
    {
        $user = User::create(['name' => 'A', 'username' => 'a', 'email' => 'a@x.test', 'password' => 'secret123']);

        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertSame('member', $user->fresh()->role);
    }

    public function test_profile_photo_url(): void
    {
        $this->assertNull(User::factory()->make()->profilePhotoUrl());
        $this->assertStringEndsWith('profile-photos/a.jpg', User::factory()->make(['profile_photo_path' => 'profile-photos/a.jpg'])->profilePhotoUrl());
    }
}
