@props([
    'action',
    'classrooms',
    'courses',
    'schoolYears',
    'classroomId' => 0,
    'courseId' => 0,
    'schoolYearId' => null,
    'requireClassCourse' => true,
])

<div class="admin-data-card mb-6 p-4 md:p-6 space-y-4">
    <form method="GET" action="{{ $action }}" id="students-class-filters-form">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-2">
                <x-admin.label for="school_year_id">Année scolaire</x-admin.label>
                <x-admin.select id="school_year_id" name="school_year_id">
                    <option value="">— Année —</option>
                    @foreach($schoolYears as $y)
                        <option value="{{ $y->id }}" @selected((int) $schoolYearId === (int) $y->id)>
                            {{ $y->name }}{{ $y->is_active ? ' (active)' : '' }}
                        </option>
                    @endforeach
                </x-admin.select>
            </div>
            <div class="space-y-2">
                <x-admin.label for="classroom_id">
                    Classe @if ($requireClassCourse)<span class="text-rose-600">*</span>@endif
                </x-admin.label>
                <x-admin.select id="classroom_id" name="classroom_id">
                    <option value="">— Classe —</option>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}" @selected((int) $classroomId === (int) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </x-admin.select>
            </div>
            <div class="space-y-2">
                <x-admin.label for="course_id">
                    Cours @if ($requireClassCourse)<span class="text-rose-600">*</span>@endif
                </x-admin.label>
                <x-admin.select id="course_id" name="course_id" :disabled="$classroomId <= 0">
                    <option value="">— Cours —</option>
                    @foreach($courses as $co)
                        <option value="{{ $co->id }}" @selected((int) $courseId === (int) $co->id)>
                            {{ $co->label ?? $co->name }}
                        </option>
                    @endforeach
                </x-admin.select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="admin-btn-primary w-full">Afficher</button>
            </div>
        </div>
    </form>

    @if($classroomId <= 0)
        <p class="text-xs text-zinc-500">
            Choisissez une classe puis cliquez sur <strong>Afficher</strong> pour charger la liste des cours.
        </p>
    @endif

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4">
            {{ $actions }}
        </div>
    @endisset
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('students-class-filters-form');
                const classroom = document.getElementById('classroom_id');
                const course = document.getElementById('course_id');
                if (!form || !classroom || !course) return;

                classroom.addEventListener('change', function () {
                    course.value = '';
                    course.disabled = !classroom.value;
                    if (classroom.value) {
                        form.submit();
                    }
                });

                course.disabled = !classroom.value;
            });
        </script>
    @endpush
@endonce
