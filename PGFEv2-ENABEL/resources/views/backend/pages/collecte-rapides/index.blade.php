@extends('backend.layouts.app')

@section('title', 'Collecte rapide')
@section('breadcrumbCurrent', 'Saisies')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Collecte rapide"
        subtitle="Tableaux statistiques scolaires par sous-division — format PROVED."
        icon="lucide:clipboard-list"
        breadcrumbCurrent="Saisies"
    >
        <x-admin.collecte-operations-nav active="saisies" />

        <x-slot:actions>
            <a href="{{ route('admin.collecte-rapides.export', request()->only(['school_year_id', 'status'])) }}"
               class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-violet-200 bg-violet-50 px-4 text-xs font-bold uppercase tracking-widest text-violet-800 shadow-sm hover:bg-violet-100">
                <iconify-icon icon="lucide:download"></iconify-icon>
                Exporter
            </a>
            <a href="{{ route('admin.collecte-rapides.synthese') }}"
               class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-sky-200 bg-sky-50 px-4 text-xs font-bold uppercase tracking-widest text-sky-800 shadow-sm hover:bg-sky-100">
                <iconify-icon icon="lucide:bar-chart-3"></iconify-icon>
                Stats
            </a>
        </x-slot:actions>

        <div class="grid gap-4 md:grid-cols-4">
            @foreach([
                ['label' => 'Total collectes', 'val' => $stats['total'], 'icon' => 'lucide:files'],
                ['label' => 'Brouillons', 'val' => $stats['draft'], 'icon' => 'lucide:pencil-line'],
                ['label' => 'Soumises', 'val' => $stats['submitted'], 'icon' => 'lucide:badge-check'],
                ['label' => 'Sous-divisions', 'val' => $stats['sous_divisions'], 'icon' => 'lucide:network'],
            ] as $card)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-3">
                        <h3 class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">{{ $card['label'] }}</h3>
                        <div class="flex size-8 items-center justify-center rounded-lg border border-zinc-100 bg-zinc-50 text-zinc-400">
                            <iconify-icon icon="{{ $card['icon'] }}" class="text-lg"></iconify-icon>
                        </div>
                    </div>
                    <div class="text-3xl font-black tracking-tighter text-zinc-900">{{ number_format($card['val']) }}</div>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-1 border-b border-zinc-100 px-6 py-5">
                <h2 class="text-base font-bold tracking-tight text-zinc-900">Importer un Excel</h2>
                <p class="text-xs font-medium text-zinc-500">Format « Collecte rapide » (1 feuille = 1 sous-division). Les feuilles SYNTHESE sont ignorées. Les collectes déjà soumises ne sont pas écrasées.</p>
            </div>
            <form method="POST" action="{{ route('admin.collecte-rapides.import') }}" enctype="multipart/form-data" class="grid gap-4 p-6 md:grid-cols-12 md:items-end">
                @csrf
                <div class="md:col-span-3">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Année scolaire</label>
                    <select name="school_year_id" required class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900/10">
                        <option value="">Choisir…</option>
                        @foreach($schoolYears as $year)
                            <option value="{{ $year->id }}" @selected(old('school_year_id', request('school_year_id')) == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-5">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Fichier .xlsx</label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                           class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-sky-600 file:px-3 file:py-2 file:text-xs file:font-bold file:uppercase file:tracking-widest file:text-white hover:file:bg-sky-700">
                </div>
                <div class="md:col-span-4">
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md border border-sky-200 bg-sky-50 px-4 text-xs font-bold uppercase tracking-widest text-sky-800 shadow-sm hover:bg-sky-100 md:w-auto">
                        <iconify-icon icon="lucide:upload"></iconify-icon>
                        Importer
                    </button>
                </div>
            </form>
            @if(session('import_warnings'))
                <div class="border-t border-amber-100 bg-amber-50/60 px-6 py-4 text-xs text-amber-900">
                    <p class="mb-1 font-bold uppercase tracking-wider">Avertissements import</p>
                    <ul class="list-disc pl-4 font-medium">
                        @foreach(session('import_warnings') as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-1 border-b border-zinc-100 px-6 py-5">
                <h2 class="text-base font-bold tracking-tight text-zinc-900">Nouvelle collecte</h2>
                <p class="text-xs font-medium text-zinc-500">Une fiche par sous-division et année scolaire, puis wizard tableau par tableau.</p>
            </div>
            <form method="POST" action="{{ route('admin.collecte-rapides.store') }}" class="grid gap-4 p-6 md:grid-cols-12 md:items-end">
                @csrf
                <div class="md:col-span-4">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Sous-division</label>
                    <select name="sous_division_id" required class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900/10">
                        <option value="">Choisir…</option>
                        @foreach($sousDivisions as $sd)
                            <option value="{{ $sd->id }}" @selected(old('sous_division_id') == $sd->id)>{{ $sd->name }} ({{ $sd->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Année scolaire</label>
                    <select name="school_year_id" required class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900/10">
                        <option value="">Choisir…</option>
                        @foreach($schoolYears as $year)
                            <option value="{{ $year->id }}" @selected(old('school_year_id') == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-5">
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-emerald-600 px-4 text-xs font-bold uppercase tracking-widest text-white shadow-sm hover:bg-emerald-700 md:w-auto">
                        <iconify-icon icon="lucide:play"></iconify-icon>
                        Démarrer le wizard
                    </button>
                </div>
            </form>
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-zinc-100 px-6 py-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-base font-bold tracking-tight text-zinc-900">Collectes</h2>
                    <p class="text-xs font-medium text-zinc-500">Brouillons et soumissions de votre PROVED.</p>
                </div>
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Année</label>
                        <select name="school_year_id" class="h-9 rounded-md border border-zinc-200 bg-white px-3 text-xs font-semibold" onchange="this.form.submit()">
                            <option value="">Toutes</option>
                            @foreach($schoolYears as $year)
                                <option value="{{ $year->id }}" @selected(request('school_year_id') == $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Statut</label>
                        <select name="status" class="h-9 rounded-md border border-zinc-200 bg-white px-3 text-xs font-semibold" onchange="this.form.submit()">
                            <option value="">Tous</option>
                            <option value="draft" @selected(request('status') === 'draft')>Brouillon</option>
                            <option value="submitted" @selected(request('status') === 'submitted')>Soumise</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 text-[10px] font-bold uppercase tracking-widest text-zinc-500">
                            <th class="px-6 py-3">Sous-division</th>
                            <th class="px-6 py-3">Année</th>
                            <th class="px-6 py-3">Statut</th>
                            <th class="px-6 py-3">Progression</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collectes as $collecte)
                            <tr class="border-b border-zinc-50 transition-colors hover:bg-zinc-50/80">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-zinc-900">{{ $collecte->sousDivision?->name }}</div>
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">{{ $collecte->sousDivision?->code }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium text-zinc-700">{{ $collecte->schoolYear?->name }}</td>
                                <td class="px-6 py-4">
                                    @if($collecte->isSubmitted())
                                        <span class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700">Soumise</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-700">Brouillon</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-1.5 w-28 overflow-hidden rounded-full bg-zinc-100">
                                            <div class="h-full rounded-full bg-zinc-900 transition-all" style="width: {{ $collecte->progressPercent() }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold tabular-nums text-zinc-500">{{ $collecte->progressPercent() }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.collecte-rapides.step', [$collecte, max(1, $collecte->current_step)]) }}"
                                           class="inline-flex h-8 items-center rounded-md bg-emerald-600 px-3 text-[10px] font-bold uppercase tracking-wider text-white hover:bg-emerald-700">
                                            Ouvrir
                                        </a>
                                        <a href="{{ route('admin.collecte-rapides.export-one', $collecte) }}"
                                           class="inline-flex h-8 items-center rounded-md border border-violet-200 bg-violet-50 px-3 text-[10px] font-bold uppercase tracking-wider text-violet-800 hover:bg-violet-100">
                                            Excel
                                        </a>
                                        @can('reopen', $collecte)
                                            <form action="{{ route('admin.collecte-rapides.reopen', $collecte) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 items-center rounded-md border border-amber-200 bg-amber-50 px-3 text-[10px] font-bold uppercase tracking-wider text-amber-800 hover:bg-amber-100">Rouvrir</button>
                                            </form>
                                        @endcan
                                        @can('delete', $collecte)
                                            <form action="{{ route('admin.collecte-rapides.destroy', $collecte) }}" method="POST" onsubmit="return confirm('Supprimer cette collecte ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 items-center rounded-md border border-red-200 bg-red-50 px-3 text-[10px] font-bold uppercase tracking-wider text-red-700 hover:bg-red-100">Suppr.</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                        <div class="flex size-12 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 text-zinc-400">
                                            <iconify-icon icon="lucide:inbox" width="22"></iconify-icon>
                                        </div>
                                        <div>
                                            <p class="font-bold text-zinc-900">Aucune collecte</p>
                                            <p class="mt-1 text-xs font-medium text-zinc-500">Créez une fiche ci-dessus pour démarrer la saisie.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($collectes->hasPages())
                <div class="border-t border-zinc-100 px-6 py-4">{{ $collectes->links() }}</div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
