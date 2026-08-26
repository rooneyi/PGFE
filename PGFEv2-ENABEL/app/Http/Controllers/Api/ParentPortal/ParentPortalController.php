<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ParentPortal;

use App\Http\Controllers\Controller;
use App\Http\Resources\BulletinRessource\StudentBulletinResource;
use App\Models\Presence;
use App\Models\StudentActivity;
use App\Services\Parent\ParentChildrenResolver;
use App\Services\StudentBulletinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ParentPortalController extends Controller
{
    public function __construct(
        private readonly ParentChildrenResolver $childrenResolver,
        private readonly StudentBulletinService $bulletinService,
    ) {}

    public function me(Request $request): JsonResponse
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        $user = $request->user();

        return response()->json([
            'status' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'parent' => [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'firstname' => $parent->firstname,
                    'lastname' => $parent->lastname,
                    'phone_number' => $parent->phone_number,
                    'email' => $parent->email,
                    'school_id' => $parent->school_id,
                ],
            ],
        ]);
    }

    public function children(): JsonResponse
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        $children = $this->childrenResolver->children($parent)->map(function ($student) {
            $registration = $student->registration;

            return [
                'id' => $student->id,
                'name' => $student->name,
                'firstname' => $student->firstname,
                'lastname' => $student->lastname,
                'matricule' => $student->matricule,
                'gender' => $student->gender,
                'image_url' => $student->image_url ?? null,
                'school' => $student->school?->only(['id', 'name']),
                'classroom' => $registration?->classroom?->only(['id', 'name']),
                'school_year' => $registration?->schoolYear?->only(['id', 'name']),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $children,
            'count' => $children->count(),
        ]);
    }

    public function child(int $studentId): JsonResponse
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        $student = $this->childrenResolver->findOwnedChild($parent, $studentId);
        if (! $student) {
            return response()->json([
                'status' => false,
                'message' => 'Cet élève ne fait pas partie de vos enfants.',
            ], 403);
        }

        $registration = $student->registration;

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $student->id,
                'name' => $student->name,
                'firstname' => $student->firstname,
                'lastname' => $student->lastname,
                'matricule' => $student->matricule,
                'gender' => $student->gender,
                'birth_date' => $student->birth_date,
                'image_url' => $student->image_url ?? null,
                'school' => $student->school?->only(['id', 'name']),
                'classroom' => $registration?->classroom?->only(['id', 'name']),
                'school_year' => $registration?->schoolYear?->only(['id', 'name']),
            ],
        ]);
    }

    public function activities(int $studentId): JsonResponse
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        $student = $this->childrenResolver->findOwnedChild($parent, $studentId);
        if (! $student) {
            return response()->json([
                'status' => false,
                'message' => 'Cet élève ne fait pas partie de vos enfants.',
            ], 403);
        }

        $classroomId = $student->registration?->classroom_id;
        if (! $classroomId) {
            return response()->json([
                'status' => true,
                'data' => [],
                'message' => 'Aucune classe associée à cet élève.',
            ]);
        }

        $activities = StudentActivity::query()
            ->withoutGlobalScopes()
            ->with(['schoolActivity', 'classroom', 'author'])
            ->where('classroom_id', $classroomId)
            ->latest('id')
            ->get()
            ->map(function (StudentActivity $item) {
                $activity = $item->schoolActivity;

                return [
                    'id' => $item->id,
                    'classroom' => $item->classroom?->only(['id', 'name']),
                    'activity' => $activity ? [
                        'id' => $activity->id,
                        'label' => $activity->label,
                        'description' => $activity->description,
                        'place' => $activity->place,
                        'start_date' => $activity->start_date,
                        'end_date' => $activity->end_date,
                    ] : null,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $activities,
            'count' => $activities->count(),
        ]);
    }

    public function presences(Request $request, int $studentId): JsonResponse
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        if (! $this->childrenResolver->ownsChild($parent, $studentId)) {
            return response()->json([
                'status' => false,
                'message' => 'Cet élève ne fait pas partie de vos enfants.',
            ], 403);
        }

        $query = Presence::query()
            ->withoutGlobalScopes()
            ->with(['classroom', 'academicLevel'])
            ->where('student_id', $studentId)
            ->latest('id');

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $presences = $query->limit(200)->get()->map(function (Presence $presence) {
            $status = 'absent';
            if ($presence->presence) {
                $status = 'present';
            } elseif ($presence->sick) {
                $status = 'sick';
            } elseif ($presence->absent_justified) {
                $status = 'absent_justified';
            }

            return [
                'id' => $presence->id,
                'date' => optional($presence->created_at)?->toDateString(),
                'status' => $status,
                'presence' => (bool) $presence->presence,
                'absent_justified' => (bool) $presence->absent_justified,
                'sick' => (bool) $presence->sick,
                'classroom' => $presence->classroom?->only(['id', 'name']),
                'academic_level' => $presence->academicLevel?->only(['id', 'name']),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $presences,
            'count' => $presences->count(),
        ]);
    }

    public function bulletin(Request $request, int $studentId): JsonResponse|StudentBulletinResource
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        if (! $this->childrenResolver->ownsChild($parent, $studentId)) {
            return response()->json([
                'status' => false,
                'message' => 'Cet élève ne fait pas partie de vos enfants.',
            ], 403);
        }

        $data = $request->validate([
            'school_year_id' => 'nullable|integer|exists:school_years,id',
        ]);

        $schoolYearId = array_key_exists('school_year_id', $data)
            ? (int) $data['school_year_id']
            : null;

        $bulletinStudent = $this->bulletinService->loadStudent($studentId, $schoolYearId, true);

        return new StudentBulletinResource($bulletinStudent);
    }

    private function requireParent(): \App\Models\Parents|JsonResponse
    {
        $parent = $this->childrenResolver->resolveParentProfile();
        if (! $parent) {
            return response()->json([
                'status' => false,
                'message' => 'Aucun profil parent lié à ce compte.',
            ], 403);
        }

        return $parent;
    }
}
