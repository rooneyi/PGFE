<!doctype html>
<html lang="fr" class="h-full">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 10px; }
    </style>
</head>

<body class="h-full bg-zinc-50 text-zinc-950 antialiased">

@php
    $adminMenu = app(\App\Services\MenuService\AdminMenuService::class);
    $showSidebar = $adminMenu->shouldShowModuleSidebar();
    $breadcrumbExtras = [];
    if (is_array($__breadcrumbExtras ?? null)) {
        $breadcrumbExtras = $__breadcrumbExtras;
    }
@endphp

<div
    class="flex min-h-screen"
    x-data="{ mobileSidebarOpen: false }"
    @keydown.escape.window="mobileSidebarOpen = false"
>
    @if($showSidebar)
        <div
            x-show="mobileSidebarOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileSidebarOpen = false"
            class="fixed inset-0 z-40 bg-zinc-900/50 backdrop-blur-[1px] lg:hidden"
            aria-hidden="true"
        ></div>

        <aside
            id="admin-sidebar"
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-zinc-300/80 bg-white shadow-xl transition-transform duration-300 ease-out lg:translate-x-0 lg:shadow-none"
            :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 lg:hidden">
                <span class="text-sm font-bold text-zinc-900">
                    {{ auth()->user()?->hasRole('admin-proved') && ! auth()->user()?->hasRole('super-admin') ? 'Menu PROVED' : 'Menu module' }}
                </span>
                <button
                    type="button"
                    @click="mobileSidebarOpen = false"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50"
                    aria-label="Fermer le menu"
                >
                    <iconify-icon icon="lucide:x" width="18"></iconify-icon>
                </button>
            </div>
            @include('backend.layouts.partials.sidebar.menu')
        </aside>
    @endif

    <div class="flex min-w-0 flex-1 flex-col {{ $showSidebar ? 'lg:pl-64' : '' }}">
        @if($showSidebar)
            <header class="sticky top-0 z-30 flex h-14 shrink-0 items-center gap-3 border-b border-zinc-200 bg-white/95 px-4 backdrop-blur-sm lg:hidden">
                <button
                    type="button"
                    @click="mobileSidebarOpen = true"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-300 bg-white text-zinc-800 shadow-sm hover:bg-zinc-50"
                    aria-label="Ouvrir le menu"
                    aria-controls="admin-sidebar"
                >
                    <iconify-icon icon="lucide:menu" width="20"></iconify-icon>
                </button>
                @php
                    $moduleMeta = $adminMenu->getModuleMeta();
                    $isProvedOnlyHeader = auth()->user()?->hasRole('admin-proved') && ! auth()->user()?->hasRole('super-admin');
                    if ($isProvedOnlyHeader && auth()->user()?->proved_id && ! auth()->user()->relationLoaded('proved')) {
                        auth()->user()->load(['proved:id,name,code,province_id', 'proved.province:id,name']);
                    }
                @endphp
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-zinc-900">
                        @if($isProvedOnlyHeader)
                            {{ auth()->user()->proved?->name ?? ($moduleMeta['label'] ?? config('app.name')) }}
                        @else
                            {{ $moduleMeta['label'] ?? config('app.name') }}
                        @endif
                    </p>
                    <p class="truncate text-[10px] font-semibold uppercase tracking-wider text-zinc-500">
                        {{ $isProvedOnlyHeader ? 'Espace PROVED' : 'Module actif' }}
                    </p>
                </div>
                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50"
                   aria-label="Tableau de bord">
                    <iconify-icon icon="lucide:layout-dashboard" width="18"></iconify-icon>
                </a>
            </header>
        @endif

        <main class="min-w-0 flex-1">
            <div class="admin-app-shell mx-auto flex min-h-full max-w-[1400px] flex-col px-4 py-6 sm:px-6 sm:py-8 lg:px-10 lg:py-10">

                <div class="mb-8 space-y-3">
                    @if(session('success'))
                        <div class="flex items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50/50 px-4 py-3 text-xs font-semibold text-emerald-900 animate-in fade-in slide-in-from-top-1">
                            <iconify-icon icon="lucide:check-circle" class="text-base text-emerald-500"></iconify-icon>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error') || $errors->any())
                        <div class="rounded-md border border-red-200 bg-red-50/50 px-4 py-3 text-xs text-red-900 animate-in fade-in slide-in-from-top-1">
                            <div class="mb-1 flex items-center gap-3 font-bold">
                                <iconify-icon icon="lucide:octagon-alert" class="text-base text-red-500"></iconify-icon>
                                Erreur détectée
                            </div>
                            <ul class="list-disc pl-7 font-medium opacity-80">
                                @if(session('error')) <li>{{ session('error') }}</li> @endif
                                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                @php
                    $usesPageShell = trim((string) $__env->yieldPushContent('admin-use-page-shell')) !== '';
                @endphp
                @if($showSidebar && ! $usesPageShell && View::hasSection('breadcrumbCurrent'))
                    <x-admin.breadcrumb-from-module
                        :extras="$breadcrumbExtras"
                        :current="trim($__env->yieldContent('breadcrumbCurrent'))"
                    />
                @endif

                <div class="flex-1 animate-in fade-in duration-700">
                    @hasSection('admin-content')
                        @yield('admin-content')
                    @else
                        {{ $slot ?? '' }}
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
