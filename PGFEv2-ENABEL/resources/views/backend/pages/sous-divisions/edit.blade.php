@extends('backend.layouts.app')

@section('admin-content')
    <h1 class="text-lg font-semibold mb-6">Modifier la sous-division</h1>
    <form method="POST" action="{{ route('admin.sous-divisions.update', $sousDivision) }}" class="space-y-6 max-w-lg">
        @csrf @method('PUT')
        <div>
            <label for="proved_id" class="block text-sm font-medium mb-1">Proved *</label>
            <select id="proved_id" name="proved_id" required class="w-full rounded-md border-gray-300 text-sm">
                @foreach($proveds as $p)
                    <option value="{{ $p->id }}" @selected(old('proved_id', $sousDivision->proved_id) == $p->id)>{{ $p->name }} ({{ $p->code }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="name" class="block text-sm font-medium mb-1">Nom *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $sousDivision->name) }}" required class="w-full rounded-md border-gray-300 text-sm" />
        </div>
        <div>
            <label for="code" class="block text-sm font-medium mb-1">Code *</label>
            <input type="text" id="code" name="code" value="{{ old('code', $sousDivision->code) }}" required class="w-full rounded-md border-gray-300 text-sm" />
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.sous-divisions.index') }}" class="text-sm px-4 py-2 border rounded-md">Annuler</a>
            <button type="submit" class="rounded-md bg-violet-600 px-5 py-2 text-sm text-white">Mettre à jour</button>
        </div>
    </form>
@endsection
