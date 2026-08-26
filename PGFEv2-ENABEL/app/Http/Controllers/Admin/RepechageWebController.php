<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\Deliberation;
use App\Services\Deliberation\DeliberationGradesService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RepechageWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function __construct(
        private readonly DeliberationGradesService $gradesService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->classCourseFilters($request);
        $threshold = (float) $request->input('threshold', 50);
        $rows = collect();

        if ($filters['classroomId'] > 0 && $filters['courseId'] > 0 && $filters['schoolYearId']) {
            $deliberations = Deliberation::query()
                ->where('is_validated', false)
                ->where('school_year_id', $filters['schoolYearId'])
                ->where('classroom_id', $filters['classroomId'])
                ->where('course_id', $filters['courseId'])
                ->with(['student'])
                ->get();

            $rows = $deliberations->map(function (Deliberation $d) {
                $grades = $this->gradesService->computeForDeliberation($d);

                return [
                    'deliberation' => $d,
                    'grades' => $grades,
                ];
            })->filter(fn (array $row) => $row['grades']['pourcentage'] < $threshold)->values();
        }

        return view('backend.pages.repechages.index', array_merge($filters, [
            'rows' => $rows,
            'threshold' => $threshold,
        ]));
    }
}
