<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class DiagnosePasswordHashes extends Command
{
    protected $signature = 'pgfe:diagnose-password-hashes
                            {--limit=30 : Nombre max de lignes détaillées par groupe problématique}';

    protected $description = 'Liste les préfixes de hash en base et ce que PHP reconnaît (bcrypt / argon2 / inconnu).';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $users = User::query()
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->orderBy('id')
            ->get(['id', 'email', 'password']);

        $byPrefix = [];
        $byAlgo = [];

        foreach ($users as $u) {
            $hash = (string) $u->password;
            $prefix = mb_strlen($hash) >= 7 ? mb_substr($hash, 0, 7) : '(trop court)';
            $byPrefix[$prefix] = ($byPrefix[$prefix] ?? 0) + 1;

            $info = password_get_info($hash);
            $algo = $info['algoName'] ?? 'unknown';
            $byAlgo[$algo] = ($byAlgo[$algo] ?? 0) + 1;
        }

        $this->info('Comptage par préfixe (7 premiers caractères) :');
        ksort($byPrefix);
        foreach ($byPrefix as $p => $n) {
            $this->line(sprintf('  %s → %d', $p, $n));
        }

        $this->newLine();
        $this->info('Comptage selon password_get_info() [algoName] :');
        ksort($byAlgo);
        foreach ($byAlgo as $algo => $n) {
            $this->line(sprintf('  %s → %d', $algo, $n));
        }

        $this->newLine();
        $this->comment('Driver Laravel actuel (config) : '.config('hashing.driver', 'bcrypt'));
        $this->comment('HASH_VERIFY bcrypt : '.var_export(config('hashing.bcrypt.verify', true), true));

        $problem = $users->filter(function ($u) {
            $hash = (string) $u->password;
            $info = password_get_info($hash);

            return ($info['algoName'] ?? 'unknown') !== 'bcrypt';
        });

        if ($problem->isEmpty()) {
            $this->newLine();
            $this->info('Tous les hashes sont reconnus comme bcrypt par PHP : l’erreur "does not use Bcrypt" ne devrait pas venir de ces lignes.');
            $this->comment('Vérifie une ancienne entrée de log, un autre environnement, ou un cache OPcache sur une vieille version du .env.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('Utilisateurs dont le hash n’est PAS reconnu comme bcrypt par PHP (échantillon) :');

        foreach ($problem->take($limit) as $u) {
            $hash = (string) $u->password;
            $info = password_get_info($hash);
            $algo = $info['algoName'] ?? 'unknown';
            $head = mb_strlen($hash) > 28 ? mb_substr($hash, 0, 28).'…' : $hash;
            $this->line(sprintf('  id=%d %s | algoName=%s | début=%s', $u->id, $u->email, $algo, $head));
        }

        if ($problem->count() > $limit) {
            $this->comment(sprintf('  … %d autre(s) non affiché(s)', $problem->count() - $limit));
        }

        $this->newLine();
        if (isset($byAlgo['argon2id']) || isset($byAlgo['argon2i'])) {
            $this->warn('Des hashes Argon2 sont présents : dans .env utilisez HASH_DRIVER=argon2id (ou argon) puis php artisan config:clear.');
        }

        return self::SUCCESS;
    }
}
