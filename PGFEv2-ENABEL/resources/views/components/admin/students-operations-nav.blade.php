@props(['active' => ''])

@php
    $tabs = [
        ['key' => 'overview', 'label' => 'Vue d\'ensemble', 'route' => 'admin.students.index', 'icon' => 'lucide:layout-grid'],
        ['key' => 'registrations', 'label' => 'Inscriptions', 'route' => 'admin.registrations.index', 'icon' => 'lucide:user-plus'],
        ['key' => 'presences', 'label' => 'Présences', 'route' => 'admin.presences.index', 'icon' => 'lucide:calendar-check'],
        ['key' => 'fiche-cotations', 'label' => 'Fiches de cotation', 'route' => 'admin.fiche-cotations.index', 'icon' => 'lucide:clipboard-list'],
        ['key' => 'deliberations', 'label' => 'Délibérations', 'route' => 'admin.deliberations.index', 'icon' => 'lucide:scale'],
        ['key' => 'repechages', 'label' => 'Repéchage', 'route' => 'admin.repechages.index', 'icon' => 'lucide:refresh-cw'],
        ['key' => 'validation-aureats', 'label' => 'Validation lauréats', 'route' => 'admin.validation-aureats.index', 'icon' => 'lucide:award'],
        ['key' => 'visits', 'label' => 'Visites de classe', 'route' => 'admin.visits.index', 'icon' => 'lucide:eye'],
        ['key' => 'indiscipline', 'label' => 'Gestion disciplinaire', 'route' => 'admin.indiscipline.index', 'icon' => 'lucide:shield-alert'],
        ['key' => 'bulletins', 'label' => 'Bulletin scolaire', 'route' => 'admin.bulletins.index', 'icon' => 'lucide:file-text'],
        ['key' => 'student-exits', 'label' => 'Sorties de classe', 'route' => 'admin.student-exits.index', 'icon' => 'lucide:log-out'],
    ];
@endphp

<nav class="mb-6 flex flex-wrap gap-1 rounded-lg border border-zinc-200 bg-zinc-50/80 p-1" aria-label="Opérations élèves">
    @foreach($tabs as $tab)
        @php $isActive = $active === $tab['key']; @endphp
        <a href="{{ route($tab['route']) }}"
           class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-semibold transition-colors {{ $isActive ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:bg-white hover:text-zinc-900' }}">
            <iconify-icon icon="{{ $tab['icon'] }}" width="14"></iconify-icon>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
