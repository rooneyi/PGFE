<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\IndisciplineCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class IndisciplineWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function index(Request $request): View
    {
        $filters = $this->classCourseFilters($request);
        $schoolId = $filters['schoolId'];

        $query = IndisciplineCase::with(['student', 'classroom', 'schoolYear'])
            ->latest('date')
            ->latest('id');

        if ($schoolId) {
            $query->whereHas('student', fn ($q) => $q->where('school_id', $schoolId));
        }
        if ($filters['classroomId'] > 0) {
            $query->where('classroom_id', $filters['classroomId']);
        }
        if ($filters['schoolYearId']) {
            $query->where('school_year_id', $filters['schoolYearId']);
        }

        if ($request->filled('search')) {
            $search = mb_strtolower(mb_trim((string) $request->input('search')));
            $query->whereHas('student', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(firstname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(matricule) LIKE ?', ["%{$search}%"]);
            });
        }

        $cases = $query->paginate(25);

        return view('backend.pages.indiscipline.index', array_merge($filters, [
            'cases' => $cases,
            'search' => $request->input('search'),
        ]));
    }
}
