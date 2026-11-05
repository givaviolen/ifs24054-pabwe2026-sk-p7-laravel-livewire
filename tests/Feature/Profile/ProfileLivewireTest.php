<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->user = User::factory()->create(['password' => 'lama-12345']);
        $this->actingAs($this->user);
    }

    public function test_mount_fills_current_data(): void
    {
        Livewire::test(Profile::class)
            ->assertSet('name', $this->user->name)
            ->assertSet('username', $this->user->username)
            ->assertSet('email', $this->user->email);
    }

    public function test_update_profile(): void
    {
        Livewire::test(Profile::class)
            ->set('name', 'Nama Baru')->set('username', 'nama_baru')->set('email', 'baru@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSee('Profil berhasil diperbarui.');

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'username' => 'nama_baru', 'email' => 'baru@example.com']);
    }

    public function test_update_profile_allows_keeping_own_username_but_not_taking_others(): void
    {
        $other = User::factory()->create();

        Livewire::test(Profile::class)->call('updateProfile')->assertHasNoErrors();

        Livewire::test(Profile::class)
            ->set('username', $other->username)->set('email', $other->email)
            ->call('updateProfile')
            ->assertHasErrors(['username', 'email']);
    }

    public function test_update_password(): void
    {
        Livewire::test(Profile::class)
            ->set('current_password', 'lama-12345')
            ->set('password', 'baru-123456')
            ->set('password_confirmation', 'baru-123456')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('current_password', '')
            ->assertSee('Password berhasil diubah.');

        $this->assertTrue(Hash::check('baru-123456', $this->user->fresh()->password));
    }

    public function test_update_password_validation(): void
    {
        Livewire::test(Profile::class)
            ->set('current_password', 'salah')
            ->set('password', 'baru-123456')->set('password_confirmation', 'baru-123456')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        Livewire::test(Profile::class)
            ->set('current_password', 'lama-12345')
            ->set('password', 'lama-12345')->set('password_confirmation', 'lama-12345')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }

    public function test_photo_upload_and_remove(): void
    {
        Livewire::test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('me.jpg'))
            ->call('updatePhoto')
            ->assertHasNoErrors()
            ->assertSee('Foto profil berhasil diperbarui.')
            ->call('removePhoto')
            ->assertSee('Foto profil dihapus.');

        $this->assertNull($this->user->fresh()->profile_photo_path);
    }

    public function test_photo_rules(): void
    {
        Livewire::test(Profile::class)->call('updatePhoto')->assertHasErrors(['photo' => 'required']);

        Livewire::test(Profile::class)
            ->set('photo', UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))
            ->call('updatePhoto')
            ->assertHasErrors(['photo']);
    }

    public function test_photo_is_rendered_when_present(): void
    {
        $this->user->update(['profile_photo_path' => 'profile-photos/x.jpg']);

        Livewire::test(Profile::class)->assertSee('profile-photos/x.jpg');
    }
}
