@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Fiches de cotation"
        subtitle="Notes par période — filtres et actions comme sur l'application."
        icon="lucide:clipboard-list"
        breadcrumbCurrent="Fiches de cotation"
    >
        <x-admin.students-operations-nav active="fiche-cotations" />

        <x-admin.students-class-filters
            :action="route('admin.fiche-cotations.index')"
            :classrooms="$classrooms"
            :courses="$courses"
            :school-years="$schoolYears"
            :classroom-id="$classroomId"
            :course-id="$courseId"
            :school-year-id="$schoolYearId"
        >
            <x-slot:actions>
                <form method="POST" action="{{ route('admin.fiche-cotations.initialize') }}" class="inline">
                    @csrf
                    <input type="hidden" name="classroom_id" value="{{ $classroomId }}">
                    <input type="hidden" name="course_id" value="{{ $courseId }}">
                    <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">
                    <button type="submit" class="admin-btn-secondary" @disabled(!$classroomId || !$courseId || !$schoolYearId)>
                        Initialiser la feuille
                    </button>
                </form>
                @if($classroomId && $courseId && $schoolYearId)
                    <a href="{{ route('admin.fiche-cotations.export', ['classroom_id' => $classroomId, 'course_id' => $courseId, 'school_year_id' => $schoolYearId]) }}"
                       class="admin-btn-secondary">Export Excel</a>
                @endif
            </x-slot:actions>
        </x-admin.students-class-filters>

        @if($classroomId && $courseId && $schoolYearId)
            <div class="admin-data-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50/50">
                                <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Élève</th>
                                @foreach(['P1', 'P2', 'E1', 'P3', 'P4', 'E2'] as $period)
                                    <th class="px-3 py-4 text-center text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">{{ $period }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse($rows as $fiche)
                                @php
                                    $rawNote = $fiche->note;
                                    $noteArray = is_string($rawNote) ? json_decode($rawNote, true) : (is_array($rawNote) ? $rawNote : []);
                                @endphp
                                <tr class="transition-colors hover:bg-zinc-50/50">
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-semibold text-zinc-900">
                                            {{ trim(($fiche->student->firstname ?? '').' '.($fiche->student->lastname ?? '').' '.($fiche->student->name ?? '')) ?: 'Élève #'.$fiche->student_id }}
                                        </span>
                                        <span class="mt-0.5 block text-xs text-zinc-500">{{ $fiche->student->matricule ?? '—' }}</span>
                                    </td>
                                    @foreach(['P1', 'P2', 'E1', 'P3', 'P4', 'E2'] as $key)
                                        <td class="px-3 py-4 text-center text-xs font-semibold text-zinc-800">
                                            {{ ($noteArray[$key] ?? null) !== null ? $noteArray[$key] : '—' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center text-sm text-zinc-500">
                                        Aucune fiche. Utilisez <strong>Initialiser</strong> pour créer les lignes.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Sélectionnez classe, cours et année scolaire pour afficher les fiches.
            </p>
        @endif
    </x-admin.shadcn-shell>
@endsection
