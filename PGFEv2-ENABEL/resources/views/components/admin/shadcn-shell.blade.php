@push('admin-use-page-shell')
@endpush

@props([
    'title',
    'subtitle' => null,
    'icon' => 'lucide:layout-grid',
    'breadcrumbCurrent' => '',
    'breadcrumbExtras' => [],
    'backUrl' => null,
    'backLabel' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-6 animate-in fade-in duration-500']) }}>
    <x-admin.breadcrumb-from-module
        :extras="$breadcrumbExtras"
        :current="$breadcrumbCurrent"
        :back-url="$backUrl"
        :back-label="$backLabel"
    />

    <div class="flex flex-col justify-between gap-4 border-b border-zinc-300 pb-6 md:flex-row md:items-end">
        <div class="flex items-start gap-4">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-zinc-900 text-white shadow-md ring-1 ring-zinc-800">
                <iconify-icon icon="{{ $icon }}" width="22"></iconify-icon>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-950 sm:text-3xl">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-1 text-sm font-medium text-zinc-600">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    {{ $slot }}
</div>
