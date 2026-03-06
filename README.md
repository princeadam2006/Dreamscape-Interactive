# Dreamscape Interactive

Dreamscape Interactive is een **Filament-first Laravel 12** trading app.
Players beheren inventory, maken trades en reageren op offers. Admins beheren users/items en monitoren trade- en economy-activiteit.

## Core Features

### Player
- Registratie + e-mailverificatie via Filament auth pages
- Login met username/password
- Profielbeheer (naam, username, e-mail, notificatievoorkeuren)
- Persoonlijke inventory met search/filter/sort
- Trade voorstellen maken, accepteren, weigeren, annuleren
- Market bekijken met lock/tradeability-regels

### Admin
- User management + role assignment (Shield/Spatie permissions)
- Item catalog CRUD met stat-validatie
- Items toewijzen aan spelers
- Open trades modereren (incl. force-cancel)
- Audit logs en ownership insights

## Tech Stack
- PHP 8.3+
- Laravel 12
- Filament 4
- Livewire 3
- Tailwind CSS 4 + Vite
- Filament Shield (Spatie permissions)
- PHPUnit 11

## Domain Model (high-level)
- `users`
- `items`
- `inventory_items`
- `trades`
- `trade_items`
- `notifications`
- `audit_logs`

## Quick Start

### 1) Install dependencies
```bash
composer install
npm install
```

### 2) Environment + app key
```bash
cp .env.example .env
php artisan key:generate
```

### 3) Database
```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

### 4) Build assets
```bash
npm run build
```

### 5) Run app (dev)
Option A:
```bash
composer run dev
```

Option B (separate terminals):
```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

## Default Roles
Seeded by `RoleSeeder`:
- `player`
- `admin`
- `super_admin`
- `panel_user`

## Useful Commands

### Run tests
```bash
php artisan test --compact
```

Run a specific file:
```bash
php artisan test --compact tests/Feature/Trade/TradeWorkflowTest.php
```

### Format code
```bash
vendor/bin/pint --format agent
```

### Rebuild frontend assets
```bash
npm run build
```

## Troubleshooting

### Tailwind/Filament style changes not visible
Run:
```bash
npm run build
```
or during development:
```bash
npm run dev
```

### Vite manifest error
If you see `Unable to locate file in Vite manifest`, rebuild assets:
```bash
npm run build
```

### Permission-related access issues
Make sure roles/permissions are seeded and cache is cleared:
```bash
php artisan db:seed --class=RoleSeeder
php artisan optimize:clear
```

## Notes
- UI flows are built with Filament resources/pages/widgets.
- Trade logic is guarded by transactional checks to prevent conflicting item ownership updates.
- Audit logs capture sensitive admin actions for traceability.
