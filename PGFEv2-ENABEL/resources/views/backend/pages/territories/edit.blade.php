@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Modifier le territoire"
        subtitle="Mise à jour de {{ $territory->name }}."
        icon="lucide:map-pinned"
        breadcrumbCurrent="Modifier"
        :breadcrumb-extras="[['label' => 'Territoires', 'url' => route('admin.territories.index')]]"
        :back-url="route('admin.territories.index')"
        back-label="Liste des territoires"
    >
        <x-admin.form-card>
            <form method="POST" action="{{ route('admin.territories.update', $territory) }}">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <x-admin.label for="name" :required="true">Nom du territoire</x-admin.label>
                    <x-admin.input id="name" name="name" type="text" value="{{ old('name', $territory->name) }}" required
                           placeholder="Ex: Isangi">
                    @error('name')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <x-admin.label for="province_id">Province</x-admin.label>
                    <x-admin.select id="province_id" name="province_id">
                        <option value="">Selectionner</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" @selected(old('province_id', $territory->province_id) == $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </x-admin.select>
                    @error('province_id')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end border-t border-zinc-200 pt-5">
                    <button type="submit" class="admin-btn-primary">
                        <iconify-icon icon="lucide:save" width="16"></iconify-icon>
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </x-admin.form-card>
    </x-admin.shadcn-shell>
@endsection
