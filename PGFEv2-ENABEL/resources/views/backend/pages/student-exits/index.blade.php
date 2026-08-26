@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Sorties de classe"
        subtitle="Historique des permissions et sorties enregistrées."
        icon="lucide:log-out"
        breadcrumbCurrent="Liste"
    >
        <x-admin.students-operations-nav active="student-exits" />

        <div class="admin-data-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/50">
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Date</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Heure</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Élève</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Filière</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Année</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Motif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($studentExits as $exit)
                            <tr class="transition-colors hover:bg-zinc-50/50">
                                <td class="px-6 py-4 text-sm text-zinc-700">
                                    {{ $exit->date ? \Carbon\Carbon::parse($exit->date)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-600">{{ $exit->exit_time ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-800">
                                    {{ trim(($exit->student->firstname ?? '').' '.($exit->student->lastname ?? '').' '.($exit->student->name ?? '')) ?: 'Élève #'.$exit->student_id }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-600">{{ $exit->filiere->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-600">{{ $exit->schoolYear->title ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-500 max-w-xs truncate" title="{{ $exit->motif }}">{{ $exit->motif ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-sm text-zinc-500">Aucune sortie trouvée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($studentExits->hasPages())
                <div class="border-t border-zinc-100 bg-zinc-50/30 px-6 py-4">{{ $studentExits->links() }}</div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
