<?php

declare(strict_types=1);

namespace App\Services\Collecte;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Lecture / écriture du format Excel « Collecte rapide » (feuilles type KILWA).
 */
final class CollecteRapideExcelMapper
{
    /** @var list<string> */
    private array $regimeOrder;

    public function __construct(
        private readonly CollecteRapideSchema $schema,
    ) {
        $this->regimeOrder = array_keys($this->schema->regimes());
    }

    /**
     * @param  array{sous_division: string, proved: string, province: string, school_year: string, payload: array<string, mixed>}  $sheetData
     */
    public function writeSheet(Worksheet $sheet, array $sheetData): void
    {
        $payload = array_replace_recursive($this->schema->emptyPayload(), $sheetData['payload'] ?? []);

        $sheet->setCellValue('A1', 'COLLECTE RAPIDE DES DONNEES STATISTIQUES SCOLAIRES DES PARAMETRES ESSENTIELS');
        $sheet->setCellValue('A2', 'TABLEAU SYNTHESE');
        $sheet->setCellValue('A3', 'SOUS-DIVISION EDUCATIONNELLE :');
        $sheet->setCellValue('D3', $sheetData['sous_division']);
        $sheet->setCellValue('A4', 'PROVINCE EDUCATIONNELLE :');
        $sheet->setCellValue('D4', $sheetData['proved']);
        $sheet->setCellValue('A5', 'PROVINCE ADMINISTRATIVE :');
        $sheet->setCellValue('D5', $sheetData['province']);
        $sheet->setCellValue('F3', $sheetData['school_year']);

        // 1. Établissements
        $sheet->setCellValue('A6', 'Tableau 1 : Nombre d’Etablissements scolaires par Niveau d’enseignement et Regime de Gestion');
        $this->writeSimple($sheet, 8, ['C' => 'pre_primaire', 'D' => 'primaire', 'E' => 'secondaire'], $payload['etablissements'] ?? []);

        // Pré-primaire
        $sheet->setCellValue('A17', 'ENSEIGNEMENT PRE-PRIMAIRE');
        $sheet->setCellValue('A18', 'Tableau 2 : Nombre de classes organisées par année d’études et regime de gestion');
        $this->writeSimple($sheet, 20, ['C' => 'annee_1', 'D' => 'annee_2', 'E' => 'annee_3'], $payload['preprim_classes'] ?? []);

        $sheet->setCellValue('A29', 'Tableau 3 : Enfants inscrits par année d’études, sexe et regime de gestion');
        $this->writeGenderHeader($sheet, 31, 3);
        $this->writeGender($sheet, 32, [
            'annee_1' => ['G' => 'C', 'F' => 'D'],
            'annee_2' => ['G' => 'E', 'F' => 'F'],
            'annee_3' => ['G' => 'G', 'F' => 'H'],
        ], $payload['preprim_eleves'] ?? [], ['G', 'F']);

        $sheet->setCellValue('A41', 'Tableau 4 : Educateurs (trices) par qualification, sexe et régime de gestion');
        $this->writeGender($sheet, 44, [
            'EAP_D3' => ['H' => 'C', 'F' => 'D'],
            'D4' => ['H' => 'E', 'F' => 'F'],
            'EM' => ['H' => 'G', 'F' => 'H'],
            'D6' => ['H' => 'I', 'F' => 'J'],
            'AUTRE' => ['H' => 'K', 'F' => 'L'],
        ], $payload['preprim_educateurs'] ?? [], ['H', 'F']);

        // Primaire
        $sheet->setCellValue('A53', 'ENSEIGNEMENT PRIMAIRE');
        $sheet->setCellValue('A54', 'Tableau 1 : Nombre de classes organisées par année d’études et régime de gestion');
        $this->writeSimple($sheet, 56, [
            'C' => 'annee_1', 'D' => 'annee_2', 'E' => 'annee_3',
            'F' => 'annee_4', 'G' => 'annee_5', 'H' => 'annee_6',
        ], $payload['prim_classes'] ?? []);

        $sheet->setCellValue('A65', 'Tableau 2: Elèves inscrits par année d’études, sexe et régime de gestion');
        $this->writeGender($sheet, 68, [
            'annee_1' => ['G' => 'C', 'F' => 'D'],
            'annee_2' => ['G' => 'E', 'F' => 'F'],
            'annee_3' => ['G' => 'G', 'F' => 'H'],
            'annee_4' => ['G' => 'I', 'F' => 'J'],
            'annee_5' => ['G' => 'K', 'F' => 'L'],
            'annee_6' => ['G' => 'M', 'F' => 'N'],
        ], $payload['prim_eleves'] ?? [], ['G', 'F']);

        $sheet->setCellValue('A77', 'Tableau 3 : Personnel enseignant par qualification, sexe et regime de gestion');
        $this->writeGender($sheet, 80, [
            'EAP_D3' => ['H' => 'C', 'F' => 'D'],
            'D4' => ['H' => 'E', 'F' => 'F'],
            'D6' => ['H' => 'G', 'F' => 'H'],
            'AUTRE' => ['H' => 'I', 'F' => 'J'],
        ], $payload['prim_enseignants'] ?? [], ['H', 'F']);

        // Secondaire
        $sheet->setCellValue('A90', 'ENSEIGNEMENT SECONDAIRE');
        $sheet->setCellValue('A91', 'Tableau 1 : Nombre de classes organisées par année d’études et regime de gestion');
        $this->writeSimple($sheet, 93, [
            'C' => 'annee_7', 'D' => 'annee_8', 'E' => 'annee_1',
            'F' => 'annee_2', 'G' => 'annee_3', 'H' => 'annee_4',
        ], $payload['sec_classes'] ?? []);

        $sheet->setCellValue('A102', 'Tableau : Eleves inscrits par niveau d’études, sexe et regime de gestion');
        $this->writeGender($sheet, 106, [
            'annee_7' => ['G' => 'C', 'F' => 'D'],
            'annee_8' => ['G' => 'E', 'F' => 'F'],
            'annee_1' => ['G' => 'G', 'F' => 'H'],
            'annee_2' => ['G' => 'I', 'F' => 'J'],
            'annee_3' => ['G' => 'K', 'F' => 'L'],
            'annee_4' => ['G' => 'M', 'F' => 'N'],
        ], $payload['sec_eleves'] ?? [], ['G', 'F']);

        $sheet->setCellValue('A115', 'Tableau 10: Eleves par type d’enseignement (simplifié PGFE)');
        $this->writeTypeMatrix($sheet, 117, $payload['sec_type'] ?? []);

        $sheet->setCellValue('A155', 'Tableau : Personnel enseignant secondaire (qualif. × sexe)');
        $this->writeGender($sheet, 157, [
            'EAP_D3' => ['H' => 'C', 'F' => 'D'],
            'D4' => ['H' => 'E', 'F' => 'F'],
            'Licence' => ['H' => 'G', 'F' => 'H'],
            'Master' => ['H' => 'I', 'F' => 'J'],
            'AUTRE' => ['H' => 'K', 'F' => 'L'],
        ], $payload['sec_personnel'] ?? [], ['H', 'F']);
    }

    /**
     * @return array{sous_division: ?string, payload: array<string, mixed>, warnings: list<string>}
     */
    public function readSheet(Worksheet $sheet): array
    {
        $warnings = [];
        $payload = $this->schema->emptyPayload();

        $sd = $this->cellStr($sheet, 'D3') ?: $sheet->getTitle();

        $payload['etablissements'] = $this->readSimple($sheet, 8, [
            'C' => 'pre_primaire', 'D' => 'primaire', 'E' => 'secondaire',
        ], $warnings, 'etablissements');

        $payload['preprim_classes'] = $this->readSimple($sheet, 20, [
            'C' => 'annee_1', 'D' => 'annee_2', 'E' => 'annee_3',
        ], $warnings, 'preprim_classes');

        $payload['preprim_eleves'] = $this->readGender($sheet, 32, [
            'annee_1' => ['G' => 'C', 'F' => 'D'],
            'annee_2' => ['G' => 'E', 'F' => 'F'],
            'annee_3' => ['G' => 'G', 'F' => 'H'],
        ], ['G', 'F'], $warnings, 'preprim_eleves');

        $payload['preprim_educateurs'] = $this->readGender($sheet, 44, [
            'EAP_D3' => ['H' => 'C', 'F' => 'D'],
            'D4' => ['H' => 'E', 'F' => 'F'],
            'EM' => ['H' => 'G', 'F' => 'H'],
            'D6' => ['H' => 'I', 'F' => 'J'],
            'AUTRE' => ['H' => 'K', 'F' => 'L'],
        ], ['H', 'F'], $warnings, 'preprim_educateurs');

        $payload['prim_classes'] = $this->readSimple($sheet, 56, [
            'C' => 'annee_1', 'D' => 'annee_2', 'E' => 'annee_3',
            'F' => 'annee_4', 'G' => 'annee_5', 'H' => 'annee_6',
        ], $warnings, 'prim_classes');

        $payload['prim_eleves'] = $this->readGender($sheet, 68, [
            'annee_1' => ['G' => 'C', 'F' => 'D'],
            'annee_2' => ['G' => 'E', 'F' => 'F'],
            'annee_3' => ['G' => 'G', 'F' => 'H'],
            'annee_4' => ['G' => 'I', 'F' => 'J'],
            'annee_5' => ['G' => 'K', 'F' => 'L'],
            'annee_6' => ['G' => 'M', 'F' => 'N'],
        ], ['G', 'F'], $warnings, 'prim_eleves');

        $payload['prim_enseignants'] = $this->readGender($sheet, 80, [
            'EAP_D3' => ['H' => 'C', 'F' => 'D'],
            'D4' => ['H' => 'E', 'F' => 'F'],
            'D6' => ['H' => 'G', 'F' => 'H'],
            'AUTRE' => ['H' => 'I', 'F' => 'J'],
        ], ['H', 'F'], $warnings, 'prim_enseignants');

        $payload['sec_classes'] = $this->readSimple($sheet, 93, [
            'C' => 'annee_7', 'D' => 'annee_8', 'E' => 'annee_1',
            'F' => 'annee_2', 'G' => 'annee_3', 'H' => 'annee_4',
        ], $warnings, 'sec_classes');

        $payload['sec_eleves'] = $this->readGender($sheet, 106, [
            'annee_7' => ['G' => 'C', 'F' => 'D'],
            'annee_8' => ['G' => 'E', 'F' => 'F'],
            'annee_1' => ['G' => 'G', 'F' => 'H'],
            'annee_2' => ['G' => 'I', 'F' => 'J'],
            'annee_3' => ['G' => 'K', 'F' => 'L'],
            'annee_4' => ['G' => 'M', 'F' => 'N'],
        ], ['G', 'F'], $warnings, 'sec_eleves');

        $typeStart = $this->findRegimeBlockStart($sheet, 115, 140);
        if ($typeStart !== null) {
            $payload['sec_type'] = $this->readTypeMatrix($sheet, $typeStart, $warnings);
        } else {
            $warnings[] = 'Bloc types d’enseignement secondaire non trouvé (optionnel).';
        }

        $persStart = $this->findRegimeBlockStart($sheet, 150, 180) ?? 157;
        $payload['sec_personnel'] = $this->readGender($sheet, $persStart, [
            'EAP_D3' => ['H' => 'C', 'F' => 'D'],
            'D4' => ['H' => 'E', 'F' => 'F'],
            'Licence' => ['H' => 'G', 'F' => 'H'],
            'Master' => ['H' => 'I', 'F' => 'J'],
            'AUTRE' => ['H' => 'K', 'F' => 'L'],
        ], ['H', 'F'], $warnings, 'sec_personnel');

        // Sanitize via schema
        foreach ($this->schema->steps() as $step => $meta) {
            $key = $meta['key'] ?? null;
            if (! is_string($key) || ! isset($payload[$key])) {
                continue;
            }
            $payload[$key] = $this->schema->sanitizeMatrix($step, $payload[$key]);
        }

        return [
            'sous_division' => $sd !== '' ? $sd : null,
            'payload' => $payload,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, string>  $colMap  ExcelCol => fieldKey
     * @param  array<string, array<string, int>>  $matrix
     */
    private function writeSimple(Worksheet $sheet, int $startRow, array $colMap, array $matrix): void
    {
        foreach ($this->regimeOrder as $i => $regime) {
            $row = $startRow + $i;
            $sheet->setCellValue('A'.$row, $i + 1);
            $sheet->setCellValue('B'.$row, $regime);
            foreach ($colMap as $col => $field) {
                $sheet->setCellValue($col.$row, (int) ($matrix[$regime][$field] ?? 0));
            }
        }
    }

    /**
     * @param  array<string, array<string, string>>  $fieldCols  field => [sex => ExcelCol]
     * @param  array<string, array<string, array<string, int>>>  $matrix
     * @param  list<string>  $sexKeys
     */
    private function writeGender(Worksheet $sheet, int $startRow, array $fieldCols, array $matrix, array $sexKeys): void
    {
        foreach ($this->regimeOrder as $i => $regime) {
            $row = $startRow + $i;
            $sheet->setCellValue('A'.$row, $i + 1);
            $sheet->setCellValue('B'.$row, $regime);
            foreach ($fieldCols as $field => $sexMap) {
                foreach ($sexKeys as $sex) {
                    if (! isset($sexMap[$sex])) {
                        continue;
                    }
                    $sheet->setCellValue($sexMap[$sex].$row, (int) ($matrix[$regime][$field][$sex] ?? 0));
                }
            }
        }
    }

    private function writeGenderHeader(Worksheet $sheet, int $row, int $yearCount): void
    {
        $cols = ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
        $labels = ['G', 'F'];
        for ($i = 0; $i < $yearCount * 2; $i++) {
            $sheet->setCellValue($cols[$i].$row, $labels[$i % 2]);
        }
    }

    /**
     * @param  array<string, array<string, array<string, array<string, int>>>>  $matrix
     */
    private function writeTypeMatrix(Worksheet $sheet, int $startRow, array $matrix): void
    {
        $types = array_keys($this->schema->teachingTypes());
        $yearCols = [
            'annee_7' => ['G' => 'D', 'F' => 'E'],
            'annee_8' => ['G' => 'F', 'F' => 'G'],
            'annee_1' => ['G' => 'H', 'F' => 'I'],
            'annee_2' => ['G' => 'J', 'F' => 'K'],
            'annee_3' => ['G' => 'L', 'F' => 'M'],
            'annee_4' => ['G' => 'N', 'F' => 'O'],
        ];

        $row = $startRow;
        $sheet->setCellValue('B'.$row, 'Régime');
        $sheet->setCellValue('C'.$row, 'Type');
        $row++;

        foreach ($this->regimeOrder as $regime) {
            foreach ($types as $ti => $type) {
                $sheet->setCellValue('B'.$row, $ti === 0 ? $regime : '');
                $sheet->setCellValue('C'.$row, $type);
                foreach ($yearCols as $field => $sexMap) {
                    $sheet->setCellValue($sexMap['G'].$row, (int) ($matrix[$regime][$type][$field]['G'] ?? 0));
                    $sheet->setCellValue($sexMap['F'].$row, (int) ($matrix[$regime][$type][$field]['F'] ?? 0));
                }
                $row++;
            }
        }
    }

    /**
     * @param  array<string, string>  $colMap
     * @param  list<string>  $warnings
     * @return array<string, array<string, int>>
     */
    private function readSimple(Worksheet $sheet, int $startRow, array $colMap, array &$warnings, string $label): array
    {
        $out = [];
        $rows = $this->resolveRegimeRows($sheet, $startRow);
        if ($rows === []) {
            $warnings[] = "Régimes introuvables pour {$label} (ligne ~{$startRow}).";
        }
        foreach ($this->regimeOrder as $regime) {
            $out[$regime] = [];
            $row = $rows[$regime] ?? null;
            foreach ($colMap as $col => $field) {
                $out[$regime][$field] = $row ? $this->cellInt($sheet, $col.$row) : 0;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, array<string, string>>  $fieldCols
     * @param  list<string>  $sexKeys
     * @param  list<string>  $warnings
     * @return array<string, array<string, array<string, int>>>
     */
    private function readGender(Worksheet $sheet, int $startRow, array $fieldCols, array $sexKeys, array &$warnings, string $label): array
    {
        $out = [];
        $rows = $this->resolveRegimeRows($sheet, $startRow);
        if ($rows === []) {
            $warnings[] = "Régimes introuvables pour {$label} (ligne ~{$startRow}).";
        }
        foreach ($this->regimeOrder as $regime) {
            $out[$regime] = [];
            $row = $rows[$regime] ?? null;
            foreach ($fieldCols as $field => $sexMap) {
                $out[$regime][$field] = [];
                foreach ($sexKeys as $sex) {
                    $col = $sexMap[$sex] ?? null;
                    $out[$regime][$field][$sex] = ($row && $col) ? $this->cellInt($sheet, $col.$row) : 0;
                }
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, array<string, array<string, array<string, int>>>>
     */
    private function readTypeMatrix(Worksheet $sheet, int $startRow, array &$warnings): array
    {
        $empty = $this->schema->emptyMatrixForStep(10);
        $types = array_keys($this->schema->teachingTypes());
        $yearCols = [
            'annee_7' => ['G' => 'D', 'F' => 'E'],
            'annee_8' => ['G' => 'F', 'F' => 'G'],
            'annee_1' => ['G' => 'H', 'F' => 'I'],
            'annee_2' => ['G' => 'J', 'F' => 'K'],
            'annee_3' => ['G' => 'L', 'F' => 'M'],
            'annee_4' => ['G' => 'N', 'F' => 'O'],
        ];

        $currentRegime = null;
        $highest = min($sheet->getHighestRow(), $startRow + 80);
        for ($row = $startRow; $row <= $highest; $row++) {
            $b = $this->normalizeRegime($this->cellStr($sheet, 'B'.$row));
            $c = mb_strtoupper(mb_trim($this->cellStr($sheet, 'C'.$row)));
            if ($b !== null) {
                $currentRegime = $b;
            }
            if ($currentRegime === null || $c === '') {
                continue;
            }
            $type = $this->matchTeachingType($c);
            if ($type === null) {
                continue;
            }
            foreach ($yearCols as $field => $sexMap) {
                $empty[$currentRegime][$type][$field]['G'] = $this->cellInt($sheet, $sexMap['G'].$row);
                $empty[$currentRegime][$type][$field]['F'] = $this->cellInt($sheet, $sexMap['F'].$row);
            }
        }

        return $empty;
    }

    /**
     * @return array<string, int> regime => row
     */
    private function resolveRegimeRows(Worksheet $sheet, int $startRow): array
    {
        $map = [];
        // Prefer exact layout start, but also scan nearby if first cell is not ENC
        $scanFrom = max(1, $startRow - 2);
        $scanTo = $startRow + 12;
        for ($row = $scanFrom; $row <= $scanTo; $row++) {
            $regime = $this->normalizeRegime($this->cellStr($sheet, 'B'.$row));
            if ($regime !== null && ! isset($map[$regime])) {
                $map[$regime] = $row;
            }
        }

        // If we found ENC, rebuild consecutive rows from ENC when all 7 present in order
        if (isset($map['ENC']) && count($map) >= 7) {
            $ordered = [];
            $r = $map['ENC'];
            foreach ($this->regimeOrder as $i => $regime) {
                $candidate = $r + $i;
                $found = $this->normalizeRegime($this->cellStr($sheet, 'B'.$candidate));
                $ordered[$regime] = ($found === $regime) ? $candidate : ($map[$regime] ?? $candidate);
            }

            return $ordered;
        }

        return $map;
    }

    private function findRegimeBlockStart(Worksheet $sheet, int $from, int $to): ?int
    {
        for ($row = $from; $row <= min($to, $sheet->getHighestRow()); $row++) {
            if ($this->normalizeRegime($this->cellStr($sheet, 'B'.$row)) === 'ENC') {
                return $row;
            }
        }

        return null;
    }

    private function normalizeRegime(string $value): ?string
    {
        $v = mb_strtoupper(mb_trim($value));
        $v = str_replace(['É', 'È', 'Ê'], 'E', $v);
        if ($v === 'PRIVÉE' || $v === 'PRIVEE' || $v === 'PRIVÉ') {
            return 'PRIVEE';
        }
        foreach ($this->regimeOrder as $regime) {
            if ($v === $regime) {
                return $regime;
            }
        }

        return null;
    }

    private function matchTeachingType(string $label): ?string
    {
        $map = [
            'GENERAL' => 'General',
            'GÉNÉRAL' => 'General',
            'NORMAL' => 'Normal',
            'TECHNIQUE' => 'Technique',
            'PROFESSIONNEL' => 'Professionnel',
            'ART ET METIERS' => 'Art_Metiers',
            'ART ET MÉTIERS' => 'Art_Metiers',
            'ART_METIERS' => 'Art_Metiers',
        ];
        $key = mb_strtoupper(mb_trim($label));
        $key = str_replace(['É', 'È', 'Ê'], 'E', $key);

        return $map[$key] ?? (array_key_exists($label, $this->schema->teachingTypes()) ? $label : null);
    }

    private function cellStr(Worksheet $sheet, string $coord): string
    {
        $v = $sheet->getCell($coord)->getCalculatedValue();
        if ($v === null) {
            return '';
        }

        return mb_trim((string) $v);
    }

    private function cellInt(Worksheet $sheet, string $coord): int
    {
        $v = $sheet->getCell($coord)->getCalculatedValue();
        if (is_numeric($v)) {
            return max(0, (int) $v);
        }

        return 0;
    }
}
