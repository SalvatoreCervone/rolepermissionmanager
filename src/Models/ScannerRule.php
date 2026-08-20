<?php

namespace SalvatoreCervone\RolePermissionManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

class ScannerRule extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('rolepermissionmanager.tables.scanner_rules', 'acl_scanner_rules');
    }

    /**
     * Boot the model to flush the ACL cache on changes.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            AclRegistry::refreshCache();
        });

        static::deleted(function () {
            AclRegistry::refreshCache();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeExcludes(Builder $query): Builder
    {
        return $query->where('type', 'exclude');
    }

    public function scopeIncludes(Builder $query): Builder
    {
        return $query->where('type', 'include');
    }
}
