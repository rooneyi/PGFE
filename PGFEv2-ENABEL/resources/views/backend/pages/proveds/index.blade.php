@extends('backend.layouts.app')

@section('admin-content')
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-700">
            <div>
                <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3">
                    <div class="h-12 w-12 rounded-2xl bg-emerald-600 flex items-center justify-center text-white">
                        <iconify-icon icon="lucide:landmark" width="28"></iconify-icon>
                    </div>
                    Proved
                </h1>
                <p class="text-sm text-gray-500 mt-2">Directions provinciales de l'éducation.</p>
            </div>
            @role('super-admin')
                <a href="{{ route('admin.proveds.create') }}" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-8 py-4 text-sm font-black text-white hover:bg-emerald-700">
                    <iconify-icon icon="lucide:plus-circle" width="20"></iconify-icon>
                    AJOUTER UN PROVED
                </a>
            @endrole
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase">ID</th>
                        <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase">Nom</th>
                        <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase">Code</th>
                        <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase">Province</th>
                        <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($proveds as $proved)
                        <tr class="hover:bg-emerald-50/20">
                            <td class="px-8 py-6 text-sm text-gray-400">#{{ $proved->id }}</td>
                            <td class="px-8 py-6 font-black text-gray-800 dark:text-white">{{ $proved->name }}</td>
                            <td class="px-8 py-6 font-mono text-sm">{{ $proved->code }}</td>
                            <td class="px-8 py-6 text-sm">{{ $proved->province?->name ?? '—' }}</td>
                            <td class="px-8 py-6 text-right">
                                <a href="{{ route('admin.proveds.edit', $proved) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gray-50 text-gray-400 hover:bg-emerald-600 hover:text-white">
                                    <iconify-icon icon="lucide:pen-line" width="20"></iconify-icon>
                                </a>
                                @role('super-admin')
                                    <form action="{{ route('admin.proveds.destroy', $proved) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce proved ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gray-50 text-red-400 hover:bg-red-600 hover:text-white">
                                            <iconify-icon icon="lucide:trash-2" width="20"></iconify-icon>
                                        </button>
                                    </form>
                                @endrole
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-8 py-12 text-center text-gray-500">Aucun proved.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($proveds->hasPages())
                <div class="px-8 py-4">{{ $proveds->links() }}</div>
            @endif
        </div>
    </div>
@endsection
