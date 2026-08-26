@props([
    'emptyMessage' => 'Aucun enregistrement.',
    'colspan' => 3,
])

<div class="admin-data-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="border-b border-zinc-200 bg-zinc-50/50">
                    {{ $head }}
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                {{ $body }}
            </tbody>
        </table>
    </div>
    @isset($pagination)
        <div class="border-t border-zinc-100 bg-zinc-50/30 px-6 py-4">
            {{ $pagination }}
        </div>
    @endisset
</div>
