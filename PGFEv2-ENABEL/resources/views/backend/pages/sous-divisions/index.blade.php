@extends('backend.layouts.app')

@section('admin-content')
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Sous-divisions</h1>
                <p class="text-sm text-gray-500">Rattachement Proved → Écoles</p>
            </div>
            @can('create', App\Models\SousDivision::class)
                <a href="{{ route('admin.sous-divisions.create') }}" class="rounded-md bg-violet-600 px-4 py-2 text-sm text-white">Nouvelle sous-division</a>
            @endcan
        </div>

        @role('super-admin')
            @if($proveds->isNotEmpty())
                <form method="GET" class="flex gap-2 items-end">
                    <div>
                        <label class="text-xs font-medium">Filtrer par proved</label>
                        <select name="proved_id" class="rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                            <option value="">Tous</option>
                            @foreach($proveds as $p)
                                <option value="{{ $p->id }}" @selected(request('proved_id') == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            @endif
        @endrole

        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg border">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Nom</th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Proved</th>
                        <th class="px-4 py-3">Écoles</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sousDivisions as $sd)
                        <tr class="border-t">
                            <td class="px-4 py-3">#{{ $sd->id }}</td>
                            <td class="px-4 py-3 font-medium">{{ $sd->name }}</td>
                            <td class="px-4 py-3 font-mono">{{ $sd->code }}</td>
                            <td class="px-4 py-3">{{ $sd->proved?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $sd->schools_count }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @role('admin-proved')
                                    <a href="{{ route('admin.sous-division.switch', $sd->id) }}" class="text-violet-600 text-xs font-semibold">Focus</a>
                                @endrole
                                @can('update', $sd)
                                    <a href="{{ route('admin.sous-divisions.edit', $sd) }}" class="text-violet-600">Modifier</a>
                                @endcan
                                @can('delete', $sd)
                                    <form action="{{ route('admin.sous-divisions.destroy', $sd) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600">Supprimer</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucune sous-division.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($sousDivisions->hasPages())
                <div class="p-4">{{ $sousDivisions->links() }}</div>
            @endif
        </div>
    </div>
@endsection
