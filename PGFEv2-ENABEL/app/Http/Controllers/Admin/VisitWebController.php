<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class VisitWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function index(Request $request): View
    {
        $filters = $this->classCourseFilters($request);
        $schoolId = $filters['schoolId'];

        $query = Visit::with(['classroom:id,name', 'school:id,name'])
            ->latest('visit_hour');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($filters['classroomId'] > 0) {
            $query->where('classroom_id', $filters['classroomId']);
        }

        if ($request->filled('search')) {
            $search = mb_trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('visiteur', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        $visits = $query->paginate(25);

        return view('backend.pages.visits.index', array_merge($filters, [
            'visits' => $visits,
        ]));
    }
}
