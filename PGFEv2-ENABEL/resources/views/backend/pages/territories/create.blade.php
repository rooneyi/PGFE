@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Nouveau territoire"
        subtitle="Ajouter un territoire et le rattacher à une province."
        icon="lucide:map"
        breadcrumbCurrent="Créer"
        :breadcrumb-extras="[['label' => 'Territoires', 'url' => route('admin.territories.index')]]"
        :back-url="route('admin.territories.index')"
        back-label="Liste des territoires"
    >
        <x-admin.form-card>
            <form method="POST" action="{{ route('admin.territories.store') }}">
                @csrf

                <div class="space-y-2">
                    <x-admin.label for="name" :required="true">Nom du territoire</x-admin.label>
                    <x-admin.input id="name" name="name" type="text" value="{{ old('name') }}" required
                                   placeholder="Ex: Isangi" />
                    @error('name')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <x-admin.label for="province_id">Province</x-admin.label>
                    <x-admin.select id="province_id" name="province_id">
                        <option value="">Sélectionner</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" @selected(old('province_id') == $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </x-admin.select>
                    @error('province_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end border-t border-zinc-200 pt-5">
                    <button type="submit" class="admin-btn-primary">
                        <iconify-icon icon="lucide:save" width="16"></iconify-icon>
                        Enregistrer
                    </button>
                </div>
            </form>
        </x-admin.form-card>
    </x-admin.shadcn-shell>
@endsection
