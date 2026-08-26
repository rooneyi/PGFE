<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Deliberation;
use App\Services\Deliberation\DeliberationGradesService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class DeliberationsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $deliberations,
        private readonly DeliberationGradesService $gradesService = new DeliberationGradesService(),
    ) {}

    public function collection(): Collection
    {
        return $this->deliberations;
    }

    /**
     * @param  Deliberation  $deliberation
     */
    public function map($deliberation): array
    {
        $grades = $this->gradesService->computeForDeliberation($deliberation);
        $note = $grades['note'];
        $student = $deliberation->student;

        $studentName = mb_trim(implode(' ', array_filter([
            $student?->name,
            $student?->lastname,
            $student?->firstname,
        ])));

        return [
            $student?->matricule ?? '',
            $studentName,
            $deliberation->classroom?->name ?? '',
            $deliberation->course?->label ?? $deliberation->course?->name ?? '',
            $deliberation->schoolYear?->name ?? '',
            $note['P1'],
            $note['P2'],
            $note['E1'],
            $grades['semestre_1_total'],
            $note['P3'],
            $note['P4'],
            $note['E2'],
            $grades['semestre_2_total'],
            $grades['moyenne_note'],
            $grades['pourcentage'],
            $deliberation->is_validated ? 'Oui' : 'Non',
        ];
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Élève',
            'Classe',
            'Cours',
            'Année scolaire',
            'P1',
            'P2',
            'E1',
            'SEM 1',
            'P3',
            'P4',
            'E2',
            'SEM 2',
            'Moyenne',
            '% Annuelle',
            'Validée',
        ];
    }
}
