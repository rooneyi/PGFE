@extends('backend.layouts.app')

@section('admin-content')
    <h1 class="text-lg font-semibold mb-6">Modifier l'école</h1>
    <form method="POST" action="{{ route('admin.schools.update', $school) }}" enctype="multipart/form-data" class="space-y-6 max-w-3xl">
        @csrf @method('PUT')
        <div class="grid gap-6 sm:grid-cols-2">
            @include('backend.pages.schools._sous_division_field', ['school' => $school])
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1" for="name">Nom <span class="text-red-600">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $school->name) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" required />
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="city">Ville <span class="text-red-600">*</span></label>
                <input type="text" name="city" id="city" value="{{ old('city', $school->city) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" required />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="address">Adresse <span class="text-red-600">*</span></label>
                <input type="text" name="address" id="address" value="{{ old('address', $school->address) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" required />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="country_id">Pays <span class="text-red-600">*</span></label>
                <select name="country_id" id="country_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" required>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}" @selected(old('country_id', $school->country_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="type_id">Type <span class="text-red-600">*</span></label>
                <select name="type_id" id="type_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" required>
                    @foreach($types as $t)
                        <option value="{{ $t->id }}" @selected(old('type_id', $school->type_id) == $t->id)>{{ $t->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="phone_number">Téléphone</label>
                <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $school->phone_number) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="email">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $school->email) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1" for="logo">Logo</label>
                <input type="file" name="logo" id="logo" accept="image/*" class="w-full text-sm" />
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.schools.index') }}" class="text-sm px-4 py-2 border rounded-md">Annuler</a>
            <button type="submit" class="rounded-md bg-violet-600 px-5 py-2 text-sm text-white">Mettre à jour</button>
        </div>
    </form>
@endsection
