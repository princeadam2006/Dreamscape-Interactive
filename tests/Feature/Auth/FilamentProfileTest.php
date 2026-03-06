<?php

namespace Tests\Feature\Auth;

use App\Filament\Auth\EditProfile;
use App\Models\User;
use Filament\Auth\Notifications\NoticeOfEmailChangeRequest;
use Filament\Auth\Notifications\VerifyEmailChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_profile_page(): void
    {
        $user = User::factory()->create([
            'username' => 'profile_user',
            'email_verified_at' => now(),
            'password' => 'Password!123',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('wire:model="data.name"', false);
        $response->assertSee('wire:model="data.username"', false);
        $response->assertSee('wire:model.live.debounce.500="data.email"', false);
        $response->assertSee('data.notification_preferences.trade_updates', false);
    }

    public function test_user_can_update_name_and_notification_preferences(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'username' => 'old_name_user',
            'email_verified_at' => now(),
            'notification_preferences' => [
                'trade_updates' => true,
                'admin_announcements' => true,
                'email_trade_updates' => false,
            ],
        ]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('data.name', 'Updated Name')
            ->set('data.username', 'updated_username')
            ->set('data.notification_preferences.trade_updates', false)
            ->set('data.notification_preferences.admin_announcements', false)
            ->set('data.notification_preferences.email_trade_updates', true)
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated_username', $user->username);
        $this->assertSame([
            'trade_updates' => false,
            'admin_announcements' => false,
            'email_trade_updates' => true,
        ], $user->notification_preferences);
    }

    public function test_email_change_uses_verification_flow(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'old@example.com',
            'username' => 'email_change_user',
            'email_verified_at' => now(),
            'password' => 'Password!123',
        ]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('data.email', 'new@example.com')
            ->set('data.currentPassword', 'Password!123')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('old@example.com', $user->email);
        Notification::assertSentTo($user, NoticeOfEmailChangeRequest::class);
        Notification::assertSentOnDemand(
            VerifyEmailChange::class,
            function (VerifyEmailChange $notification, array $channels, object $notifiable): bool {
                return in_array('mail', $channels, true)
                    && method_exists($notifiable, 'routeNotificationFor')
                    && $notifiable->routeNotificationFor('mail') === 'new@example.com';
            }
        );
    }
}
