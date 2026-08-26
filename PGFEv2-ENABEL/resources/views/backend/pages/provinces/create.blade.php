@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Nouvelle province"
        subtitle="Ajouter une province et la rattacher au pays."
        icon="lucide:map-pin"
        breadcrumbCurrent="Créer"
        :breadcrumb-extras="[['label' => 'Provinces', 'url' => route('admin.provinces.index')]]"
        :back-url="route('admin.provinces.index')"
        back-label="Liste des provinces"
    >
        <x-admin.form-card>
            <form method="POST" action="{{ route('admin.provinces.store') }}">
                @csrf

                <div class="space-y-2">
                    <x-admin.label for="name" :required="true">Nom de la province</x-admin.label>
                    <x-admin.input id="name" name="name" type="text" value="{{ old('name') }}" required
                                   placeholder="Ex: Tshopo" />
                    @error('name')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <x-admin.label>Pays</x-admin.label>
                    <x-admin.input type="text" value="Democratic Republic of the Congo" readonly
                                   class="!border-zinc-200 !bg-zinc-100 !text-zinc-700" />
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
