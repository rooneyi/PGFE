@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Présences personnel"
        subtitle="Feuille de pointage par établissement et par date (alignée sur l'API : person-presences)."
        icon="lucide:calendar-check-2"
        breadcrumbCurrent="Pointage"
    >
        <x-admin.personnel-operations-nav active="presences" />

        <div class="admin-data-card mb-6 p-4 md:p-6">
            <form method="GET" action="{{ route('admin.personnel-presences.index') }}" id="personnel-presence-filters" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @if(session('selected_school_id'))
                    <input type="hidden" name="school_id" value="{{ session('selected_school_id') }}">
                    <div class="space-y-2 lg:col-span-2">
                        <x-admin.label>Établissement</x-admin.label>
                        <p class="text-sm font-semibold text-zinc-900">{{ $school?->name ?? 'École active' }}</p>
                    </div>
                @else
                    <div class="space-y-2 lg:col-span-2">
                        <x-admin.label for="school_id">Établissement <span class="text-rose-600">*</span></x-admin.label>
                        <x-admin.select id="school_id" name="school_id">
                            <option value="">— Choisir un établissement —</option>
                            @foreach($schools as $sch)
                                <option value="{{ $sch->id }}" @selected($schoolId == $sch->id)>{{ $sch->name }}</option>
                            @endforeach
                        </x-admin.select>
                    </div>
                @endif
                <div class="space-y-2">
                    <x-admin.label for="date">Date</x-admin.label>
                    <x-admin.input type="date" id="date" name="date" value="{{ $date }}" max="{{ now()->format('Y-m-d') }}" />
                </div>
                <div class="space-y-2">
                    <x-admin.label for="status">Statut</x-admin.label>
                    <x-admin.select id="status" name="status">
                        <option value="">Tous</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" @selected($statusFilter === $st)>
                                @switch($st)
                                    @case('present') Présent @break
                                    @case('absent') Absent @break
                                    @case('absent_justified') Absent justifié @break
                                    @case('sick') Malade @break
                                @endswitch
                            </option>
                        @endforeach
                    </x-admin.select>
                </div>
                <div class="space-y-2 md:col-span-2 lg:col-span-4">
                    <x-admin.label for="search">Recherche</x-admin.label>
                    <div class="flex gap-2">
                        <x-admin.input type="search" id="search" name="search" value="{{ $search }}"
                                       placeholder="Nom, matricule, téléphone…" class="flex-1" />
                        <button type="submit" class="admin-btn-primary">Afficher</button>
                    </div>
                </div>
            </form>
        </div>

        @if($schoolId)
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.personnel-presences.initialize') }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <input type="hidden" name="date" value="{{ $date }}">
                    <button type="submit" class="admin-btn-secondary">
                        <iconify-icon icon="lucide:clipboard-plus" width="16"></iconify-icon>
                        Initialiser la feuille
                    </button>
                </form>

                <a href="{{ route('admin.personnel-presences.export', ['school_id' => $schoolId, 'date' => $date]) }}"
                   class="admin-btn-secondary">
                    <iconify-icon icon="lucide:file-spreadsheet" width="16"></iconify-icon>
                    Export Excel
                </a>

                <span class="text-xs font-medium text-zinc-500">
                    {{ $school?->name ?? 'École' }} · {{ $initializedCount }} ligne(s) en base · {{ $rows->count() }} agent(s)
                </span>
            </div>

            @if($rows->isEmpty())
                <div class="admin-data-card p-8 text-center text-sm text-zinc-600">
                    Aucun personnel dans cet établissement, ou aucun résultat pour les filtres.
                </div>
            @else
                <form method="POST" action="{{ route('admin.personnel-presences.bulk') }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <input type="hidden" name="date" value="{{ $date }}">

                    <div class="admin-data-card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr class="border-b border-zinc-200 bg-zinc-50/50">
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Agent</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Matricule</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @foreach($rows as $index => $row)
                                        @php
                                            $agent = $row['personnel'];
                                            $currentStatus = $row['status'];
                                            $agentName = trim(($agent->pre_name ?? '').' '.($agent->post_name ?? '').' '.($agent->name ?? ''));
                                        @endphp
                                        <tr class="transition-colors hover:bg-zinc-50/50">
                                            <td class="px-6 py-3">
                                                <span class="text-sm font-semibold text-zinc-900">{{ $agentName ?: '—' }}</span>
                                            </td>
                                            <td class="px-6 py-3 font-mono text-xs text-zinc-600">{{ $agent->matricule ?? '—' }}</td>
                                            <td class="px-6 py-3">
                                                <input type="hidden" name="presences[{{ $index }}][personnel_id]" value="{{ $agent->id }}">
                                                <x-admin.select name="presences[{{ $index }}][status]" class="!py-2 text-xs min-w-[10rem]">
                                                    <option value="present" @selected($currentStatus === 'present')>Présent</option>
                                                    <option value="absent" @selected($currentStatus === 'absent')>Absent</option>
                                                    <option value="absent_justified" @selected($currentStatus === 'absent_justified')>Absent justifié</option>
                                                    <option value="sick" @selected($currentStatus === 'sick')>Malade</option>
                                                </x-admin.select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-end border-t border-zinc-200 bg-zinc-50/30 px-6 py-4">
                            <button type="submit" class="admin-btn-primary">
                                <iconify-icon icon="lucide:save" width="16"></iconify-icon>
                                Enregistrer les présences
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Sélectionnez un <strong>établissement</strong> et une <strong>date</strong> pour afficher la feuille de présence
                (comme sur l'application : <code class="text-xs">GET /api/v1/presence/person-presences?date=</code>).
            </div>
        @endif
    </x-admin.shadcn-shell>
@endsection
