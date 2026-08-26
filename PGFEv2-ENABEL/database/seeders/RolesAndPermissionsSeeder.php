<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolesAndPermissionsSeeder extends Seeder
{
    /** Guard utilisé par la session web (/login) et Sanctum sur le même modèle User. */
    private const GUARD = 'web';

    public function run(): void
    {
        // Nettoyage (idempotent)
        app()['cache']->forget('spatie.permission.cache');

        // Granular permissions
        $permissions = [
            // Users
            'users.view', 'users.create.tiers', 'users.create.any', 'users.update', 'users.delete', 'users.assign.roles',
            // Organisation (Proved / Sous-division)
            'proveds.view', 'proveds.update',
            'sous-divisions.view', 'sous-divisions.create', 'sous-divisions.update', 'sous-divisions.delete',
            // Schools & years
            'schools.view', 'schools.create', 'schools.update', 'schools.delete',
            'schoolyears.view', 'schoolyears.create', 'schoolyears.update', 'schoolyears.delete', 'schoolyears.activate',
            // Academic structures
            'classrooms.full', 'academic-levels.full', 'students.full', 'students.view', 'personals.full',
            'classrooms.view', 'courses.manage', 'grades.manage', 'deliberation.manage',
            // Finance & Accounting
            'accounts.full', 'fees.full', 'payments.full', 'expenses.full', 'accounting.module',
            // Stock & Asset Management
            'stock.full', 'sales.full', 'rentals.full',
            // HR / Personnel
            'personnel.module',
            // Inspecteur
            'visits.manage',
            // Discipline
            'discipline.full',
            // Infrastructure
            'infrastructure.full',
            // Internat
            'internat.full', 'internat.view',
            // Espace parent
            'parent.portal', 'parent.children.view', 'parent.activities.view', 'parent.presence.view', 'parent.bulletin.view',
            'parent.forum.view', 'parent.forum.post', 'parent.forum.manage',
            // Settings
            'settings.full',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, self::GUARD);
        }

        $permissionsForRoles = Permission::query()->where('guard_name', self::GUARD)->get();

        // Rôles standards existants
        $admin = Role::findOrCreate('super-admin', self::GUARD);
        $adminEcole = Role::findOrCreate('admin-ecole', self::GUARD);
        $tiers = Role::findOrCreate('tiers', self::GUARD);

        // Nouveaux rôles demandés
        $enseignant = Role::findOrCreate('enseignant', self::GUARD);
        $comptable = Role::findOrCreate('comptable', self::GUARD);
        $stoker = Role::findOrCreate('stoker', self::GUARD);
        $rh = Role::findOrCreate('rh', self::GUARD);
        $inspecteur = Role::findOrCreate('inspecteur', self::GUARD);
        $disciplinaire = Role::findOrCreate('disciplinaire', self::GUARD);
        $adminProved = Role::findOrCreate('admin-proved', self::GUARD);
        $adminSousDivision = Role::findOrCreate('admin-sous-division', self::GUARD);

        // SUPER-ADMIN: toutes les permissions
        $admin->syncPermissions($permissionsForRoles);

        // ADMIN-ECOLE: Gestion locale
        $adminEcolePermissions = [
            'users.view', 'users.update', 'users.create.tiers',
            'schools.view',
            'schoolyears.view', 'schoolyears.activate',
            'students.full', 'personals.full',
            'accounts.full', 'fees.full', 'payments.full', 'expenses.full',
            'infrastructure.full', 'stock.full', 'personnel.module',
            'courses.manage', 'grades.manage', 'deliberation.manage',
            'discipline.full', 'visits.manage',
            'internat.full', 'internat.view',
            'parent.forum.manage',
        ];
        $adminEcole->syncPermissions($adminEcolePermissions);

        // Alias « admin » (utilisé sur certaines routes API avec admin-ecole)
        $legacyAdmin = Role::findOrCreate('admin', self::GUARD);
        $legacyAdmin->syncPermissions($adminEcolePermissions);

        // ENSEIGNANT: Cours, cotation et délibération
        $enseignant->syncPermissions([
            'courses.manage',
            'grades.manage',
            'deliberation.manage',
            'students.view',
            'parent.forum.manage',
        ]);

        // COMPTABLE: Module comptabilité
        $comptable->syncPermissions([
            'accounts.full',
            'fees.full',
            'payments.full',
            'expenses.full',
            'accounting.module',
        ]);

        // STOKER: Stock, vente et location
        $stoker->syncPermissions([
            'stock.full',
            'sales.full',
            'rentals.full',
        ]);

        // RH: Module Personnel
        $rh->syncPermissions([
            'personnel.module',
            'personals.full',
        ]);

        // INSPECTEUR: Visite de classe
        $inspecteur->syncPermissions([
            'visits.manage',
            'classrooms.view',
        ]);

        // DISCIPLINAIRE: Toute la discipline
        $disciplinaire->syncPermissions([
            'discipline.full',
            'students.view',
        ]);

        // TIERS: uniquement lecture
        $tiers->syncPermissions([]);

        // PARENT: consultation lecture seule des enfants
        $parentRole = Role::findOrCreate('parent', self::GUARD);
        $parentRole->syncPermissions([
            'parent.portal',
            'parent.children.view',
            'parent.activities.view',
            'parent.presence.view',
            'parent.bulletin.view',
            'parent.forum.view',
            'parent.forum.post',
        ]);

        $adminProved->syncPermissions([
            'proveds.view', 'proveds.update',
            'sous-divisions.view', 'sous-divisions.create', 'sous-divisions.update', 'sous-divisions.delete',
            'schools.view', 'schools.create', 'schools.update',
            'users.view', 'users.create.tiers', 'users.update',
            'schoolyears.view', 'schoolyears.activate',
            'students.view', 'classrooms.view',
        ]);

        $adminSousDivision->syncPermissions([
            'sous-divisions.view',
            'schools.view', 'schools.create', 'schools.update',
            'users.view', 'users.create.tiers', 'users.update',
            'schoolyears.view', 'schoolyears.activate',
            'students.view', 'classrooms.view',
        ]);

        // Super admin seed
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'password');

        $defaultSchoolId = \App\Models\School::query()->value('id');

        $super = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrateur Global',
                'password' => Hash::make($password),
                'school_id' => $defaultSchoolId,
            ]
        );

        // Assurer une école pour le bulletin / modules scolarité même si le user existait déjà
        if (! $super->school_id && $defaultSchoolId) {
            $super->school_id = $defaultSchoolId;
            $super->save();
        }

        $super->assignRole('super-admin');
    }
}
