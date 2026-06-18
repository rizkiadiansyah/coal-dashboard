<?php

namespace App\Filament\Resources\Concerns;

use App\Models\User;

trait HasFilamentRoleAccess
{
    protected static function getFilamentUser(): ?User
    {
        return auth()->user();
    }

    protected static function isFilamentAdmin(): bool
    {
        return static::getFilamentUser()?->isAdmin() ?? false;
    }

    protected static function isFilamentMcr(): bool
    {
        return static::getFilamentUser()?->isMcr() ?? false;
    }

    protected static function isFilamentManagement(): bool
    {
        return static::getFilamentUser()?->isManagement() ?? false;
    }

    public static function canAccessAdminOnly(): bool
    {
        return static::isFilamentAdmin();
    }

    public static function canAccessPlan(): bool
    {
        return static::isFilamentAdmin() || static::isFilamentMcr();
    }

    public static function canModifyOnlyAdmin(?\Illuminate\Database\Eloquent\Model $record = null): bool
    {
        return static::isFilamentAdmin();
    }
}
