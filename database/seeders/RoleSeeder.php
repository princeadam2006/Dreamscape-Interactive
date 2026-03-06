<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = ['player', 'admin', 'super_admin', 'panel_user'];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        $subjects = [
            'AuditLog',
            'InventoryItem',
            'Item',
            'Role',
            'Trade',
            'User',
        ];
        $actions = [
            'ViewAny',
            'View',
            'Create',
            'Update',
            'Delete',
            'DeleteAny',
            'ForceDelete',
            'ForceDeleteAny',
            'Restore',
            'RestoreAny',
            'Replicate',
            'Reorder',
        ];

        $allPermissionNames = [];

        foreach ($subjects as $subject) {
            foreach ($actions as $action) {
                $allPermissionNames[] = "{$action}:{$subject}";
            }
        }

        $allPermissionNames[] = 'View:OwnershipInsightsWidget';

        $allPermissions = collect($allPermissionNames)
            ->map(fn (string $permissionName): Permission => Permission::findOrCreate($permissionName, 'web'));

        $playerPermissionNames = [
            'ViewAny:Item',
            'View:Item',
            'ViewAny:InventoryItem',
            'View:InventoryItem',
            'ViewAny:Trade',
            'View:Trade',
            'Create:Trade',
            'Update:Trade',
        ];

        $playerPermissions = $allPermissions
            ->whereIn('name', $playerPermissionNames)
            ->values();

        Role::findByName('admin', 'web')->syncPermissions($allPermissions);
        Role::findByName('super_admin', 'web')->syncPermissions($allPermissions);
        Role::findByName('player', 'web')->syncPermissions($playerPermissions);
        Role::findByName('panel_user', 'web')->syncPermissions([]);
    }
}
