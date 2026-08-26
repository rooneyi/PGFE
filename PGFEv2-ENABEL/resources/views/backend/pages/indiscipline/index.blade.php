@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Gestion disciplinaire"
        subtitle="Cas d'indiscipline par élève et par classe."
        icon="lucide:shield-alert"
        breadcrumbCurrent="Discipline"
    >
        <x-admin.students-operations-nav active="indiscipline" />

        <x-admin.students-class-filters
            :action="route('admin.indiscipline.index')"
            :classrooms="$classrooms"
            :courses="$courses"
            :school-years="$schoolYears"
            :classroom-id="$classroomId"
            :course-id="$courseId"
            :school-year-id="$schoolYearId"
            :require-class-course="false"
        />

        <div class="admin-data-card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50/50">
                        <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Date</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Élève</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Classe</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($cases as $case)
                        <tr>
                            <td class="px-6 py-3">{{ $case->date?->format('d/m/Y') ?? $case->date }}</td>
                            <td class="px-6 py-3">
                                @if($case->student)
                                    {{ trim(($case->student->firstname ?? '').' '.($case->student->lastname ?? '').' '.($case->student->name ?? '')) }}
                                @else — @endif
                            </td>
                            <td class="px-6 py-3 text-zinc-600">{{ $case->classroom->name ?? '—' }}</td>
                            <td class="px-6 py-3">{{ $case->action ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-zinc-500">Aucun cas disciplinaire.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($cases->hasPages())
                <div class="border-t border-zinc-100 px-6 py-4">{{ $cases->links() }}</div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
