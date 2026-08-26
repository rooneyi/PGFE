@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Personnels"
        subtitle="Liste des agents académiques (alignée sur l'application : saisie personnel)."
        icon="lucide:id-card"
        breadcrumbCurrent="Effectif"
    >
        <x-admin.personnel-operations-nav active="personnels" />

        <div class="mb-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Effectif total</p>
                <p class="mt-1 text-3xl font-black tracking-tighter text-zinc-900">{{ number_format($stats['total'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Comptes utilisateur liés</p>
                <p class="mt-1 text-3xl font-black tracking-tighter text-zinc-900">{{ number_format($stats['with_account'] ?? 0) }}</p>
            </div>
        </div>

        <div class="admin-data-card mb-6 p-4 md:p-6">
            <form method="GET" action="{{ route('admin.personnels.index') }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @if(! $selectedSchoolId)
                    <div class="space-y-2">
                        <x-admin.label for="school_id">Établissement</x-admin.label>
                        <x-admin.select id="school_id" name="school_id">
                            <option value="">Tous</option>
                            @foreach($schools as $sch)
                                <option value="{{ $sch->id }}" @selected(request('school_id') == $sch->id)>{{ $sch->name }}</option>
                            @endforeach
                        </x-admin.select>
                    </div>
                @endif
                <div class="space-y-2 {{ $selectedSchoolId ? 'md:col-span-3' : 'lg:col-span-2' }}">
                    <x-admin.label for="search">Recherche</x-admin.label>
                    <div class="flex gap-2">
                        <x-admin.input type="search" id="search" name="search" value="{{ request('search') }}"
                                       placeholder="Nom, matricule, email…" class="flex-1" />
                        <button type="submit" class="admin-btn-primary">Filtrer</button>
                        @if(request()->hasAny(['school_id', 'search']))
                            <a href="{{ route('admin.personnels.index') }}" class="admin-btn-secondary">Réinitialiser</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="admin-data-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/50">
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Agent</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Matricule</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Fonction</th>
                            @if(! $selectedSchoolId)
                                <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">École</th>
                            @endif
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($personnels as $p)
                            @php
                                $dName = trim(($p->pre_name ?? '').' '.($p->post_name ?? '').' '.($p->name ?? ''));
                                if ($dName === '') {
                                    $dName = 'Agent #'.$p->id;
                                }
                            @endphp
                            <tr class="transition-colors hover:bg-zinc-50/50">
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-zinc-900">{{ $dName }}</span>
                                    @if($p->email)
                                        <span class="block text-xs text-zinc-500">{{ $p->email }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-zinc-600">{{ $p->matricule ?? '—' }}</td>
                                <td class="px-6 py-4 text-xs text-zinc-600">{{ $p->fonction->title ?? '—' }}</td>
                                @if(! $selectedSchoolId)
                                    <td class="px-6 py-4 text-xs text-zinc-600">{{ $p->school->name ?? '—' }}</td>
                                @endif
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('admin.personnels.edit', $p) }}"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-900"
                                           title="Modifier">
                                            <iconify-icon icon="lucide:edit-3" width="16"></iconify-icon>
                                        </a>
                                        <form action="{{ route('admin.personnels.destroy', $p) }}" method="POST"
                                              onsubmit="return confirm('Supprimer cet agent ?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded text-zinc-400 hover:bg-red-50 hover:text-red-600"
                                                    title="Supprimer">
                                                <iconify-icon icon="lucide:trash-2" width="16"></iconify-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $selectedSchoolId ? 4 : 5 }}" class="px-6 py-16 text-center text-sm text-zinc-500">
                                    Aucun personnel pour les critères sélectionnés.
                                    <span class="mt-1 block text-xs text-zinc-400">La création se fait depuis l'application client (RH → Saisie personnel).</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($personnels->hasPages())
                <div class="border-t border-zinc-100 bg-zinc-50/30 px-6 py-4">
                    {{ $personnels->links() }}
                </div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
