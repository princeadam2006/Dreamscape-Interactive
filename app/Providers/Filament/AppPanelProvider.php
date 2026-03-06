<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditProfile as UserEditProfile;
use App\Filament\Auth\Login as UsernameLogin;
use App\Filament\Auth\Register as UsernameRegister;
use App\Filament\Pages\Dashboard;
use App\Models\User;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Openplain\FilamentShadcnTheme\Color as ShadcnColor;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('')
            ->databaseNotifications()
            ->login(UsernameLogin::class)
            ->registration(UsernameRegister::class)
            ->passwordReset()
            ->emailVerification()
            ->emailChangeVerification()
            ->profile(UserEditProfile::class)
            ->colors([
                'primary' => ShadcnColor::Blue,
            ])
            ->darkMode(false)
            ->viteTheme('resources/css/filament/app/theme.css')
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(fn (): bool => app()->environment('local'))
                    ->column('username')
                    ->users(fn (): array => User::query()
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [
                            "{$user->name} ({$user->username})" => $user->username,
                        ])
                        ->toArray()),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
