@extends('backend.layouts.app')

@section('admin-content')
    <h1 class="text-lg font-semibold mb-6">Modifier le proved</h1>
    <form method="POST" action="{{ route('admin.proveds.update', $proved) }}" class="space-y-6 max-w-lg">
        @csrf @method('PUT')
        <div>
            <label for="name" class="block text-sm font-medium mb-1">Nom *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $proved->name) }}" required class="w-full rounded-md border-gray-300 text-sm" />
            @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="code" class="block text-sm font-medium mb-1">Code *</label>
            <input type="text" id="code" name="code" value="{{ old('code', $proved->code) }}" required class="w-full rounded-md border-gray-300 text-sm" />
            @error('code')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="province_id" class="block text-sm font-medium mb-1">Province</label>
            <select id="province_id" name="province_id" class="w-full rounded-md border-gray-300 text-sm">
                <option value="">— Aucune —</option>
                @foreach($provinces as $p)
                    <option value="{{ $p->id }}" @selected(old('province_id', $proved->province_id) == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.proveds.index') }}" class="text-sm px-4 py-2 border rounded-md">Annuler</a>
            <button type="submit" class="rounded-md bg-emerald-600 px-5 py-2 text-sm text-white">Mettre à jour</button>
        </div>
    </form>
@endsection
