@extends('backend.layouts.app')

@section('admin-content')
    <div class="space-y-6">
        <div class="flex items-center justify-between bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-4">
                <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-cyan-500/20">
                    <iconify-icon icon="lucide:graduation-cap" width="32"></iconify-icon>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-800 dark:text-white">Modifier le niveau académique</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium tracking-tight">
                        Mise à jour de <span class="text-cyan-600">{{ $academicLevel->name }}</span>.
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.academic-levels.index') }}" class="h-12 px-6 flex items-center gap-2 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all font-black text-xs uppercase tracking-widest">
                <iconify-icon icon="lucide:arrow-left" width="18"></iconify-icon>
                RETOUR
            </a>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <form method="POST" action="{{ route('admin.academic-levels.update', $academicLevel) }}" class="p-10 space-y-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2 md:col-span-2">
                        <label for="cycle_id" class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Cycle <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                <iconify-icon icon="lucide:layers" width="18"></iconify-icon>
                            </span>
                            <select id="cycle_id" name="cycle_id" required
                                    class="w-full h-12 pl-12 pr-4 rounded-2xl bg-gray-50 dark:bg-gray-800 border-none font-bold text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-600 transition-all appearance-none">
                                <option value="">Sélectionner un cycle</option>
                                @foreach($cycles as $cycle)
                                    <option value="{{ $cycle->id }}" @selected(old('cycle_id', $academicLevel->cycle_id) == $cycle->id)>{{ $cycle->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('cycle_id')<p class="text-xs text-rose-500 font-bold px-1 italic">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label for="name" class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Nom du niveau <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                                <iconify-icon icon="lucide:tag" width="18"></iconify-icon>
                            </span>
                            <input id="name" name="name" type="text" value="{{ old('name', $academicLevel->name) }}" required placeholder="Ex: 7e année"
                                   class="w-full h-12 pl-12 rounded-2xl bg-gray-50 dark:bg-gray-800 border-none font-bold text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-600 transition-all">
                        </div>
                        @error('name')<p class="text-xs text-rose-500 font-bold px-1 italic">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-50 dark:border-gray-800 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center gap-3 rounded-2xl bg-cyan-600 px-10 py-4 text-sm font-black text-white hover:bg-cyan-700 shadow-xl shadow-cyan-600/20 transition-all hover:-translate-y-0.5">
                        <iconify-icon icon="lucide:save" width="20"></iconify-icon>
                        ENREGISTRER LES MODIFICATIONS
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
