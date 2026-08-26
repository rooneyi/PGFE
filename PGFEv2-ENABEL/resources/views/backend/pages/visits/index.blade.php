@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Visites de classe"
        subtitle="Visites pédagogiques pour l'école sélectionnée."
        icon="lucide:eye"
        breadcrumbCurrent="Liste"
    >
        <x-admin.students-operations-nav active="visits" />

        <div class="admin-data-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/50">
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Date / heure</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Classe</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Visiteur</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Objet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($visits as $visit)
                            <tr class="transition-colors hover:bg-zinc-50/50">
                                <td class="px-6 py-4 text-sm text-zinc-700">
                                    @php $vh = $visit->visit_hour; @endphp
                                    {{ $vh ? \Carbon\Carbon::parse($vh)->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-700">{{ $visit->classroom->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-700">{{ $visit->visiteur ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-600 max-w-xs truncate" title="{{ $visit->subject }}">{{ $visit->subject ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center text-sm text-zinc-500">Aucune visite trouvée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($visits->hasPages())
                <div class="border-t border-zinc-100 bg-zinc-50/30 px-6 py-4">{{ $visits->links() }}</div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
