@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Bulletin scolaire"
        subtitle="Consultation et impression PDF par élève."
        icon="lucide:file-text"
        breadcrumbCurrent="Bulletin"
    >
        <x-admin.students-operations-nav active="bulletins" />

        <form method="GET" action="{{ route('admin.bulletins.index') }}" class="admin-data-card mb-6 p-4 md:p-6">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-2">
                    <x-admin.label for="school_year_id">Année scolaire</x-admin.label>
                    <x-admin.select id="school_year_id" name="school_year_id">
                        <option value="">—</option>
                        @foreach($schoolYears as $y)
                            <option value="{{ $y->id }}" @selected((int)$schoolYearId === (int)$y->id)>{{ $y->name }}</option>
                        @endforeach
                    </x-admin.select>
                </div>
                <div class="space-y-2 md:col-span-2">
                    <x-admin.label for="search">Rechercher un élève</x-admin.label>
                    <x-admin.input type="search" name="search" value="{{ $search }}" placeholder="Nom, matricule…" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="admin-btn-secondary w-full">Rechercher</button>
                </div>
            </div>
        </form>

        <div class="admin-data-card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50/50">
                        <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Élève</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase text-zinc-400">Matricule</th>
                        <th class="px-6 py-3 text-right text-[10px] font-bold uppercase text-zinc-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($students as $student)
                        <tr>
                            <td class="px-6 py-3 font-medium">{{ trim(($student->firstname ?? '').' '.($student->lastname ?? '').' '.($student->name ?? '')) }}</td>
                            <td class="px-6 py-3 font-mono text-xs">{{ $student->matricule ?? '—' }}</td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('admin.bulletins.print', ['student_id' => $student->id, 'school_year_id' => $schoolYearId]) }}"
                                   target="_blank" rel="noopener"
                                   class="admin-btn-secondary !py-1.5 !text-xs">
                                    Voir / imprimer PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-12 text-center text-zinc-500">Aucun élève trouvé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.shadcn-shell>
@endsection
