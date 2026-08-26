@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Nouvelle inscription"
        subtitle="Rattacher un élève à une classe pour l'année scolaire."
        icon="lucide:user-plus"
        breadcrumbCurrent="Créer"
        :breadcrumb-extras="[['label' => 'Inscriptions', 'url' => route('admin.registrations.index')]]"
        :back-url="route('admin.registrations.index')"
        back-label="Liste des inscriptions"
    >
        <x-admin.form-card>
            <form method="POST" action="{{ route('admin.registrations.store') }}" class="space-y-6">
                @csrf

                <p class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-xs text-zinc-600">
                    L'école, la filière, le cycle et le niveau sont déduits automatiquement de la classe choisie.
                </p>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <x-admin.label for="student_id" :required="true">Élève</x-admin.label>
                        <x-admin.select id="student_id" name="student_id" required>
                            <option value="">Sélectionner un élève</option>
                            @foreach($students as $s)
                                @php
                                    $label = trim(($s->lastname ?? '').' '.($s->name ?? '').' '.($s->firstname ?? ''));
                                    if ($s->matricule) {
                                        $label = $s->matricule.' — '.$label;
                                    }
                                @endphp
                                <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>{{ $label ?: 'Élève #'.$s->id }}</option>
                            @endforeach
                        </x-admin.select>
                        @error('student_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <x-admin.label for="classroom_id" :required="true">Classe</x-admin.label>
                        <x-admin.select id="classroom_id" name="classroom_id" required>
                            <option value="">Sélectionner une classe</option>
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}" @selected(old('classroom_id') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </x-admin.select>
                        @error('classroom_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <x-admin.label for="school_year_id">Année scolaire</x-admin.label>
                        <x-admin.select id="school_year_id" name="school_year_id">
                            <option value="">Année active (automatique)</option>
                            @foreach($schoolYears as $y)
                                <option value="{{ $y->id }}" @selected(old('school_year_id') == $y->id)>{{ $y->name ?? 'Année #'.$y->id }}</option>
                            @endforeach
                        </x-admin.select>
                        @error('school_year_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <x-admin.label for="academic_personal_id" :required="true">Personnel référent</x-admin.label>
                        <x-admin.select id="academic_personal_id" name="academic_personal_id" required>
                            <option value="">Sélectionner</option>
                            @foreach($personnels as $p)
                                @php $n = trim(($p->pre_name ?? '').' '.($p->post_name ?? '').' '.($p->name ?? '')); @endphp
                                <option value="{{ $p->id }}" @selected(old('academic_personal_id') == $p->id)>{{ $n ?: 'Personnel #'.$p->id }}</option>
                            @endforeach
                        </x-admin.select>
                        @error('academic_personal_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <x-admin.label for="registration_date" :required="true">Date d'inscription</x-admin.label>
                        <x-admin.input type="date" id="registration_date" name="registration_date"
                                       value="{{ old('registration_date', date('Y-m-d')) }}" required />
                        @error('registration_date')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center gap-2 pt-8">
                        <input type="hidden" name="registration_status" value="0">
                        <input type="checkbox" id="registration_status" name="registration_status" value="1"
                               @checked(old('registration_status', true))
                               class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                        <x-admin.label for="registration_status">Inscription active</x-admin.label>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <x-admin.label for="note">Note</x-admin.label>
                        <x-admin.textarea id="note" name="note" rows="3">{{ old('note') }}</x-admin.textarea>
                        @error('note')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-zinc-200 pt-5">
                    <a href="{{ route('admin.registrations.index') }}" class="admin-btn-secondary">Annuler</a>
                    <button type="submit" class="admin-btn-primary">
                        <iconify-icon icon="lucide:save" width="16"></iconify-icon>
                        Enregistrer
                    </button>
                </div>
            </form>
        </x-admin.form-card>
    </x-admin.shadcn-shell>
@endsection
