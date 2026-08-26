<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CivilStatusEnum;
use App\Enums\GenderEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Harmonise les colonnes genre / état civil vers les backing values des enums PHP.
 *
 * Tables : students, academic_personals, personals (gender + civil_status), parents (genre).
 */
final class HarmonizeGenderAndCivilStatus extends Command
{
    protected $signature = 'data:harmonize-gender-civil-status
                            {--dry-run : Affiche le résumé sans modifier la base}';

    protected $description = 'Harmonise genre et état civil (GenderEnum / CivilStatusEnum).';

    /** @var array<string, true> */
    private array $unknownGenderSamples = [];

    /** @var array<string, true> */
    private array $unknownCivilSamples = [];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->unknownGenderSamples = [];
        $this->unknownCivilSamples = [];

        if ($dry) {
            $this->warn('Mode dry-run : aucune écriture en base.');
        }

        $total = 0;
        $total += $this->harmonizeTableColumn('students', 'gender', true);
        $total += $this->harmonizeTableColumn('students', 'civil_status', false);
        $total += $this->harmonizeTableColumn('academic_personals', 'gender', true);
        $total += $this->harmonizeTableColumn('academic_personals', 'civil_status', false);
        $total += $this->harmonizeTableColumn('personals', 'gender', true);
        $total += $this->harmonizeTableColumn('personals', 'civil_status', false);
        $total += $this->harmonizeTableColumn('parents', 'genre', true);

        if (! empty($this->unknownGenderSamples)) {
            $this->newLine();
            $this->warn('Valeurs de genre non reconnues (échantillon) : '.implode(', ', array_keys($this->unknownGenderSamples)));
        }
        if (! empty($this->unknownCivilSamples)) {
            $this->newLine();
            $this->warn('Valeurs d\'état civil non reconnues (échantillon) : '.implode(', ', array_keys($this->unknownCivilSamples)));
        }

        $this->newLine();
        $this->info($dry ? "Dry-run terminé. {$total} ligne(s) seraient mises à jour." : "Terminé. {$total} ligne(s) mises à jour.");

        return self::SUCCESS;
    }

    private function harmonizeTableColumn(string $table, string $column, bool $isGender): int
    {
        $dry = (bool) $this->option('dry-run');
        $canonical = $isGender
            ? [GenderEnum::MA->value, GenderEnum::FA->value, GenderEnum::NA->value]
            : [CivilStatusEnum::C->value, CivilStatusEnum::M->value, CivilStatusEnum::D->value, CivilStatusEnum::V->value];

        $updated = 0;

        DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->whereNotIn($column, $canonical)
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $column, $isGender, $dry, &$updated): void {
                foreach ($rows as $row) {
                    $raw = (string) $row->{$column};
                    $mapped = $isGender ? $this->mapGender($raw) : $this->mapCivilStatus($raw);
                    if ($mapped === null) {
                        if ($isGender) {
                            if (count($this->unknownGenderSamples) < 25) {
                                $this->unknownGenderSamples[$raw] = true;
                            }
                        } elseif (count($this->unknownCivilSamples) < 25) {
                            $this->unknownCivilSamples[$raw] = true;
                        }

                        continue;
                    }
                    if ($mapped === $raw) {
                        continue;
                    }
                    if (! $dry) {
                        DB::table($table)->where('id', $row->id)->update([$column => $mapped]);
                    }
                    $updated++;
                }
            });

        $label = $isGender ? 'genre' : 'état civil';
        $this->line(sprintf('  %s.%s (%s) : %d', $table, $column, $label, $updated));

        return $updated;
    }

    private function mapGender(string $raw): ?string
    {
        $t = trim($raw);
        $canonical = [GenderEnum::MA->value, GenderEnum::FA->value, GenderEnum::NA->value];
        if (in_array($t, $canonical, true)) {
            return $t;
        }

        $n = mb_strtolower($t);

        $variants = [
            GenderEnum::MA->value => ['masculin', 'male', 'm', 'homme', 'man', 'garcon', 'garçon', 'h'],
            GenderEnum::FA->value => ['féminin', 'feminin', 'female', 'f', 'femme', 'woman', 'femelle'],
            GenderEnum::NA->value => ['non spécifié', 'non specifie', 'non-specifie', 'other', 'autre', 'n/a', 'na', 'inconnu', 'unknown', 'ns', 'o'],
        ];

        foreach ($variants as $target => $keys) {
            if (in_array($n, $keys, true)) {
                return $target;
            }
        }

        return null;
    }

    private function mapCivilStatus(string $raw): ?string
    {
        $t = trim($raw);
        $canonical = [
            CivilStatusEnum::C->value,
            CivilStatusEnum::M->value,
            CivilStatusEnum::D->value,
            CivilStatusEnum::V->value,
        ];
        if (in_array($t, $canonical, true)) {
            return $t;
        }

        $n = mb_strtolower($t);

        $variants = [
            CivilStatusEnum::C->value => [
                'célibataire', 'celibataire', 'single', 'unmarried', 'c', 'celibatair',
                'unknown', 'inconnu', 'n/a', 'na',
            ],
            CivilStatusEnum::M->value => [
                'marié(e)', 'marie(e)', 'marié', 'marie', 'married', 'm', 'epoux', 'époux', 'epouse', 'épouse',
            ],
            CivilStatusEnum::D->value => [
                'divorcé(e)', 'divorce(e)', 'divorcé', 'divorce', 'divorced', 'd', 'séparé', 'separe', 'separée',
            ],
            CivilStatusEnum::V->value => [
                'veuf/veuve', 'veuf', 'veuve', 'widowed', 'widow', 'v', 'veuf veuve', 'veuf(ve)',
            ],
        ];

        foreach ($variants as $target => $keys) {
            if (in_array($n, $keys, true)) {
                return $target;
            }
        }

        return null;
    }
}
