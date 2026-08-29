@php
    use App\Models\School;
    use App\Services\Organization\SchoolScopeResolver;

    $resolver = app(SchoolScopeResolver::class);
    $allowedIds = $resolver->allowedSchoolIds($user);

    $schoolsQuery = School::query()->orderBy('name');
    if ($allowedIds !== null) {
        $schoolsQuery->whereIn('id', $allowedIds);
    }
    $schools = $schoolsQuery->get(['id', 'name']);
@endphp

@if($user?->hasAnyRole(['super-admin', 'admin-proved', 'admin-sous-division']))
    {{-- Sous-division switcher masqué : flux produit centré sur l'école --}}

    <div x-data="{ open: false, schoolSearch: '' }" class="relative">
        <button @click="open = !open" type="button"
            class="group flex w-full items-center justify-between rounded-xl border border-zinc-200/90 bg-white p-2.5 shadow-sm transition-all hover:border-zinc-300 hover:bg-zinc-50/80">
            <div class="flex min-w-0 items-center gap-3 overflow-hidden">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white">
                    {{ session('selected_school_name') ? mb_substr(session('selected_school_name'), 0, 2) : 'GL' }}
                </div>
                <div class="min-w-0 truncate text-left">
                    <p class="text-[10px] font-bold uppercase leading-none tracking-widest text-zinc-400">École</p>
                    <p class="mt-1 truncate text-xs font-bold text-zinc-900">
                        {{ session('selected_school_name') ?? 'Vue globale' }}
                    </p>
                </div>
            </div>
            <iconify-icon icon="lucide:chevrons-up-down" class="shrink-0 text-zinc-400" width="14"></iconify-icon>
        </button>
        <div x-show="open" @click.away="open = false" x-cloak
            class="absolute left-0 top-full z-[100] mt-2 w-72 rounded-xl border border-zinc-200 bg-white p-2 shadow-2xl">
            <input x-model="schoolSearch" type="search" placeholder="Rechercher une école…" autocomplete="off"
                class="admin-input mb-2 !py-2 text-xs" />
            <div class="custom-scrollbar max-h-60 overflow-y-auto">
                <a href="{{ route('admin.school.switch', ['id' => 'all']) }}"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold {{ !session('selected_school_id') ? 'bg-indigo-50 text-indigo-700' : 'text-zinc-600 hover:bg-zinc-50' }}">
                    <iconify-icon icon="lucide:globe" width="16"></iconify-icon>
                    Toutes les écoles
                </a>
                <div class="my-2 border-t border-zinc-50"></div>
                @foreach ($schools as $sch)
                    <a href="{{ route('admin.school.switch', ['id' => $sch->id]) }}"
                        x-show="!schoolSearch || @js(mb_strtolower($sch->name, 'UTF-8')).includes(schoolSearch.toLowerCase())"
                        class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-100">
                        <span class="truncate">{{ $sch->name }}</span>
                        @if (session('selected_school_id') == $sch->id)
                            <iconify-icon icon="lucide:check" class="text-indigo-600" width="14"></iconify-icon>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
