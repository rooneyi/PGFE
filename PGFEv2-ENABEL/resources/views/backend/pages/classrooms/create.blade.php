@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Nouvelle classe"
        subtitle="Ajouter une classe a l'ecole active."
        icon="lucide:layers"
        breadcrumbCurrent="Créer"
        :breadcrumb-extras="[['label' => 'Classes', 'url' => route('admin.classrooms.index')]]"
        :back-url="route('admin.classrooms.index')"
        back-label="Liste des classes"
    >
        <x-admin.form-card>
            <form method="POST" action="{{ route('admin.classrooms.store') }}">
                @csrf

                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Ecole</label>
                    <input type="text" value="{{ $activeSchoolName ?? '— (selectionnez une ecole dans le dashboard)' }}"
                           class="admin-input !bg-zinc-100 !border-zinc-200"
                           readonly>
                </div>

                <div class="space-y-2">
                    <label for="name" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        Nom de la classe <span class="text-rose-600">*</span>
                    </label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                           class="admin-input"
                           placeholder="Ex: 6eme A">
                    @error('name')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <label for="filiaire_id" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        Filiere / option <span class="text-rose-600">*</span>
                    </label>
                    <x-admin.select id="filiaire_id" name="filiaire_id" required>
                        <option value="">Selectionner</option>
                        @foreach($filiaires as $filiaire)
                            <option value="{{ $filiaire->id }}" @selected(old('filiaire_id') == $filiaire->id)>
                                {{ $filiaire->name }}
                            </option>
                        @endforeach
                    </x-admin.select>
                    @error('filiaire_id')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <label for="indicator" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Indicateur</label>
                    <input id="indicator" name="indicator" type="text" value="{{ old('indicator') }}"
                           class="admin-input"
                           placeholder="Ex: 6A">
                    @error('indicator')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                @if(isset($academicLevels) && $academicLevels->isNotEmpty())
                    <div class="space-y-2">
                        <label for="academic_level_id" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                            Niveau academique
                        </label>
                        <x-admin.select id="academic_level_id" name="academic_level_id">
                            <option value="">— Aucun —</option>
                            @foreach($academicLevels as $level)
                                <option value="{{ $level->id }}" @selected(old('academic_level_id') == $level->id)>
                                    {{ $level->name }}
                                </option>
                            @endforeach
                        </x-admin.select>
                        @error('academic_level_id')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endif

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
