@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Modifier le cycle"
        subtitle="Mise à jour de {{ $cycle->name }}."
        icon="lucide:layers-3"
        breadcrumbCurrent="Modifier"
        :breadcrumb-extras="[['label' => 'Cycles', 'url' => route('admin.cycles.index')]]"
        :back-url="route('admin.cycles.index')"
        back-label="Liste des cycles"
    >
        <x-admin.form-card>
            <form method="POST" action="{{ route('admin.cycles.update', $cycle) }}">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <x-admin.label for="filiaire_id" :required="true">Filière / section</x-admin.label>
                    <x-admin.select id="filiaire_id" name="filiaire_id" required>
                        <option value="">Sélectionner</option>
                        @foreach($filiaires as $filiaire)
                            <option value="{{ $filiaire->id }}" @selected(old('filiaire_id', $cycle->filiaire_id) == $filiaire->id)>{{ $filiaire->name }}</option>
                        @endforeach
                    </x-admin.select>
                    @error('filiaire_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <x-admin.label for="name" :required="true">Nom du cycle</x-admin.label>
                    <x-admin.input id="name" name="name" type="text" value="{{ old('name', $cycle->name) }}" required
                                   placeholder="Ex: Cycle 1" />
                    @error('name')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
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
