@php
    $showField = isset($sousDivisions);
    $selectedId = old('sous_division_id', isset($school) ? $school->sous_division_id : null);
    $lockedSd = auth()->user()?->hasRole('admin-sous-division');
@endphp

@if($showField)
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium mb-1" for="sous_division_id">
            Sous-division
        </label>
        @if($lockedSd)
            <input type="hidden" name="sous_division_id" value="{{ auth()->user()->sous_division_id }}" />
            <p class="text-sm text-gray-600 dark:text-gray-300 py-2 px-3 rounded-md bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                {{ $sousDivisions->first()?->name ?? '—' }}
                @if($sousDivisions->first()?->code)
                    <span class="text-gray-400 font-mono text-xs">({{ $sousDivisions->first()->code }})</span>
                @endif
            </p>
        @elseif($sousDivisions->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-300 py-2 px-3 rounded-md bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                Aucune sous-division disponible. Vous pouvez enregistrer l’école sans en choisir une, ou en créer une dans
                <a href="{{ route('admin.sous-divisions.index') }}" class="font-semibold underline">Organisation → Sous-divisions</a>.
            </p>
        @else
            <select name="sous_division_id" id="sous_division_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                <option value="">— Sélectionner —</option>
                @foreach($sousDivisions as $sd)
                    <option value="{{ $sd->id }}" @selected((int) $selectedId === (int) $sd->id)>
                        {{ $sd->name }} ({{ $sd->code }})@if($sd->proved) — {{ $sd->proved->name }}@endif
                    </option>
                @endforeach
            </select>
        @endif
        @error('sous_division_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
@endif
