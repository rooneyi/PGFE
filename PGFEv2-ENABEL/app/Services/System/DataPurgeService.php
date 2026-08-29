<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Purges operational application data while keeping geo/ref seed data
 * and essential admin accounts.
 *
 * Uses FOREIGN_KEY_CHECKS=0 (MySQL) so truncate/delete never fails on FKs.
 * TRUNCATE is preferred for speed; users/role pivots are handled selectively.
 */
final class DataPurgeService
{
    /**
     * Tables that must never be truncated.
     * users / model_has_* are cleaned selectively (keep super-admins).
     */
    private const KEEP_TABLES = [
        'migrations',
        // Geo / ref seed
        'countries',
        'provinces',
        'territories',
        'communes',
        'types',
        'fonctions',
        'currencies',
        'mois',
        // Spatie RBAC catalog
        'roles',
        'permissions',
        'role_has_permissions',
        // Handled selectively below (keep super-admin rows / tokens)
        'users',
        'model_has_roles',
        'model_has_permissions',
        'personal_access_tokens',
    ];

    /**
     * @return array{purged_tables: list<string>, kept_user_ids: list<int>, deleted_users: int}
     */
    public function purge(User $actor): array
    {
        $keepUserIds = $this->resolveKeepUserIds($actor);
        $driver = DB::getDriverName();

        $this->disableForeignKeyChecks($driver);

        try {
            $purgedTables = [];

            foreach ($this->listTables() as $table) {
                if (in_array($table, self::KEEP_TABLES, true)) {
                    continue;
                }

                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->truncate();
                $purgedTables[] = $table;
            }

            $deletedUsers = $this->purgeNonKeptUsers($keepUserIds);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return [
                'purged_tables' => $purgedTables,
                'kept_user_ids' => $keepUserIds,
                'deleted_users' => $deletedUsers,
            ];
        } finally {
            $this->enableForeignKeyChecks($driver);
        }
    }

    /**
     * @return list<int>
     */
    private function resolveKeepUserIds(User $actor): array
    {
        $ids = User::role(['super-admin', 'admin'])
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $ids[] = (int) $actor->id;

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<int>  $keepUserIds
     */
    private function purgeNonKeptUsers(array $keepUserIds): int
    {
        if ($keepUserIds === []) {
            return 0;
        }

        // Tokens / pivots for users that will be deleted
        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereNotIn('tokenable_id', $keepUserIds)
                ->delete();
        }

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereNotIn('model_id', $keepUserIds)
                ->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->whereNotIn('model_id', $keepUserIds)
                ->delete();
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->whereNotNull('user_id')->whereNotIn('user_id', $keepUserIds)->delete();
        }

        return (int) DB::table('users')->whereNotIn('id', $keepUserIds)->delete();
    }

    /**
     * @return list<string>
     */
    private function listTables(): array
    {
        $tables = Schema::getTableListing();

        return array_values(array_map(
            static fn ($t) => is_string($t) ? $t : (string) $t,
            $tables
        ));
    }

    private function disableForeignKeyChecks(string $driver): void
    {
        match ($driver) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
            default => null,
        };
    }

    private function enableForeignKeyChecks(string $driver): void
    {
        match ($driver) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
            default => null,
        };
    }
}
