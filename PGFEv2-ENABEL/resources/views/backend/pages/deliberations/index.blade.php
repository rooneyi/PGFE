@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Délibérations"
        subtitle="Pointage par classe, cours et année (aligné application mobile)."
        icon="lucide:scale"
        breadcrumbCurrent="Délibérations"
    >
        <x-admin.students-operations-nav active="deliberations" />

        <x-admin.students-class-filters
            :action="route('admin.deliberations.index')"
            :classrooms="$classrooms"
            :courses="$courses"
            :school-years="$schoolYears"
            :classroom-id="$classroomId"
            :course-id="$courseId"
            :school-year-id="$schoolYearId"
        >
            <x-slot:actions>
                <form method="POST" action="{{ route('admin.deliberations.initialize') }}" class="inline">
                    @csrf
                    <input type="hidden" name="classroom_id" value="{{ $classroomId }}">
                    <input type="hidden" name="course_id" value="{{ $courseId }}">
                    <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">
                    <button type="submit" class="admin-btn-secondary" @disabled(!$classroomId || !$courseId || !$schoolYearId)>
                        Initialiser la feuille
                    </button>
                </form>
                @if($classroomId && $courseId && $schoolYearId)
                    <a href="{{ route('admin.deliberations.export', ['classroom_id' => $classroomId, 'course_id' => $courseId, 'school_year_id' => $schoolYearId]) }}"
                       class="admin-btn-secondary">Export Excel</a>
                @endif
            </x-slot:actions>
        </x-admin.students-class-filters>

        @if($classroomId && $courseId)
            <div class="admin-data-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50/50">
                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-zinc-400">Élève</th>
                                @foreach(['P1','P2','E1'] as $p)<th class="px-2 py-3 text-center text-[10px] font-bold uppercase text-zinc-400">{{ $p }}</th>@endforeach
                                <th class="px-2 py-3 text-center text-[10px] font-bold uppercase text-zinc-400 bg-blue-50/50">SEM 1</th>
                                @foreach(['P3','P4','E2'] as $p)<th class="px-2 py-3 text-center text-[10px] font-bold uppercase text-zinc-400">{{ $p }}</th>@endforeach
                                <th class="px-2 py-3 text-center text-[10px] font-bold uppercase text-zinc-400 bg-blue-50/50">SEM 2</th>
                                <th class="px-2 py-3 text-center text-[10px] font-bold uppercase text-zinc-400">%</th>
                                <th class="px-2 py-3 text-center text-[10px] font-bold uppercase text-zinc-400">Validée</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse($rows as $row)
                                @php
                                    $d = $row['deliberation'];
                                    $g = $row['grades'];
                                    $note = $g['note'];
                                    $s = $d->student;
                                @endphp
                                <tr class="hover:bg-zinc-50/50">
                                    <td class="px-4 py-2 font-medium text-zinc-900">
                                        {{ trim(($s->firstname ?? '').' '.($s->lastname ?? '').' '.($s->name ?? '')) ?: '—' }}
                                    </td>
                                    @foreach(['P1','P2','E1'] as $k)
                                        <td class="px-2 py-2 text-center">{{ $note[$k] ?? '—' }}</td>
                                    @endforeach
                                    <td class="px-2 py-2 text-center font-semibold bg-blue-50/30">{{ $g['semestre_1_total'] }}</td>
                                    @foreach(['P3','P4','E2'] as $k)
                                        <td class="px-2 py-2 text-center">{{ $note[$k] ?? '—' }}</td>
                                    @endforeach
                                    <td class="px-2 py-2 text-center font-semibold bg-blue-50/30">{{ $g['semestre_2_total'] }}</td>
                                    <td class="px-2 py-2 text-center font-bold">{{ $g['pourcentage'] }}%</td>
                                    <td class="px-2 py-2 text-center">
                                        <form method="POST" action="{{ route('admin.deliberations.validation', $d) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="classroom_id" value="{{ $classroomId }}">
                                            <input type="hidden" name="course_id" value="{{ $courseId }}">
                                            <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">
                                            <input type="hidden" name="is_validated" value="{{ $d->is_validated ? '0' : '1' }}">
                                            <button type="submit" class="text-xs font-bold {{ $d->is_validated ? 'text-emerald-600' : 'text-amber-600' }}">
                                                {{ $d->is_validated ? 'Oui' : 'Non' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-6 py-12 text-center text-zinc-500">
                                        Aucune délibération. Cliquez sur <strong>Initialiser</strong> pour créer la feuille.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Sélectionnez une <strong>classe</strong>, un <strong>cours</strong> et une <strong>année scolaire</strong>.
            </p>
        @endif
    </x-admin.shadcn-shell>
@endsection
