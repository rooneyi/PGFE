@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Validation lauréats"
        subtitle="Élèves avec au moins 80 % (comme sur l'application)."
        icon="lucide:award"
        breadcrumbCurrent="Validation lauréats"
    >
        <x-admin.students-operations-nav active="validation-aureats" />

        <x-admin.students-class-filters
            :action="route('admin.validation-aureats.index')"
            :classrooms="$classrooms"
            :courses="$courses"
            :school-years="$schoolYears"
            :classroom-id="$classroomId"
            :course-id="$courseId"
            :school-year-id="$schoolYearId"
            :require-class-course="false"
        >
            <x-slot:actions>
                <a href="{{ route('admin.validation-aureats.export', ['classroom_id' => $classroomId ?: null]) }}" class="admin-btn-secondary">Export Excel</a>
            </x-slot:actions>
        </x-admin.students-class-filters>

        <div class="admin-data-card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50/50">
                        <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Nom</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Classe</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Année</th>
                        <th class="px-6 py-3 text-right text-[10px] font-bold uppercase text-zinc-400">%</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-6 py-3">{{ $item->last_name }} {{ $item->first_name }}</td>
                            <td class="px-6 py-3 text-zinc-600">{{ $item->class }}</td>
                            <td class="px-6 py-3 text-zinc-600">{{ $item->year }}</td>
                            <td class="px-6 py-3 text-right font-bold text-emerald-700">{{ $item->percentage }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-zinc-500">Aucun lauréat pour ces filtres.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($items->hasPages())
                <div class="border-t border-zinc-100 px-6 py-4">{{ $items->links() }}</div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
