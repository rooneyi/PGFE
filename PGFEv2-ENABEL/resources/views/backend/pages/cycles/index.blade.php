@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Cycles"
        subtitle="Cycles d'études rattachés aux filières."
        icon="lucide:layers-3"
        breadcrumbCurrent="Liste"
    >
        <x-slot:actions>
            <a href="{{ route('admin.cycles.create') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider text-zinc-700 hover:bg-zinc-50">
                <iconify-icon icon="lucide:plus-circle" width="16"></iconify-icon>
                Nouveau cycle
            </a>
        </x-slot:actions>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/50">
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400 w-20">ID</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Cycle</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Filière</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($cycles as $cycle)
                            <tr class="group transition-colors hover:bg-zinc-50/50">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-semibold text-zinc-400">
                                        #{{ str_pad((string) $cycle->id, 3, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-zinc-900">{{ $cycle->name }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-600">{{ $cycle->filiaire?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.cycles.edit', $cycle) }}"
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900">
                                            <iconify-icon icon="lucide:pen-line" width="16"></iconify-icon>
                                        </a>
                                        <form action="{{ route('admin.cycles.destroy', $cycle) }}" method="POST" onsubmit="return confirm('Supprimer ce cycle ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 hover:bg-rose-50 hover:text-rose-700">
                                                <iconify-icon icon="lucide:trash-2" width="16"></iconify-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center text-sm text-zinc-500">Aucun cycle défini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($cycles->hasPages())
                <div class="border-t border-zinc-100 bg-zinc-50/30 px-6 py-4">
                    {{ $cycles->links() }}
                </div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
