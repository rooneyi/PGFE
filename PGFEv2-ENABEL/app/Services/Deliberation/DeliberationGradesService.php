<?php

declare(strict_types=1);

namespace App\Services\Deliberation;

use App\Models\Deliberation;
use App\Models\FicheCotation;

final class DeliberationGradesService
{
    private const NOTE_KEYS = ['P1', 'P2', 'P3', 'P4', 'E1', 'E2'];

    /**
     * @return array{
     *     note: array<string, float>,
     *     moyenne_note: float,
     *     semestre_1_total: float,
     *     semestre_2_total: float,
     *     pourcentage: float,
     * }
     */
    public function computeForDeliberation(Deliberation $deliberation): array
    {
        $fiche = FicheCotation::query()
            ->where('student_id', $deliberation->student_id)
            ->where('classroom_id', $deliberation->classroom_id)
            ->where('course_id', $deliberation->course_id)
            ->where('school_year_id', $deliberation->school_year_id)
            ->orderByDesc('id')
            ->first();

        $note = $fiche ? $this->parseNote($fiche->note) : $this->emptyNote();
        $course = $fiche?->course ?? $deliberation->course;

        $maxima = $this->courseMaxima($course);
        $somme = 0.0;
        $sommeMaxima = 0.0;
        foreach (self::NOTE_KEYS as $key) {
            $somme += (float) ($note[$key] ?? 0);
            $sommeMaxima += (float) ($maxima[$key] ?? 0);
        }

        $pourcentage = $sommeMaxima > 0 ? round(($somme / $sommeMaxima) * 100, 2) : 0.0;
        $moyenneNote = count(self::NOTE_KEYS) > 0 ? round($somme / count(self::NOTE_KEYS), 2) : 0.0;

        return [
            'note' => $note,
            'moyenne_note' => $moyenneNote,
            'semestre_1_total' => round((float) $note['P1'] + (float) $note['P2'] + (float) $note['E1'], 2),
            'semestre_2_total' => round((float) $note['P3'] + (float) $note['P4'] + (float) $note['E2'], 2),
            'pourcentage' => $pourcentage,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function parseNote(mixed $rawNote): array
    {
        $parsed = null;
        if (is_string($rawNote)) {
            $decoded = json_decode($rawNote, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $parsed = $decoded;
            }
        } elseif (is_array($rawNote)) {
            $parsed = $rawNote;
        }

        if (! is_array($parsed)) {
            return $this->emptyNote();
        }

        $normalized = [];
        foreach ($parsed as $k => $v) {
            $normalized[mb_strtoupper((string) $k)] = $v;
        }

        $note = [];
        foreach (self::NOTE_KEYS as $key) {
            $note[$key] = (float) ($normalized[$key] ?? 0.0);
        }

        return $note;
    }

    /**
     * @return array<string, float>
     */
    private function emptyNote(): array
    {
        return array_fill_keys(self::NOTE_KEYS, 0.0);
    }

    /**
     * @return array<string, float>
     */
    private function courseMaxima(?object $course): array
    {
        if (! $course) {
            return array_fill_keys(self::NOTE_KEYS, 0.0);
        }

        return [
            'P1' => (float) ($course->max_period_1 ?? 0),
            'P2' => (float) ($course->max_period_2 ?? 0),
            'P3' => (float) ($course->max_period_3 ?? 0),
            'P4' => (float) ($course->max_period_4 ?? 0),
            'E1' => (float) ($course->max_exam_1 ?? 0),
            'E2' => (float) ($course->max_exam_2 ?? 0),
        ];
    }
}
