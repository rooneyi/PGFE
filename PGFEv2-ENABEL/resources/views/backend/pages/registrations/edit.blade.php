@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Modifier l'inscription"
        subtitle="Mise à jour du rattachement élève / classe."
        icon="lucide:user-pen"
        breadcrumbCurrent="Modifier"
        :breadcrumb-extras="[['label' => 'Inscriptions', 'url' => route('admin.registrations.index')]]"
        :back-url="route('admin.registrations.index')"
        back-label="Liste des inscriptions"
    >
        <x-admin.form-card>
            <form method="POST" action="{{ route('admin.registrations.update', $registration) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <x-admin.label for="student_id" :required="true">Élève</x-admin.label>
                        <x-admin.select id="student_id" name="student_id" required>
                            @foreach($students as $s)
                                @php
                                    $label = trim(($s->lastname ?? '').' '.($s->name ?? '').' '.($s->firstname ?? ''));
                                    if ($s->matricule) {
                                        $label = $s->matricule.' — '.$label;
                                    }
                                @endphp
                                <option value="{{ $s->id }}" @selected(old('student_id', $registration->student_id) == $s->id)>{{ $label ?: 'Élève #'.$s->id }}</option>
                            @endforeach
                        </x-admin.select>
                        @error('student_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <x-admin.label for="classroom_id" :required="true">Classe</x-admin.label>
                        <x-admin.select id="classroom_id" name="classroom_id" required>
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}" @selected(old('classroom_id', $registration->classroom_id) == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </x-admin.select>
                        @error('classroom_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <x-admin.label for="school_year_id" :required="true">Année scolaire</x-admin.label>
                        <x-admin.select id="school_year_id" name="school_year_id" required>
                            @foreach($schoolYears as $y)
                                <option value="{{ $y->id }}" @selected(old('school_year_id', $registration->school_year_id) == $y->id)>{{ $y->name ?? 'Année #'.$y->id }}</option>
                            @endforeach
                        </x-admin.select>
                        @error('school_year_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <x-admin.label for="academic_personal_id" :required="true">Personnel référent</x-admin.label>
                        <x-admin.select id="academic_personal_id" name="academic_personal_id" required>
                            @foreach($personnels as $p)
                                @php $n = trim(($p->pre_name ?? '').' '.($p->post_name ?? '').' '.($p->name ?? '')); @endphp
                                <option value="{{ $p->id }}" @selected(old('academic_personal_id', $registration->academic_personal_id) == $p->id)>{{ $n ?: 'Personnel #'.$p->id }}</option>
                            @endforeach
                        </x-admin.select>
                        @error('academic_personal_id')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <x-admin.label for="registration_date" :required="true">Date d'inscription</x-admin.label>
                        <x-admin.input type="date" id="registration_date" name="registration_date"
                                       value="{{ old('registration_date', $registration->registration_date?->format('Y-m-d')) }}" required />
                        @error('registration_date')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center gap-2 pt-8">
                        <input type="hidden" name="registration_status" value="0">
                        <input type="checkbox" id="registration_status" name="registration_status" value="1"
                               @checked(old('registration_status', $registration->registration_status))
                               class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                        <x-admin.label for="registration_status">Inscription active</x-admin.label>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <x-admin.label for="note">Note</x-admin.label>
                        <x-admin.textarea id="note" name="note" rows="3">{{ old('note', $registration->note) }}</x-admin.textarea>
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
