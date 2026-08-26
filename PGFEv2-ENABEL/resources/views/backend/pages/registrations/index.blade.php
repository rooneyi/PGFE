@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Inscriptions"
        subtitle="Inscriptions des élèves par classe et année scolaire."
        icon="lucide:user-plus"
        breadcrumbCurrent="Liste"
    >
        <x-admin.students-operations-nav active="registrations" />

        <x-slot:actions>
            <a href="{{ route('admin.registrations.create') }}" class="admin-btn-primary">
                <iconify-icon icon="lucide:plus" width="16"></iconify-icon>
                Nouvelle inscription
            </a>
        </x-slot:actions>

        <div class="admin-data-card overflow-hidden">
            <div class="border-b border-zinc-100 bg-zinc-50/30 p-4">
                <form method="GET" action="{{ route('admin.registrations.index') }}" class="flex max-w-md gap-2">
                    <div class="relative flex-1">
                        <iconify-icon icon="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" width="16"></iconify-icon>
                        <input type="search" name="search" value="{{ request('search') }}"
                               placeholder="Matricule, nom, prénom…"
                               class="admin-input !py-2 pl-10 text-sm">
                    </div>
                    <button type="submit" class="admin-btn-secondary">Rechercher</button>
                    @if(request('search'))
                        <a href="{{ route('admin.registrations.index') }}" class="admin-btn-secondary">Effacer</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/50">
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Matricule</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Élève</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Classe</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Année</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Date</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Statut</th>
                            <th class="px-6 py-4 text-right text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($registrations as $r)
                            <tr class="transition-colors hover:bg-zinc-50/50">
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-zinc-600">
                                    {{ $r->student->matricule ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-zinc-900">
                                        {{ trim(($r->student->lastname ?? '').' '.($r->student->name ?? '')) }}
                                    </span>
                                    @if($r->student?->firstname)
                                        <span class="block text-xs text-zinc-500">{{ $r->student->firstname }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-700">{{ $r->classroom->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-600">{{ $r->schoolYear->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-600">{{ $r->registration_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if($r->registration_status)
                                        <span class="inline-flex rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-800">Active</span>
                                    @else
                                        <span class="inline-flex rounded-md border border-zinc-200 bg-zinc-100 px-2 py-0.5 text-[10px] font-bold uppercase text-zinc-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.registrations.edit', $r) }}"
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900">
                                            <iconify-icon icon="lucide:pen-line" width="16"></iconify-icon>
                                        </a>
                                        <form action="{{ route('admin.registrations.destroy', $r) }}" method="POST" onsubmit="return confirm('Supprimer cette inscription ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 hover:bg-rose-50 hover:text-rose-700">
                                                <iconify-icon icon="lucide:trash-2" width="16"></iconify-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center text-sm text-zinc-500">
                                    Aucune inscription trouvée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($registrations->hasPages())
                <div class="border-t border-zinc-100 bg-zinc-50/30 px-6 py-4">
                    {{ $registrations->links() }}
                </div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
