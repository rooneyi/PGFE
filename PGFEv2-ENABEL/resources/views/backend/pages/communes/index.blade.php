@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Communes"
        subtitle="Communes rattachées aux provinces."
        icon="lucide:map"
        breadcrumbCurrent="Liste"
    >
        <x-slot:actions>
            <a href="{{ route('admin.communes.create') }}" class="admin-btn-primary">
                <iconify-icon icon="lucide:plus-circle" width="16"></iconify-icon>
                Nouvelle commune
            </a>
        </x-slot:actions>

        <x-admin.crud-table :colspan="4">
            <x-slot:head>
                <th class="w-20 px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">ID</th>
                <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Commune</th>
                <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Province</th>
                <th class="px-6 py-4 text-right text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Actions</th>
            </x-slot:head>
            <x-slot:body>
                @forelse($communes as $c)
                    <tr class="transition-colors hover:bg-zinc-50/50">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-zinc-400">#{{ $c->id }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-zinc-900">{{ $c->name }}</td>
                        <td class="px-6 py-4 text-sm text-zinc-600">{{ $c->province->name ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.communes.edit', $c) }}"
                                   class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900">
                                    <iconify-icon icon="lucide:pen-line" width="16"></iconify-icon>
                                </a>
                                <form action="{{ route('admin.communes.destroy', $c) }}" method="POST" onsubmit="return confirm('Supprimer cette commune ?')">
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
                        <td colspan="4" class="px-6 py-16 text-center text-sm text-zinc-500">Aucune commune enregistrée.</td>
                    </tr>
                @endforelse
            </x-slot:body>
            @if($communes->hasPages())
                <x-slot:pagination>{{ $communes->links() }}</x-slot:pagination>
            @endif
        </x-admin.crud-table>
    </x-admin.shadcn-shell>
@endsection
