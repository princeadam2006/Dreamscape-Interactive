<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Trade;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TradePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Trade');
    }

    public function view(AuthUser $authUser, Trade $trade): bool
    {
        return $authUser->can('View:Trade');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Trade');
    }

    public function update(AuthUser $authUser, Trade $trade): bool
    {
        return $authUser->can('Update:Trade');
    }

    public function delete(AuthUser $authUser, Trade $trade): bool
    {
        return $authUser->can('Delete:Trade');
    }

    public function restore(AuthUser $authUser, Trade $trade): bool
    {
        return $authUser->can('Restore:Trade');
    }

    public function forceDelete(AuthUser $authUser, Trade $trade): bool
    {
        return $authUser->can('ForceDelete:Trade');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Trade');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Trade');
    }

    public function replicate(AuthUser $authUser, Trade $trade): bool
    {
        return $authUser->can('Replicate:Trade');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Trade');
    }
}
