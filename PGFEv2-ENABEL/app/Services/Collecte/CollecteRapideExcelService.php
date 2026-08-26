<?php

declare(strict_types=1);

namespace App\Services\Collecte;

use App\Models\CollecteRapide;
use App\Models\Proved;
use App\Models\SchoolYear;
use App\Models\SousDivision;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CollecteRapideExcelService
{
    public function __construct(
        private readonly CollecteRapideSchema $schema,
        private readonly CollecteRapideExcelMapper $mapper,
    ) {}

    /**
     * @param  Collection<int, CollecteRapide>  $collectes
     */
    public function download(Collection $collectes, string $filename): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($collectes);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, CollecteRapide>  $collectes
     * @param  array{proved: string, province: string, school_year: string, payload: array<string, mixed>}|null  $synthese
     */
    public function downloadWithSynthese(Collection $collectes, ?array $synthese, string $filename): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($collectes);

        if ($synthese !== null) {
            $sheet = $spreadsheet->createSheet();
            $title = $this->safeTitle('SYNTHESE');
            $sheet->setTitle($title);
            $this->mapper->writeSheet($sheet, [
                'sous_division' => $synthese['sous_divisions'] ?? 'SYNTHESE',
                'proved' => $synthese['proved'],
                'province' => $synthese['province'],
                'school_year' => $synthese['school_year'],
                'payload' => $synthese['payload'],
            ]);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{imported: int, created: int, updated: int, skipped: list<string>, warnings: list<string>}
     */
    public function import(UploadedFile $file, int $provedId, int $schoolYearId, int $userId): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $result = [
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => [],
            'warnings' => [],
        ];

        $sousDivisions = SousDivision::query()
            ->where('proved_id', $provedId)
            ->get(['id', 'name', 'code', 'proved_id']);

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $title = mb_strtoupper(mb_trim($sheet->getTitle()));
            if (str_contains($title, 'SYNTHESE') || str_contains($title, 'SYNTHÈSE')) {
                $result['skipped'][] = "Feuille « {$sheet->getTitle()} » ignorée (synthèse).";

                continue;
            }

            $parsed = $this->mapper->readSheet($sheet);
            foreach ($parsed['warnings'] as $w) {
                $result['warnings'][] = "[{$sheet->getTitle()}] {$w}";
            }

            $sd = $this->matchSousDivision($sousDivisions, $parsed['sous_division'], $sheet->getTitle());
            if ($sd === null) {
                $result['skipped'][] = "Feuille « {$sheet->getTitle()} » : sous-division introuvable"
                    .($parsed['sous_division'] ? " ({$parsed['sous_division']})" : '').'.';

                continue;
            }

            $collecte = CollecteRapide::query()
                ->where('sous_division_id', $sd->id)
                ->where('school_year_id', $schoolYearId)
                ->first();

            if ($collecte === null) {
                CollecteRapide::create([
                    'proved_id' => $provedId,
                    'sous_division_id' => $sd->id,
                    'school_year_id' => $schoolYearId,
                    'status' => CollecteRapide::STATUS_DRAFT,
                    'current_step' => $this->schema->lastStep(),
                    'data' => $parsed['payload'],
                    'created_by' => $userId,
                ]);
                $result['created']++;
            } else {
                if ($collecte->isSubmitted()) {
                    $result['skipped'][] = "« {$sd->name} » déjà soumise — import ignoré (rouvrir d’abord).";

                    continue;
                }
                $collecte->update([
                    'data' => $parsed['payload'],
                    'current_step' => max($collecte->current_step, $this->schema->lastStep()),
                ]);
                $result['updated']++;
            }

            $result['imported']++;
        }

        return $result;
    }

    /**
     * @param  Collection<int, CollecteRapide>  $collectes
     */
    private function buildSpreadsheet(Collection $collectes): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $usedTitles = [];

        foreach ($collectes as $collecte) {
            $collecte->loadMissing(['sousDivision', 'proved.province', 'schoolYear']);
            $sheet = $spreadsheet->createSheet();
            $title = $this->safeTitle($collecte->sousDivision?->name ?? 'SD-'.$collecte->id, $usedTitles);
            $usedTitles[] = mb_strtoupper($title);
            $sheet->setTitle($title);

            $this->mapper->writeSheet($sheet, [
                'sous_division' => $collecte->sousDivision?->name ?? '',
                'proved' => $collecte->proved?->name ?? '',
                'province' => $collecte->proved?->province?->name ?? '',
                'school_year' => $collecte->schoolYear?->name ?? '',
                'payload' => array_replace_recursive($this->schema->emptyPayload(), $collecte->data ?? []),
            ]);
        }

        if ($spreadsheet->getSheetCount() === 0) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle('VIDE');
            $sheet->setCellValue('A1', 'Aucune collecte à exporter');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  Collection<int, SousDivision>  $sousDivisions
     */
    private function matchSousDivision(Collection $sousDivisions, ?string $name, string $sheetTitle): ?SousDivision
    {
        $candidates = array_filter([$name, $sheetTitle]);
        foreach ($candidates as $candidate) {
            $norm = $this->normalizeName($candidate);
            $found = $sousDivisions->first(function (SousDivision $sd) use ($norm): bool {
                return $this->normalizeName($sd->name) === $norm
                    || $this->normalizeName($sd->code) === $norm;
            });
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    private function normalizeName(string $value): string
    {
        $v = Str::ascii(mb_strtoupper(mb_trim($value)));
        $v = preg_replace('/[^A-Z0-9]+/', '', $v) ?? $v;

        return $v;
    }

    /**
     * @param  list<string>  $usedUpper
     */
    private function safeTitle(string $name, array $usedUpper = []): string
    {
        $title = mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name) ?? $name, 0, 31);
        $title = mb_trim($title) ?: 'Feuille';
        $base = $title;
        $i = 2;
        while (in_array(mb_strtoupper($title), $usedUpper, true)) {
            $suffix = '-'.$i;
            $title = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
            $i++;
        }

        return $title;
    }
}
