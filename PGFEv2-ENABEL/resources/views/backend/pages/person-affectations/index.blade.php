@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Affectations"
        subtitle="Mises en place et missions (aligné sur RH → mise en place personnel)."
        icon="lucide:map-pin"
        breadcrumbCurrent="Affectations"
    >
        <x-admin.personnel-operations-nav active="affectations" />

        <div class="admin-data-card mb-6 p-4 md:p-6">
            <form method="GET" action="{{ route('admin.person-affectations.index') }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
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
                <div class="space-y-2">
                    <x-admin.label for="school_year_id">Année scolaire</x-admin.label>
                    <x-admin.select id="school_year_id" name="school_year_id">
                        <option value="">Toutes</option>
                        @foreach($schoolYears as $sy)
                            <option value="{{ $sy->id }}" @selected($schoolYearId == $sy->id)>{{ $sy->name }}</option>
                        @endforeach
                    </x-admin.select>
                </div>
                <div class="space-y-2 lg:col-span-2">
                    <x-admin.label for="search">Recherche agent</x-admin.label>
                    <div class="flex gap-2">
                        <x-admin.input type="search" id="search" name="search" value="{{ request('search') }}"
                                       placeholder="Nom, matricule…" class="flex-1" />
                        <button type="submit" class="admin-btn-primary">Filtrer</button>
                        @if(request()->hasAny(['school_id', 'school_year_id', 'search']))
                            <a href="{{ route('admin.person-affectations.index') }}" class="admin-btn-secondary">Réinitialiser</a>
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
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Lieu</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Période</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Année</th>
                            @if(! $selectedSchoolId)
                                <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">École</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($affectations as $aff)
                            @php
                                $ap = $aff->academicPersonal;
                                $dName = trim(($ap?->pre_name ?? '').' '.($ap?->post_name ?? '').' '.($ap?->name ?? '')) ?: '—';
                            @endphp
                            <tr class="transition-colors hover:bg-zinc-50/50">
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-zinc-900">{{ $dName }}</span>
                                    @if($ap?->matricule)
                                        <span class="block font-mono text-xs text-zinc-500">{{ $ap->matricule }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-800">{{ $aff->lieu_affectation ?? '—' }}</td>
                                <td class="px-6 py-4 text-xs text-zinc-600">
                                    @if($aff->date_debut)
                                        {{ \Illuminate\Support\Carbon::parse($aff->date_debut)->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                    @if($aff->durree_jours)
                                        <span class="mt-1 block text-[10px] text-zinc-400">{{ $aff->durree_jours }} jour(s)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-zinc-600">{{ $aff->schoolYear->name ?? '—' }}</td>
                                @if(! $selectedSchoolId)
                                    <td class="px-6 py-4 text-xs text-zinc-600">{{ $aff->school->name ?? '—' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $selectedSchoolId ? 4 : 5 }}" class="px-6 py-16 text-center text-sm text-zinc-500">
                                    Aucune affectation pour les critères sélectionnés.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($affectations->hasPages())
                <div class="border-t border-zinc-100 bg-zinc-50/30 px-6 py-4">
                    {{ $affectations->links() }}
                </div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
