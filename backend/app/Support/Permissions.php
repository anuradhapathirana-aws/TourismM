<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Single catalogue of roles and permissions. The seeder syncs these; policies check them.
 * Adding a module = add its key to RESOURCES (and to a role below), then re-run the seeder.
 */
final class Permissions
{
    public const SUPER_ADMIN = 'super-admin';

    public const CONTENT_EDITOR = 'content-editor';

    public const INQUIRY_MANAGER = 'inquiry-manager';

    public const ACTIONS = ['view', 'create', 'update', 'delete', 'restore', 'force-delete'];

    /** Resource keys (permission prefix). */
    public const RESOURCES = ['cities', 'destinations', 'themes', 'place-types', 'districts', 'provinces', 'users'];

    /** @return array<string, list<string>> role => resource keys it fully manages */
    public static function roles(): array
    {
        return [
            self::SUPER_ADMIN => self::RESOURCES, // also bypasses every check via Gate::before
            self::CONTENT_EDITOR => ['cities', 'destinations', 'themes', 'place-types'],
            self::INQUIRY_MANAGER => [],          // gets 'inquiries' when that module is built
        ];
    }

    /** @return list<string> */
    public static function all(): array
    {
        $out = [];
        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                $out[] = "{$resource}.{$action}";
            }
        }

        return $out;
    }

    /** @return list<string> */
    public static function forResources(array $resources, bool $withForceDelete = false): array
    {
        $out = [];
        foreach ($resources as $resource) {
            foreach (self::ACTIONS as $action) {
                if ($action === 'force-delete' && ! $withForceDelete) {
                    continue;
                }
                $out[] = "{$resource}.{$action}";
            }
        }

        return $out;
    }
}
