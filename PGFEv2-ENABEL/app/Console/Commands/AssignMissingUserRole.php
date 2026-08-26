<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

final class AssignMissingUserRole extends Command
{
    protected $signature = 'pgfe:assign-missing-role
                            {role=admin-ecole : Nom du rôle Spatie à assigner}
                            {--guard=web : Guard du rôle (session web = web)}
                            {--email= : Limiter à un utilisateur par e-mail}
                            {--dry-run : Afficher les cibles sans modifier}';

    protected $description = 'Assigne un rôle aux utilisateurs qui n’en ont aucun (ex. admin-ecole pour accès /admin).';

    public function handle(): int
    {
        $roleName = (string) $this->argument('role');
        $guard = (string) $this->option('guard');
        $email = $this->option('email') ? (string) $this->option('email') : null;
        $dryRun = (bool) $this->option('dry-run');

        Role::findOrCreate($roleName, $guard);

        $query = User::query()->whereDoesntHave('roles');

        if ($email !== null && $email !== '') {
            $query->where('email', '=', $email);
        }

        // Éviter LazyLoadingViolationException quand preventLazyLoading() est actif (assignRole touche la relation roles).
        $users = $query->with(['roles', 'permissions'])->orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->info('Aucun utilisateur sans rôle à traiter.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Utilisateurs sans rôle : %d', $users->count()));

        foreach ($users as $user) {
            $label = "{$user->id} <{$user->email}>";
            if ($dryRun) {
                $this->line("  [dry-run] {$label}");

                continue;
            }
            $user->assignRole($roleName);
            $this->line("  rôle « {$roleName} » assigné : {$label}");
        }

        if (! $dryRun) {
            app()['cache']->forget('spatie.permission.cache');
            $this->info('Cache des permissions régénéré (forget). Lancez php artisan optimize:clear si besoin.');
        }

        return self::SUCCESS;
    }
}
