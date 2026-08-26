<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ValidationAureatsExport;
use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\ValidationAureat;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ValidationAureatWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function index(Request $request): View
    {
        $filters = $this->classCourseFilters($request);
        $classroomId = $filters['classroomId'];
        $schoolYearId = $filters['schoolYearId'];

        $query = ValidationAureat::query()->where('percentage', '>=', 80);

        if ($schoolId = $filters['schoolId']) {
            $query->where('school_id', $schoolId);
        }

        if ($classroomId > 0) {
            $classroom = $filters['classrooms']->firstWhere('id', $classroomId) ?? Classroom::find($classroomId);
            if ($classroom) {
                $query->where('class', $classroom->name);
            }
        }

        if ($schoolYearId) {
            $year = $filters['schoolYears']->firstWhere('id', $schoolYearId) ?? SchoolYear::find($schoolYearId);
            if ($year) {
                $query->where('year', $year->name);
            }
        }

        $items = $query->orderBy('last_name')->orderBy('first_name')->paginate(25);

        return view('backend.pages.validation-aureats.index', array_merge($filters, [
            'items' => $items,
        ]));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $classroomId = (int) $request->input('classroom_id', 0);
        $className = null;
        if ($classroomId > 0) {
            $className = Classroom::query()->find($classroomId)?->name;
        }

        return Excel::download(
            new ValidationAureatsExport(classFilter: $className, minPercentage: 80),
            'validation_laureats_'.now()->format('Ymd_His').'.xlsx',
        );
    }
}
