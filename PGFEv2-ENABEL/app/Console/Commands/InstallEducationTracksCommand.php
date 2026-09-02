<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\School;
use App\Services\Academic\EducationStructureBootstrapper;
use App\Support\Academic\EducationTracks;
use Illuminate\Console\Command;

final class InstallEducationTracksCommand extends Command
{
    protected $signature = 'pgfe:install-education-tracks
                            {--school= : Identifiant de l’école (toutes si omis)}
                            {--tracks=maternelle,primaire,base_7_8 : Pistes à installer}
                            {--keep-5-6 : Ne pas retirer les 5ème et 6ème des cycles secondaires}';

    protected $description = 'Installe maternelle, primaire, 7e-8e de base et ramène les cycles secondaires à 1ère-4ème.';

    public function handle(EducationStructureBootstrapper $bootstrapper): int
    {
        $schoolId = $this->option('school');
        $query = School::query();
        if ($schoolId) {
            $query->whereKey((int) $schoolId);
        }

        $schools = $query->get();
        if ($schools->isEmpty()) {
            $this->error('Aucune école trouvée.');

            return self::FAILURE;
        }

        $tracks = array_values(array_filter(array_map(
            static fn (string $key): string => trim($key),
            explode(',', (string) $this->option('tracks')),
        )));
        $tracks = array_values(array_intersect($tracks, EducationTracks::keys()));
        if ($tracks === []) {
            $tracks = EducationTracks::keys();
        }

        $trim = ! $this->option('keep-5-6');

        foreach ($schools as $school) {
            $result = $bootstrapper->install($school, $tracks, $trim);
            $this->info(sprintf(
                'École #%d %s — créées: %s | déjà présentes: %s | 5e-6e retirées: %s | conservées (données): %s',
                $school->id,
                $school->name,
                $result['created'] ? implode(', ', $result['created']) : '—',
                $result['already'] ? implode(', ', $result['already']) : '—',
                $result['trimmed'] ? implode(', ', $result['trimmed']) : '—',
                $result['skipped'] ? implode(', ', $result['skipped']) : '—',
            ));
        }

        return self::SUCCESS;
    }
}
