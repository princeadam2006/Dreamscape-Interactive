<?php

namespace Tests\Feature\Auth;

use App\Filament\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentUsernameAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_has_name_field(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('wire:model="data.name"', false);
    }

    public function test_register_page_has_username_field(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('wire:model="data.username"', false);
    }

    public function test_login_page_has_username_field(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('wire:model="data.username"', false);
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'username_login_test',
            'email_verified_at' => now(),
            'password' => 'Password!123',
        ]);

        Livewire::test(Login::class)
            ->set('data.username', $user->username)
            ->set('data.password', 'Password!123')
            ->call('authenticate');

        $this->assertAuthenticatedAs($user);
    }
}
