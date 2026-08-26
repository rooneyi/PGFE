@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Modifier la classe"
        subtitle="Mise a jour de {{ $classroom->name }}."
        icon="lucide:layers"
        breadcrumbCurrent="Modifier"
        :breadcrumb-extras="[['label' => 'Classes', 'url' => route('admin.classrooms.index')]]"
        :back-url="route('admin.classrooms.index')"
        back-label="Liste des classes"
    >
        <x-admin.form-card>
            <form method="POST" action="{{ route('admin.classrooms.update', $classroom) }}">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Ecole</label>
                    <input type="text" value="{{ $classroom->school?->name ?? '—' }}"
                           class="admin-input !bg-zinc-100 !border-zinc-200"
                           readonly>
                </div>

                <div class="space-y-2">
                    <label for="name" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        Nom de la classe <span class="text-rose-600">*</span>
                    </label>
                    <input id="name" name="name" type="text" value="{{ old('name', $classroom->name) }}" required
                           class="admin-input"
                           placeholder="Ex: 6eme A">
                    @error('name')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <label for="filiaire_id" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        Filiere / option <span class="text-rose-600">*</span>
                    </label>
                    <select id="filiaire_id" name="filiaire_id" required class="admin-select">
                        <option value="">Selectionner</option>
                        @foreach($filiaires as $filiaire)
                            <option value="{{ $filiaire->id }}" @selected(old('filiaire_id', $classroom->filiaire_id) == $filiaire->id)>
                                {{ $filiaire->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('filiaire_id')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <label for="indicator" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Indicateur</label>
                    <input id="indicator" name="indicator" type="text" value="{{ old('indicator', $classroom->indicator) }}"
                           class="admin-input"
                           placeholder="Ex: 6A">
                    @error('indicator')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                @if(isset($academicLevels) && $academicLevels->isNotEmpty())
                    <div class="space-y-2">
                        <label for="academic_level_id" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                            Niveau academique
                        </label>
                        <select id="academic_level_id" name="academic_level_id"
                                class="admin-select">
                            <option value="">— Aucun —</option>
                            @foreach($academicLevels as $level)
                                <option value="{{ $level->id }}" @selected(old('academic_level_id', $classroom->academic_level_id) == $level->id)>
                                    {{ $level->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_level_id')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endif

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
