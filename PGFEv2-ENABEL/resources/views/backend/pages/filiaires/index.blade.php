@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Filières"
        subtitle="Catalogue des filieres et sections de l'ecole."
        icon="lucide:git-branch"
        breadcrumbCurrent="Liste"
    >
        <x-slot:actions>
            <a href="{{ route('admin.filiaires.create') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider text-zinc-700 hover:bg-zinc-50">
                <iconify-icon icon="lucide:plus-circle" width="16"></iconify-icon>
                Nouvelle filiere
            </a>
        </x-slot:actions>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/50">
                            <th class="w-20 px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">ID</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Intitule</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($filiaires as $f)
                            <tr class="transition-colors hover:bg-zinc-50/50">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-semibold text-zinc-400">#{{ str_pad((string) $f->id, 3, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-zinc-900">{{ $f->name }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.filiaires.edit', $f) }}"
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900">
                                            <iconify-icon icon="lucide:pen-line" width="16"></iconify-icon>
                                        </a>
                                        <form action="{{ route('admin.filiaires.destroy', $f) }}" method="POST" onsubmit="return confirm('Supprimer cette filiere ?')">
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
                                <td colspan="3" class="px-6 py-16 text-center text-sm text-zinc-500">Aucune filiere definie.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($filiaires->hasPages())
                <div class="border-t border-zinc-100 bg-zinc-50/30 px-6 py-4">
                    {{ $filiaires->links() }}
                </div>
            @endif
        </div>
    </x-admin.shadcn-shell>
@endsection
