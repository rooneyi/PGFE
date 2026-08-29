@php
    $menuService = app(\App\Services\MenuService\AdminMenuService::class);
    $menuGroups = $menuService->getMenu();
    $moduleMeta = $menuService->getModuleMeta();
    $user = auth()->user();
    $isProvedOnly = $user && $user->hasRole('admin-proved') && ! $user->hasRole('super-admin');
    $singleGroup = count($menuGroups) === 1;

    if ($isProvedOnly && $user->proved_id && ! $user->relationLoaded('proved')) {
        $user->load(['proved:id,name,code,province_id', 'proved.province:id,name']);
    }

    $provedName = $user?->proved?->name;
    $provedCode = $user?->proved?->code;
    $provinceName = $user?->proved?->province?->name;
@endphp

<div class="admin-sidebar-panel flex h-full min-h-0 flex-col {{ $isProvedOnly ? 'admin-sidebar-proved' : '' }}">
    <div class="border-b border-zinc-200 bg-gradient-to-b from-zinc-50 to-white px-4 py-4">
        @if($isProvedOnly)
            <a href="{{ route('admin.dashboard') }}"
               class="mb-3 flex w-full items-center gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/60 p-2.5 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm">
                    <iconify-icon icon="lucide:landmark" width="18"></iconify-icon>
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <p class="truncate text-[13px] font-bold leading-tight tracking-tight text-zinc-900">
                        {{ $provedName ?: config('app.name') }}
                    </p>
                    <p class="mt-0.5 truncate text-[10px] font-semibold uppercase tracking-wider text-emerald-700/80">
                        @if($provedCode){{ $provedCode }}@endif
                        @if($provedCode && $provinceName) · @endif
                        {{ $provinceName ?: 'Espace PROVED' }}
                    </p>
                </div>
            </a>
        @else
            <a href="{{ route('admin.dashboard') }}"
               class="mb-3 inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-zinc-600 transition-colors hover:text-zinc-900">
                <iconify-icon icon="lucide:arrow-left" width="14"></iconify-icon>
                Tableau de bord
            </a>

            @if($moduleMeta)
                <div class="flex w-full items-center gap-3 rounded-xl border border-zinc-200 bg-white p-2.5 shadow-sm">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-900 text-white shadow-md">
                        <iconify-icon icon="{{ $moduleMeta['icon'] }}" width="18"></iconify-icon>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-bold leading-none tracking-tight text-zinc-900">{{ $moduleMeta['label'] }}</p>
                        <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-zinc-500">Module</p>
                    </div>
                </div>
            @endif
        @endif
    </div>

    @if($menuService->shouldShowSchoolContextSwitchers())
        <div class="px-4 pb-2 pt-3">
            @include('backend.layouts.partials.sidebar._context_switchers')
        </div>
    @endif

    <nav class="admin-sidebar-nav custom-scrollbar flex flex-1 flex-col overflow-y-auto">
        @forelse ($menuGroups as $groupName => $groupItems)
            @php
                $groupAccent = match ($groupName) {
                    'Dashboard' => 'text-zinc-500',
                    'Organisation' => 'text-violet-600',
                    'Collecte rapide' => 'text-emerald-600',
                    default => 'text-zinc-500',
                };
                $groupIcon = match ($groupName) {
                    'Dashboard' => 'lucide:layout-dashboard',
                    'Organisation' => 'lucide:network',
                    'Collecte rapide' => 'lucide:clipboard-list',
                    default => null,
                };
            @endphp
            <div>
                @if (! $singleGroup)
                    <h3 class="admin-sidebar-group-title flex items-center gap-1.5 {{ $groupAccent }}">
                        @if($groupIcon)
                            <iconify-icon icon="{{ $groupIcon }}" width="12"></iconify-icon>
                        @endif
                        {{ __($groupName) }}
                    </h3>
                @endif
                <ul class="space-y-1">
                    @foreach ($groupItems as $item)
                        @include('backend.layouts.partials.sidebar.menu-item', ['item' => $item])
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="px-4 py-6 text-center text-xs font-medium text-zinc-500">Aucun menu pour ce module.</p>
        @endforelse
    </nav>

    <div class="mt-auto border-t border-zinc-200/80 bg-white/50 p-4 backdrop-blur-sm">
        @if($isProvedOnly)
            <div class="mb-3">
                <a href="{{ route('admin.schools.create') }}"
                   class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2 py-2 text-[10px] font-bold uppercase tracking-wider text-amber-800 hover:bg-amber-100">
                    <iconify-icon icon="lucide:plus" width="12"></iconify-icon>
                    Nouvelle école
                </a>
            </div>
        @endif

        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open"
                class="group flex w-full items-center gap-3 rounded-xl border border-transparent p-2 transition-all hover:border-zinc-200 hover:bg-white hover:shadow-md focus:outline-none">
                <div class="relative shrink-0">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background={{ $isProvedOnly ? '059669' : 'f4f4f5' }}&color={{ $isProvedOnly ? 'fff' : '18181b' }}&bold=true"
                        class="h-9 w-9 rounded-lg border border-zinc-200 object-cover" alt="">
                    <span
                        class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-500"></span>
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <p class="truncate text-xs font-bold leading-none text-zinc-900">{{ $user->name }}</p>
                    <p class="mt-1 truncate text-[10px] font-medium text-zinc-400">
                        {{ $isProvedOnly ? 'Admin PROVED' : $user->email }}
                    </p>
                </div>
                <iconify-icon icon="lucide:chevrons-up-down"
                    class="shrink-0 text-zinc-400 transition-colors group-hover:text-zinc-800" width="14"></iconify-icon>
            </button>

            <div x-show="open" @click.away="open = false" x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="translate-y-2 scale-95 opacity-0"
                x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                class="absolute bottom-full left-0 z-50 mb-2 w-full overflow-hidden rounded-xl border border-zinc-200 bg-white py-1.5 shadow-xl">
                <div class="mb-1 border-b border-zinc-50 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Compte</p>
                    <p class="mt-1 truncate text-xs font-medium text-zinc-600">{{ $user->email }}</p>
                </div>
                <form method="POST" action="{{ route('web.auth.logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50">
                        <iconify-icon icon="lucide:log-out" width="16"></iconify-icon>
                        Déconnexion
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
