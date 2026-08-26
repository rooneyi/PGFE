<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\FicheCotation;
use App\Models\Registration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class FicheCotationSeeder extends Seeder
{
    public function run(): void
    {
        $registrations = Registration::query()
            ->with(['student:id,school_id', 'classroom:id'])
            ->whereNotNull('classroom_id')
            ->whereNotNull('school_year_id')
            ->whereNotNull('student_id')
            ->get();

        if ($registrations->isEmpty()) {
            $this->command?->warn('FicheCotationSeeder: aucune inscription trouvée.');

            return;
        }

        $coursesByClassroom = Course::query()
            ->whereNotNull('classroom_id')
            ->get(['id', 'classroom_id', 'max_period_1', 'max_period_2', 'max_period_3', 'max_period_4', 'max_exam_1', 'max_exam_2'])
            ->groupBy('classroom_id');

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($registrations, $coursesByClassroom, &$created, &$updated): void {
            foreach ($registrations as $registration) {
                $classroomId = (int) $registration->classroom_id;
                $courses = $coursesByClassroom->get($classroomId) ?? collect();

                if ($courses->isEmpty()) {
                    continue;
                }

                foreach ($courses as $course) {
                    $notes = $this->buildRandomNotes($course);
                    $payload = [
                        'note' => json_encode($notes, JSON_THROW_ON_ERROR),
                        'school_id' => $registration->student?->school_id,
                    ];

                    $fiche = FicheCotation::withTrashed()->updateOrCreate(
                        [
                            'school_year_id' => $registration->school_year_id,
                            'student_id' => $registration->student_id,
                            'classroom_id' => $classroomId,
                            'course_id' => $course->id,
                        ],
                        $payload
                    );

                    if ($fiche->trashed()) {
                        $fiche->restore();
                    }

                    if ($fiche->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }
                }
            }
        });

        $this->command?->info("FicheCotationSeeder: {$created} créées, {$updated} mises à jour.");
    }

    /**
     * @return array{P1: float, P2: float, P3: float, P4: float, E1: float, E2: float}
     */
    private function buildRandomNotes(Course $course): array
    {
        $max = [
            'P1' => (float) ($course->max_period_1 ?: 10),
            'P2' => (float) ($course->max_period_2 ?: 10),
            'P3' => (float) ($course->max_period_3 ?: 10),
            'P4' => (float) ($course->max_period_4 ?: 10),
            'E1' => (float) ($course->max_exam_1 ?: 20),
            'E2' => (float) ($course->max_exam_2 ?: 20),
        ];

        $notes = [];
        foreach ($max as $key => $maxValue) {
            // Notes réalistes entre 40% et 95% du maxima
            $ratio = mt_rand(40, 95) / 100;
            $notes[$key] = round($maxValue * $ratio, 1);
        }

        return $notes;
    }
}
