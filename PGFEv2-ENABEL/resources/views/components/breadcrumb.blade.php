@props([
    'links' => [],
    'current' => '',
    'backUrl' => null,
    'backLabel' => 'Retour',
])

<nav aria-label="Fil d'Ariane" class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex min-w-0 flex-wrap items-center gap-2">
        @if($backUrl)
            <a href="{{ $backUrl }}"
               class="admin-btn-back shrink-0">
                <iconify-icon icon="lucide:arrow-left" width="16"></iconify-icon>
                {{ $backLabel }}
            </a>
            <span class="hidden h-6 w-px bg-zinc-300 sm:inline-block" aria-hidden="true"></span>
        @endif

        <ol class="flex min-w-0 flex-wrap items-center gap-1 text-sm">
            <li>
                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-semibold text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900">
                    <iconify-icon icon="lucide:home" width="14"></iconify-icon>
                    <span class="hidden sm:inline">Accueil</span>
                </a>
            </li>

            @foreach($links as $link)
                <li class="flex items-center gap-1 text-zinc-400" aria-hidden="true">
                    <iconify-icon icon="lucide:chevron-right" width="14"></iconify-icon>
                </li>
                <li>
                    <a href="{{ $link['url'] }}"
                       class="rounded-md px-2 py-1 font-medium text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900">
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach

            @if($current)
                <li class="flex items-center gap-1 text-zinc-400" aria-hidden="true">
                    <iconify-icon icon="lucide:chevron-right" width="14"></iconify-icon>
                </li>
                <li>
                    <span class="rounded-md bg-zinc-900 px-2.5 py-1 text-xs font-bold text-white"
                          aria-current="page">
                        {{ $current }}
                    </span>
                </li>
            @endif
        </ol>
    </div>

    <a href="{{ route('admin.dashboard') }}"
       class="inline-flex h-9 shrink-0 items-center gap-2 self-start rounded-lg border border-zinc-300 bg-white px-3 text-[11px] font-bold uppercase tracking-wider text-zinc-700 shadow-sm transition-colors hover:bg-zinc-50 sm:self-center">
        <iconify-icon icon="lucide:layout-grid" width="14"></iconify-icon>
        Dashboard
    </a>
</nav>
