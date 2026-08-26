@extends('backend.layouts.app')

@section('admin-content')
    @php
        $displayName = trim(($personnel->pre_name ?? '').' '.($personnel->post_name ?? '').' '.($personnel->name ?? ''));
        if ($displayName === '') {
            $displayName = 'Agent #'.$personnel->id;
        }
    @endphp

    <x-admin.shadcn-shell
        title="Modifier le personnel"
        :subtitle="'Fiche de '.$displayName"
        icon="lucide:id-card"
        breadcrumbCurrent="Édition"
    >
        <x-admin.personnel-operations-nav active="personnels" />

        <div class="admin-data-card p-6 md:p-8">
            <form action="{{ route('admin.personnels.update', $personnel) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <x-admin.label for="pre_name">Prénom</x-admin.label>
                        <x-admin.input id="pre_name" name="pre_name" value="{{ old('pre_name', $personnel->pre_name) }}" />
                        @error('pre_name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <x-admin.label for="post_name">Post-nom</x-admin.label>
                        <x-admin.input id="post_name" name="post_name" value="{{ old('post_name', $personnel->post_name) }}" />
                        @error('post_name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <x-admin.label for="name">Nom de famille</x-admin.label>
                        <x-admin.input id="name" name="name" value="{{ old('name', $personnel->name) }}" />
                        @error('name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <x-admin.label for="email">Email de connexion</x-admin.label>
                        <x-admin.input type="email" id="email" name="email"
                                       value="{{ old('email', $personnel->user?->email ?? $personnel->email) }}" />
                        @error('email')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <x-admin.label for="school_id">École d'affectation</x-admin.label>
                        <x-admin.select id="school_id" name="school_id">
                            <option value="">— Non affecté à une école —</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" @selected(old('school_id', $personnel->school_id) == $school->id)>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </x-admin.select>
                        @error('school_id')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <x-admin.label for="password">Nouveau mot de passe</x-admin.label>
                        <x-admin.input type="password" id="password" name="password" autocomplete="new-password"
                                       placeholder="Laisser vide pour ne pas changer" />
                        @if(! $personnel->user)
                            <p class="text-xs text-amber-700">Aucun compte utilisateur lié à ce personnel.</p>
                        @endif
                        @error('password')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <x-admin.label for="password_confirmation">Confirmer</x-admin.label>
                        <x-admin.input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-200 pt-6">
                    <a href="{{ route('admin.personnels.index') }}" class="admin-btn-secondary">Annuler</a>
                    <button type="submit" class="admin-btn-primary">
                        <iconify-icon icon="lucide:save" width="16"></iconify-icon>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </x-admin.shadcn-shell>
@endsection
