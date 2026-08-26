@extends('backend.layouts.app')

@section('title', 'Stats collecte PROVED')
@section('breadcrumbCurrent', 'Stats')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Stats collecte"
        :subtitle="'Synthèse agrégée des collectes soumises'.($proved ? ' — '.$proved->name : '').($proved?->province ? ' · '.$proved->province->name : '')"
        icon="lucide:bar-chart-3"
        breadcrumbCurrent="Stats"
    >
        <x-admin.collecte-operations-nav active="stats" />

        <x-slot:actions>
            <a href="{{ route('admin.collecte-rapides.export-synthese', ['school_year_id' => $schoolYearId]) }}"
               class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-violet-200 bg-violet-50 px-4 text-xs font-bold uppercase tracking-widest text-violet-800 shadow-sm hover:bg-violet-100">
                <iconify-icon icon="lucide:download"></iconify-icon>
                Exporter Excel
            </a>
            <a href="{{ route('admin.collecte-rapides.index') }}"
               class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-4 text-xs font-bold uppercase tracking-widest text-emerald-800 shadow-sm hover:bg-emerald-100">
                <iconify-icon icon="lucide:clipboard-list"></iconify-icon>
                Saisies
            </a>
        </x-slot:actions>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">SD incluses</p>
                <p class="mt-2 text-3xl font-black tracking-tighter text-zinc-900">{{ $collectes->count() }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm md:col-span-2">
                <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-zinc-500">Périmètre</p>
                @if($collectes->isEmpty())
                    <p class="text-sm font-medium text-zinc-500">Aucune collecte soumise pour ces filtres.</p>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach($collectes as $c)
                            <span class="inline-flex items-center rounded-md border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-semibold text-zinc-700">
                                {{ $c->sousDivision?->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Année scolaire</label>
                <select name="school_year_id" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm font-medium shadow-sm">
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" @selected($schoolYearId == $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex h-10 items-center rounded-md bg-sky-600 px-4 text-xs font-bold uppercase tracking-widest text-white hover:bg-sky-700">
                Actualiser
            </button>
        </form>

        @foreach($steps as $i => $s)
            @continue(in_array($s['key'] ?? '', ['contexte', 'recap'], true))
            @php $k = $s['key']; @endphp
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 px-6 py-5">
                    @if(!empty($s['section']))
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-zinc-400">{{ $s['section'] }}</p>
                    @endif
                    <h2 class="text-lg font-bold tracking-tight text-zinc-900">{{ $s['title'] }}</h2>
                </div>
                <div class="p-6">
                    @include('backend.pages.collecte-rapides.partials.matrix-readonly', [
                        'kind' => $s['kind'] ?? null,
                        'matrix' => $payload[$k] ?? [],
                        'columns' => $s['columns'] ?? [],
                        'regimes' => $regimes,
                        'teachingTypes' => $teachingTypes,
                        'sexKeys' => $s['sex_keys'] ?? ['G', 'F'],
                    ])
                </div>
            </div>
        @endforeach
    </x-admin.shadcn-shell>
@endsection
