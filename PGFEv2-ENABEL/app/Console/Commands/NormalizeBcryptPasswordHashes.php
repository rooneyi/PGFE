<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Les hashes bcrypt préfixés en $2b$ (Node, certains outils) font échouer
 * Hash::check() quand HASH_VERIFY=true : Laravel exige algoName === bcrypt,
 * or password_get_info() peut renvoyer "unknown" pour $2b$.
 *
 * Le préfixe $2y$ est équivalent pour password_verify() sous PHP.
 */
final class NormalizeBcryptPasswordHashes extends Command
{
    protected $signature = 'pgfe:normalize-bcrypt-hashes
                            {--dry-run : Afficher les lignes sans modifier}
                            {--chunk=200 : Taille des lots pour les mises à jour}';

    protected $description = 'Convertit les mots de passe bcrypt $2b$… en $2y$… pour éviter RuntimeException au login (HASH_VERIFY).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $count = User::query()->whereNotNull('password')->where('password', 'like', '$2b$%')->count();

        if ($count === 0) {
            $this->info('Aucun hash à préfixe $2b$ trouvé.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Hashes $2b$ détectés : %d', $count));

        if ($dryRun) {
            User::query()
                ->whereNotNull('password')
                ->where('password', 'like', '$2b$%')
                ->orderBy('id')
                ->limit(20)
                ->get(['id', 'email'])
                ->each(fn (User $u) => $this->line("  #{$u->id} {$u->email}"));

            if ($count > 20) {
                $this->comment('  … (liste tronquée à 20 pour dry-run)');
            }

            return self::SUCCESS;
        }

        $updated = 0;
        User::query()
            ->whereNotNull('password')
            ->where('password', 'like', '$2b$%')
            ->orderBy('id')
            ->chunkById($chunk, function ($users) use (&$updated): void {
                foreach ($users as $user) {
                    $hash = (string) $user->password;
                    if (! str_starts_with($hash, '$2b$')) {
                        continue;
                    }
                    $normalized = '$2y$'.mb_substr($hash, 4);
                    DB::table('users')->where('id', $user->id)->update([
                        'password' => $normalized,
                        'updated_at' => now(),
                    ]);
                    $updated++;
                }
            });

        $this->info(sprintf('Mise à jour effectuée : %d utilisateur(s).', $updated));

        return self::SUCCESS;
    }
}
