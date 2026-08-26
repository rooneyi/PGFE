@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Repéchage"
        subtitle="Élèves non validés sous le seuil (délibérations en cours)."
        icon="lucide:refresh-cw"
        breadcrumbCurrent="Repéchage"
    >
        <x-admin.students-operations-nav active="repechages" />

        <form method="GET" action="{{ route('admin.repechages.index') }}" class="admin-data-card mb-6 p-4 md:p-6">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div class="space-y-2">
                    <x-admin.label for="school_year_id">Année</x-admin.label>
                    <x-admin.select id="school_year_id" name="school_year_id">
                        <option value="">—</option>
                        @foreach($schoolYears as $y)
                            <option value="{{ $y->id }}" @selected((int)$schoolYearId === (int)$y->id)>{{ $y->name }}</option>
                        @endforeach
                    </x-admin.select>
                </div>
                <div class="space-y-2">
                    <x-admin.label for="classroom_id">Classe</x-admin.label>
                    <x-admin.select id="classroom_id" name="classroom_id" required>
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}" @selected((int)$classroomId === (int)$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </x-admin.select>
                </div>
                <div class="space-y-2">
                    <x-admin.label for="course_id">Cours</x-admin.label>
                    <x-admin.select id="course_id" name="course_id" required>
                        @foreach($courses as $co)
                            <option value="{{ $co->id }}" @selected((int)$courseId === (int)$co->id)>{{ $co->label ?? $co->name }}</option>
                        @endforeach
                    </x-admin.select>
                </div>
                <div class="space-y-2">
                    <x-admin.label for="threshold">Seuil %</x-admin.label>
                    <x-admin.input type="number" id="threshold" name="threshold" value="{{ $threshold }}" min="0" max="100" step="1" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="admin-btn-primary w-full">Afficher</button>
                </div>
            </div>
        </form>

        @if($classroomId && $courseId)
            <div class="admin-data-card overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/50">
                            <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Élève</th>
                            <th class="px-6 py-3 text-right text-[10px] font-bold uppercase text-zinc-400">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($rows as $row)
                            @php $s = $row['deliberation']->student; @endphp
                            <tr>
                                <td class="px-6 py-3">{{ trim(($s->firstname ?? '').' '.($s->lastname ?? '').' '.($s->name ?? '')) }}</td>
                                <td class="px-6 py-3 text-right font-bold text-rose-600">{{ $row['grades']['pourcentage'] }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-6 py-12 text-center text-zinc-500">Aucun élève en repéchage pour ce seuil.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.shadcn-shell>
@endsection
