@php
    /** @var array $provedDashboard */
    $d = $provedDashboard;
    $stats = $d['stats'];
    $rate = max(0, min(100, (int) $stats['completion_rate']));
    $avg = max(0, min(100, (int) $stats['avg_progress']));
@endphp

<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    {{-- Hero — light shadcn card (pas de bloc noir massif) --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-sm">
        <div class="grid gap-0 md:grid-cols-[1.35fr_1fr]">
            <div class="flex flex-col justify-center gap-4 p-6 md:p-8 lg:p-10">
                <p class="text-sm font-medium text-zinc-500">Academy PROVED · {{ config('app.name') }}</p>
                <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                    Bonjour, {{ $d['greeting_name'] }}
                </h1>
                <p class="max-w-xl text-base text-zinc-600">
                    Que souhaitez-vous faire aujourd’hui pour votre collecte de données scolaires ?
                </p>
                <p class="max-w-xl text-sm text-zinc-500">
                    Saisissez les tableaux par sous-division, suivez la progression et consultez la synthèse
                    @if($d['proved'])
                        — <span class="font-medium text-zinc-700">{{ $d['proved']->name }}</span>@if($d['proved']->province)<span class="text-zinc-400"> · {{ $d['proved']->province->name }}</span>@endif
                    @endif.
                </p>
                <div class="flex flex-wrap gap-2.5 pt-1">
                    <a href="{{ route('admin.collecte-rapides.index') }}"
                       class="inline-flex h-9 items-center gap-2 rounded-md bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-700">
                        <iconify-icon icon="lucide:clipboard-list"></iconify-icon>
                        Ouvrir Collecte
                    </a>
                    <a href="{{ route('admin.sous-divisions.create') }}"
                       class="inline-flex h-9 items-center gap-2 rounded-md border border-violet-200 bg-violet-50 px-4 text-sm font-medium text-violet-800 hover:bg-violet-100">
                        <iconify-icon icon="lucide:network"></iconify-icon>
                        Nouvelle sous-division
                    </a>
                    <a href="{{ route('admin.schools.create') }}"
                       class="inline-flex h-9 items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-4 text-sm font-medium text-amber-800 hover:bg-amber-100">
                        <iconify-icon icon="mdi:school"></iconify-icon>
                        Nouvelle école
                    </a>
                    <a href="{{ route('admin.collecte-rapides.synthese') }}"
                       class="inline-flex h-9 items-center gap-2 rounded-md border border-sky-200 bg-sky-50 px-4 text-sm font-medium text-sky-800 hover:bg-sky-100">
                        <iconify-icon icon="lucide:bar-chart-3"></iconify-icon>
                        Voir les stats
                    </a>
                </div>
            </div>

            <div class="border-t border-zinc-100 bg-zinc-50/80 p-6 md:border-l md:border-t-0 md:p-8">
                <div class="flex h-full flex-col justify-center gap-4 rounded-lg border border-zinc-200/80 bg-white p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">Parcours collecte</p>
                        <span class="rounded-md border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-medium tabular-nums text-zinc-600">
                            {{ $d['path_done'] }} / {{ $d['path_total'] }} SD
                        </span>
                    </div>
                    <div>
                        <p class="text-base font-semibold text-zinc-900">Couverture des sous-divisions</p>
                        <p class="mt-1 text-sm text-zinc-500">{{ $rate }}% des SD ont au moins une collecte soumise</p>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-100">
                        <div class="h-full rounded-full bg-zinc-800 transition-all" style="width: {{ $rate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-12">
        <div class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-sm lg:col-span-4">
            <p class="text-sm font-semibold text-zinc-900">Taux de complétion PROVED</p>
            <div class="mt-6 flex items-center gap-5">
                <div class="relative flex size-28 shrink-0 items-center justify-center">
                    <svg class="size-28 -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#f4f4f5" stroke-width="8"></circle>
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#3f3f46" stroke-width="8"
                                stroke-linecap="round"
                                stroke-dasharray="{{ 2 * 3.1416 * 42 }}"
                                stroke-dashoffset="{{ 2 * 3.1416 * 42 * (1 - $rate / 100) }}"></circle>
                    </svg>
                    <span class="absolute text-2xl font-semibold tabular-nums tracking-tight text-zinc-900">{{ $rate }}%</span>
                </div>
                <div class="space-y-2 text-sm text-zinc-500">
                    <p>Objectif : <span class="font-medium text-zinc-800">100%</span></p>
                    <p>Soumises : <span class="font-medium text-zinc-800">{{ $stats['submitted'] }}</span></p>
                    <p>Brouillons : <span class="font-medium text-zinc-800">{{ $stats['draft'] }}</span></p>
                </div>
            </div>
            <a href="{{ route('admin.collecte-rapides.synthese') }}" class="mt-6 inline-flex text-sm font-medium text-zinc-700 underline-offset-4 hover:text-zinc-900 hover:underline">
                Voir le détail →
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-8">
            @foreach([
                ['label' => 'Total collectes', 'val' => $stats['total'], 'icon' => 'lucide:files', 'hint' => 'Toutes années'],
                ['label' => 'Sous-divisions', 'val' => $stats['sous_divisions'], 'icon' => 'lucide:network', 'hint' => 'Périmètre PROVED'],
                ['label' => 'Collectes soumises', 'val' => $stats['submitted'], 'icon' => 'lucide:badge-check', 'hint' => number_format($stats['total'] > 0 ? ($stats['submitted'] / $stats['total']) * 100 : 0, 1).'% du total'],
                ['label' => 'Progression moyenne', 'val' => $avg.'%', 'icon' => 'lucide:activity', 'hint' => 'Sur les fiches ouvertes'],
            ] as $card)
                <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ $card['label'] }}</p>
                        <div class="flex size-8 items-center justify-center rounded-md border border-zinc-200 bg-zinc-50 text-zinc-500">
                            <iconify-icon icon="{{ $card['icon'] }}"></iconify-icon>
                        </div>
                    </div>
                    <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight text-zinc-900">
                        {{ is_numeric($card['val']) ? number_format((int) $card['val']) : $card['val'] }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-12">
        <div class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-sm lg:col-span-5">
            <div class="mb-6">
                <h2 class="text-base font-semibold text-zinc-900">Statistiques de progression</h2>
                <p class="text-sm text-zinc-500">Répartition des saisies</p>
            </div>
            <div class="space-y-5">
                @php
                    $bars = [
                        ['label' => 'Activité totale', 'pct' => $avg, 'count' => $stats['total']],
                        ['label' => 'En cours (brouillon)', 'pct' => $stats['total'] > 0 ? (int) round(($stats['draft'] / $stats['total']) * 100) : 0, 'count' => $stats['draft']],
                        ['label' => 'Terminées (soumises)', 'pct' => $stats['total'] > 0 ? (int) round(($stats['submitted'] / $stats['total']) * 100) : 0, 'count' => $stats['submitted']],
                    ];
                @endphp
                @foreach($bars as $bar)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="text-zinc-600">{{ $bar['label'] }}</span>
                            <span class="font-medium tabular-nums text-zinc-800">{{ $bar['count'] }} · {{ $bar['pct'] }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-zinc-100">
                            <div class="h-full rounded-full bg-zinc-700" style="width: {{ $bar['pct'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-8 grid grid-cols-3 gap-3 border-t border-zinc-100 pt-5 text-center">
                <div>
                    <p class="text-2xl font-semibold tabular-nums tracking-tight text-zinc-900">{{ $stats['total'] }}</p>
                    <p class="text-xs text-zinc-500">Total</p>
                </div>
                <div>
                    <p class="text-2xl font-semibold tabular-nums tracking-tight text-zinc-900">{{ $stats['draft'] }}</p>
                    <p class="text-xs text-zinc-500">En cours</p>
                </div>
                <div>
                    <p class="text-2xl font-semibold tabular-nums tracking-tight text-zinc-900">{{ $stats['submitted'] }}</p>
                    <p class="text-xs text-zinc-500">Terminées</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-sm lg:col-span-7">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-zinc-900">Classement sous-divisions</h2>
                <p class="text-sm text-zinc-500">Selon les collectes soumises et la progression</p>
            </div>
            <ul class="divide-y divide-zinc-100">
                @forelse($d['leaderboard'] as $i => $row)
                    <li class="flex items-center gap-3 py-3">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-50 text-xs font-semibold tabular-nums text-zinc-500 ring-1 ring-zinc-200">
                            {{ $i + 1 }}
                        </span>
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[10px] font-semibold uppercase text-zinc-700 ring-1 ring-zinc-200">
                            {{ mb_substr($row['name'], 0, 2) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-zinc-900">{{ $row['name'] }}</p>
                            <p class="text-xs text-zinc-500">
                                {{ $row['submitted'] }} soumise(s) · {{ $row['draft'] }} brouillon(s)
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold tabular-nums text-zinc-900">{{ number_format($row['points']) }}</p>
                            <p class="text-[10px] font-medium uppercase tracking-wider text-zinc-400">pts</p>
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-zinc-500">Aucune sous-division rattachée.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-zinc-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-zinc-900">Collectes récentes</h2>
                <p class="text-sm text-zinc-500">Reprenez une saisie ou consultez une fiche</p>
            </div>
            <a href="{{ route('admin.collecte-rapides.index') }}"
               class="inline-flex h-9 items-center rounded-md border border-sky-200 bg-sky-50 px-3 text-sm font-medium text-sky-800 hover:bg-sky-100">
                Toutes les saisies
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 bg-zinc-50/60 text-xs font-medium text-zinc-500">
                        <th class="px-6 py-3 font-medium">Sous-division</th>
                        <th class="px-6 py-3 font-medium">Année</th>
                        <th class="px-6 py-3 font-medium">Statut</th>
                        <th class="px-6 py-3 font-medium">Progression</th>
                        <th class="px-6 py-3 text-right font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($d['recent'] as $row)
                        <tr class="border-b border-zinc-100 last:border-0 hover:bg-zinc-50/70">
                            <td class="px-6 py-3.5 font-medium text-zinc-900">{{ $row['sd'] }}</td>
                            <td class="px-6 py-3.5 text-zinc-600">{{ $row['year'] }}</td>
                            <td class="px-6 py-3.5">
                                @if($row['status'] === 'submitted')
                                    <span class="inline-flex rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Soumise</span>
                                @else
                                    <span class="inline-flex rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Brouillon</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-1.5 w-24 overflow-hidden rounded-full bg-zinc-100">
                                        <div class="h-full rounded-full bg-zinc-700" style="width: {{ $row['progress'] }}%"></div>
                                    </div>
                                    <span class="text-xs tabular-nums text-zinc-500">{{ $row['progress'] }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ $row['url'] }}"
                                   class="inline-flex h-8 items-center rounded-md bg-emerald-600 px-3 text-xs font-medium text-white hover:bg-emerald-700">
                                    Continuer
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-zinc-500">
                                Aucune collecte pour le moment —
                                <a href="{{ route('admin.collecte-rapides.index') }}" class="font-medium text-zinc-800 underline-offset-4 hover:underline">créer la première</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
