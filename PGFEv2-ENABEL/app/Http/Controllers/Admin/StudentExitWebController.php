<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\StudentExit;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StudentExitWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function index(Request $request): View
    {
        $filters = $this->classCourseFilters($request);
        $schoolId = $filters['schoolId'];

        $query = StudentExit::with(['student:id,firstname,lastname,name,matricule', 'filiere:id,name', 'schoolYear:id,name'])
            ->latest('date')
            ->latest('id');

        if ($schoolId) {
            $query->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }
        $studentExits = $query->paginate(25);

        return view('backend.pages.student-exits.index', array_merge($filters, [
            'studentExits' => $studentExits,
        ]));
    }
}
