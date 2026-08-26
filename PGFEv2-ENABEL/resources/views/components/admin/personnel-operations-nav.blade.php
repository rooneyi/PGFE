@props(['active' => ''])

@php
    $tabs = [
        ['key' => 'personnels', 'label' => 'Personnels', 'route' => 'admin.personnels.index', 'icon' => 'lucide:id-card'],
        ['key' => 'presences', 'label' => 'Présences', 'route' => 'admin.personnel-presences.index', 'icon' => 'lucide:calendar-check-2'],
        ['key' => 'affectations', 'label' => 'Affectations', 'route' => 'admin.person-affectations.index', 'icon' => 'lucide:map-pin'],
        ['key' => 'roles', 'label' => 'Rôles & assignation', 'route' => 'admin.roles.index', 'icon' => 'lucide:shield-check'],
    ];
@endphp

<nav class="mb-6 flex flex-wrap gap-1 rounded-lg border border-zinc-200 bg-zinc-50/80 p-1" aria-label="Opérations ressources humaines">
    @foreach($tabs as $tab)
        @php $isActive = $active === $tab['key']; @endphp
        <a href="{{ route($tab['route']) }}"
           class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-semibold transition-colors {{ $isActive ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:bg-white hover:text-zinc-900' }}">
            <iconify-icon icon="{{ $tab['icon'] }}" width="14"></iconify-icon>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
