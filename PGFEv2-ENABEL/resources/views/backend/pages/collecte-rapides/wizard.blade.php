@extends('backend.layouts.app')

@section('title', $meta['title'] ?? 'Collecte rapide')
@section('breadcrumbCurrent', $meta['label'] ?? 'Wizard')

@section('admin-content')
    @php
        $stepKey = $meta['key'] ?? '';
        $matrix = $payload[$stepKey] ?? [];
        $isMatrixStep = in_array($meta['kind'] ?? null, ['simple_matrix', 'gender_matrix', 'type_matrix'], true);
        $progress = $lastStep > 0 ? (int) round(($step / $lastStep) * 100) : 0;
    @endphp

    <x-admin.shadcn-shell
        title="Wizard collecte"
        :subtitle="$collecte->sousDivision?->name.' · '.$collecte->schoolYear?->name.' · '.($collecte->proved?->name ?? '')"
        icon="lucide:clipboard-pen"
        :breadcrumbCurrent="$meta['label'] ?? 'Étape '.$step"
        :breadcrumbExtras="[
            ['label' => 'Saisies', 'url' => route('admin.collecte-rapides.index')],
        ]"
        :back-url="route('admin.collecte-rapides.index')"
        back-label="Saisies"
    >
        <x-admin.collecte-operations-nav active="saisies" />

        <x-slot:actions>
            @if($collecte->isSubmitted())
                <span class="inline-flex h-9 items-center rounded-md border border-emerald-200 bg-emerald-50 px-3 text-[10px] font-bold uppercase tracking-widest text-emerald-700">Soumise</span>
            @else
                <span class="inline-flex h-9 items-center rounded-md border border-amber-200 bg-amber-50 px-3 text-[10px] font-bold uppercase tracking-widest text-amber-700">Brouillon</span>
            @endif
            <div class="inline-flex h-9 items-center gap-2 rounded-md bg-zinc-900 px-3 text-[10px] font-bold uppercase tracking-widest text-white">
                Étape {{ $step }}/{{ $lastStep }}
                <span class="text-zinc-400">·</span>
                {{ $progress }}%
            </div>
        </x-slot:actions>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="h-1.5 w-full bg-zinc-100">
                <div class="h-full bg-zinc-900 transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>
            <div class="overflow-x-auto p-3">
                <div class="flex min-w-max gap-1">
                    @foreach($steps as $i => $s)
                        @php
                            $reached = $i <= max($collecte->current_step, $step);
                            $active = $i === $step;
                        @endphp
                        <a href="{{ route('admin.collecte-rapides.step', [$collecte, $i]) }}"
                           class="inline-flex items-center rounded-md px-2.5 py-1.5 text-[11px] font-semibold whitespace-nowrap transition-colors
                            {{ $active ? 'bg-zinc-900 text-white shadow-sm' : ($reached ? 'bg-zinc-100 text-zinc-800 hover:bg-zinc-200' : 'text-zinc-400 hover:bg-zinc-50') }}">
                            <span class="mr-1.5 tabular-nums opacity-60">{{ $i }}</span>
                            {{ $s['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-100 px-6 py-5">
                @if(!empty($meta['section']))
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-zinc-400">{{ $meta['section'] }}</p>
                @endif
                <h2 class="text-lg font-bold tracking-tight text-zinc-900">{{ $meta['title'] }}</h2>
            </div>

            <div class="p-6">
                @if($stepKey === 'contexte')
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach([
                            ['label' => 'Sous-division', 'value' => $collecte->sousDivision?->name],
                            ['label' => 'Année scolaire', 'value' => $collecte->schoolYear?->name],
                            ['label' => 'PROVED', 'value' => $collecte->proved?->name],
                            ['label' => 'Province', 'value' => $collecte->proved?->province?->name ?? '—'],
                        ] as $field)
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">{{ $field['label'] }}</p>
                                <p class="mt-1 text-sm font-bold text-zinc-900">{{ $field['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-4 text-sm font-medium text-zinc-500">Chaque étape suivante correspond à un tableau du fichier Excel de collecte rapide.</p>
                @elseif($stepKey === 'recap')
                    <p class="mb-6 text-sm font-medium text-zinc-600">Vérifiez les tableaux saisis puis soumettez la collecte au PROVED.</p>
                    <div class="flex flex-col gap-6">
                        @foreach($steps as $i => $s)
                            @continue(in_array($s['key'] ?? '', ['contexte', 'recap'], true))
                            @php $k = $s['key']; @endphp
                            <div class="rounded-xl border border-zinc-200">
                                <div class="border-b border-zinc-100 bg-zinc-50/80 px-4 py-3">
                                    <h3 class="text-sm font-bold text-zinc-900">{{ $i }}. {{ $s['title'] }}</h3>
                                </div>
                                <div class="p-4">
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
                    </div>
                @endif

                @if($isMatrixStep || $stepKey === 'contexte')
                    <form method="POST" action="{{ route('admin.collecte-rapides.step.update', [$collecte, $step]) }}" class="{{ $stepKey === 'contexte' ? 'mt-6' : '' }}">
                        @csrf
                        @method('PUT')

                        @if(($meta['kind'] ?? null) === 'simple_matrix')
                            @include('backend.pages.collecte-rapides.partials.matrix-simple', [
                                'matrix' => $matrix,
                                'columns' => $meta['columns'],
                                'regimes' => $regimes,
                                'readonly' => $readonly,
                                'prefix' => 'data',
                            ])
                        @elseif(($meta['kind'] ?? null) === 'gender_matrix')
                            @include('backend.pages.collecte-rapides.partials.matrix-gender', [
                                'matrix' => $matrix,
                                'columns' => $meta['columns'],
                                'regimes' => $regimes,
                                'sexKeys' => $meta['sex_keys'] ?? ['G', 'F'],
                                'readonly' => $readonly,
                                'prefix' => 'data',
                            ])
                        @elseif(($meta['kind'] ?? null) === 'type_matrix')
                            @include('backend.pages.collecte-rapides.partials.matrix-type', [
                                'matrix' => $matrix,
                                'columns' => $meta['columns'],
                                'regimes' => $regimes,
                                'teachingTypes' => $teachingTypes,
                                'readonly' => $readonly,
                                'prefix' => 'data',
                            ])
                        @endif

                        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-5">
                            <div>
                                @if($step > 0)
                                    <a href="{{ route('admin.collecte-rapides.step', [$collecte, $step - 1]) }}"
                                       class="inline-flex h-9 items-center rounded-md border border-zinc-200 bg-white px-4 text-xs font-bold uppercase tracking-widest text-zinc-700 hover:bg-zinc-50">
                                        Précédent
                                    </a>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if($readonly)
                                    @if($step < $lastStep)
                                        <a href="{{ route('admin.collecte-rapides.step', [$collecte, $step + 1]) }}"
                                           class="inline-flex h-9 items-center rounded-md bg-emerald-600 px-4 text-xs font-bold uppercase tracking-widest text-white hover:bg-emerald-700">Suivant</a>
                                    @endif
                                @else
                                    <button type="submit" name="advance" value="0"
                                            class="inline-flex h-9 items-center rounded-md border border-sky-200 bg-sky-50 px-4 text-xs font-bold uppercase tracking-widest text-sky-800 hover:bg-sky-100">
                                        Enregistrer
                                    </button>
                                    @if($step < $lastStep)
                                        <button type="submit" name="advance" value="1"
                                                class="inline-flex h-9 items-center gap-2 rounded-md bg-emerald-600 px-4 text-xs font-bold uppercase tracking-widest text-white hover:bg-emerald-700">
                                            Suivant
                                            <iconify-icon icon="lucide:arrow-right"></iconify-icon>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </form>
                @elseif($stepKey === 'recap')
                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-5">
                        <a href="{{ route('admin.collecte-rapides.step', [$collecte, $step - 1]) }}"
                           class="inline-flex h-9 items-center rounded-md border border-zinc-200 bg-white px-4 text-xs font-bold uppercase tracking-widest text-zinc-700 hover:bg-zinc-50">
                            Précédent
                        </a>
                        <div class="flex flex-wrap gap-2">
                            @can('submit', $collecte)
                                <form method="POST" action="{{ route('admin.collecte-rapides.submit', $collecte) }}" onsubmit="return confirm('Soumettre cette collecte ? Elle sera verrouillée.')">
                                    @csrf
                                    <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-md bg-emerald-600 px-4 text-xs font-bold uppercase tracking-widest text-white hover:bg-emerald-700">
                                        <iconify-icon icon="lucide:send"></iconify-icon>
                                        Soumettre
                                    </button>
                                </form>
                            @endcan
                            @if($collecte->isSubmitted())
                                @can('reopen', $collecte)
                                    <form method="POST" action="{{ route('admin.collecte-rapides.reopen', $collecte) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex h-9 items-center rounded-md border border-amber-200 bg-amber-50 px-4 text-xs font-bold uppercase tracking-widest text-amber-800">
                                            Rouvrir
                                        </button>
                                    </form>
                                @endcan
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-admin.shadcn-shell>
@endsection

@push('scripts')
<script>
window.collecteSimpleMatrix = function (matrix, columns, regimes) {
    return {
        matrix, columns, regimes,
        n(v) { const x = Number(v); return Number.isFinite(x) ? x : 0; },
        rowTotal(regime) { return this.columns.reduce((s, c) => s + this.n(this.matrix?.[regime]?.[c]), 0); },
        colTotal(col) { return this.regimes.reduce((s, r) => s + this.n(this.matrix?.[r]?.[col]), 0); },
        grandTotal() { return this.regimes.reduce((s, r) => s + this.rowTotal(r), 0); },
    };
};
window.collecteGenderMatrix = function (matrix, columns, regimes, sexKeys) {
    return {
        matrix, columns, regimes, sexKeys,
        n(v) { const x = Number(v); return Number.isFinite(x) ? x : 0; },
        rowSexTotal(regime, sex) { return this.columns.reduce((s, c) => s + this.n(this.matrix?.[regime]?.[c]?.[sex]), 0); },
        rowGrand(regime) { return this.sexKeys.reduce((s, sex) => s + this.rowSexTotal(regime, sex), 0); },
        colSexTotal(col, sex) { return this.regimes.reduce((s, r) => s + this.n(this.matrix?.[r]?.[col]?.[sex]), 0); },
        allSexTotal(sex) { return this.regimes.reduce((s, r) => s + this.rowSexTotal(r, sex), 0); },
        grandTotal() { return this.sexKeys.reduce((s, sex) => s + this.allSexTotal(sex), 0); },
    };
};
window.collecteTypeMatrix = function (matrix, columns, regimes, types) {
    return {
        matrix, columns, regimes, types,
        n(v) { const x = Number(v); return Number.isFinite(x) ? x : 0; },
        typeRowSex(regime, type, sex) { return this.columns.reduce((s, c) => s + this.n(this.matrix?.[regime]?.[type]?.[c]?.[sex]), 0); },
        typeRowGrand(regime, type) { return this.typeRowSex(regime, type, 'G') + this.typeRowSex(regime, type, 'F'); },
    };
};
</script>
@endpush
