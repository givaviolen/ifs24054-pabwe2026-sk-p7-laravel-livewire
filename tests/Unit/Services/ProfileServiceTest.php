<?php

namespace Tests\Unit\Services;

use App\Features\Profile\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new ProfileService;
    }

    public function test_update_profile_only_touches_allowed_fields(): void
    {
        $user = User::factory()->create();

        $this->service->updateProfile($user, ['name' => 'Baru', 'username' => 'baru', 'email' => 'baru@x.test', 'role' => 'admin']);

        $user->refresh();
        $this->assertSame('Baru', $user->name);
        $this->assertSame('baru', $user->username);
        $this->assertSame('baru@x.test', $user->email);
        $this->assertSame('member', $user->role);
    }

    public function test_update_password_hashes(): void
    {
        $user = User::factory()->create();

        $this->service->updatePassword($user, 'password-baru');

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }

    public function test_photo_upload_replace_and_remove(): void
    {
        $user = User::factory()->create();

        $this->service->updatePhoto($user, UploadedFile::fake()->image('a.jpg'));
        $first = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $this->service->updatePhoto($user, UploadedFile::fake()->image('b.jpg'));
        $second = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->service->removePhoto($user);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($user->fresh()->profile_photo_path);

        // tanpa foto: tidak error
        $this->service->removePhoto($user);
        $this->assertNull($user->fresh()->profile_photo_path);
    }
}
