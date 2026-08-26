@extends('backend.layouts.app')

@section('admin-content')
    <x-admin.shadcn-shell
        title="Modifier la filiere"
        subtitle="Mise a jour de {{ $filiaire->name }}."
        icon="lucide:git-branch"
        breadcrumbCurrent="Modifier une filiere"
    >
        <x-slot:actions>
            <a href="{{ route('admin.filiaires.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider text-zinc-700 hover:bg-zinc-50">
                <iconify-icon icon="lucide:arrow-left" width="16"></iconify-icon>
                Retour
            </a>
        </x-slot:actions>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <form method="POST" action="{{ route('admin.filiaires.update', $filiaire) }}" class="space-y-6 p-6 md:p-8">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <label for="name" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        Nom <span class="text-rose-600">*</span>
                    </label>
                    <input id="name" name="name" type="text" value="{{ old('name', $filiaire->name) }}" required
                           class="admin-input"
                           placeholder="Ex: Scientifique">
                    @error('name')<p class="text-xs italic font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end border-t border-zinc-100 pt-5">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white hover:bg-black">
                        <iconify-icon icon="lucide:save" width="16"></iconify-icon>
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </x-admin.shadcn-shell>
@endsection
