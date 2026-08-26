@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Nouveau cycle"
        subtitle="Associer un cycle a une filiere."
        icon="lucide:layers-3"
        breadcrumbCurrent="Créer"
        :breadcrumb-extras="[['label' => 'Cycles', 'url' => route('admin.cycles.index')]]"
        :back-url="route('admin.cycles.index')"
        back-label="Liste des cycles"
    >
        <x-admin.form-card>
            <form method="POST" action="{{ route('admin.cycles.store') }}">
                @csrf

                <div class="space-y-2">
                    <label for="filiaire_id" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        Filiere / Section <span class="text-rose-600">*</span>
                    </label>
                    <x-admin.select id="filiaire_id" name="filiaire_id" required>
                        <option value="">Selectionner</option>
                        @foreach($filiaires as $filiaire)
                            <option value="{{ $filiaire->id }}" @selected(old('filiaire_id') == $filiaire->id)>{{ $filiaire->name }}</option>
                        @endforeach
                    </x-admin.select>
                    @error('filiaire_id')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <label for="name" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        Nom du cycle <span class="text-rose-600">*</span>
                    </label>
                    <x-admin.input id="name" name="name" type="text" value="{{ old('name') }}" required
                                   placeholder="Ex: Cycle 1" />
                    @error('name')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
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
