@php
    /** @var array $matrix */
    /** @var array $columns */
    /** @var array $regimes */
    $readonly = $readonly ?? false;
    $prefix = $prefix ?? 'data';
@endphp

<div class="overflow-x-auto" x-data="collecteSimpleMatrix(@js($matrix), @js(array_keys($columns)), @js(array_keys($regimes)))">
    <table class="min-w-full border-collapse text-sm">
        <thead>
            <tr class="bg-zinc-50">
                <th class="border border-zinc-200 px-2 py-2 text-left">Régime</th>
                @foreach($columns as $colKey => $colLabel)
                    <th class="border border-zinc-200 px-2 py-2 text-center whitespace-nowrap">{{ $colLabel }}</th>
                @endforeach
                <th class="border border-zinc-200 px-2 py-2 text-center bg-zinc-100">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($regimes as $regimeKey => $regimeLabel)
                <tr>
                    <td class="border border-zinc-200 px-2 py-1.5 font-medium">{{ $regimeLabel }}</td>
                    @foreach($columns as $colKey => $colLabel)
                        <td class="border border-zinc-200 px-1 py-1">
                            <input
                                type="number"
                                min="0"
                                step="1"
                                name="{{ $prefix }}[{{ $regimeKey }}][{{ $colKey }}]"
                                x-model.number="matrix['{{ $regimeKey }}']['{{ $colKey }}']"
                                @disabled($readonly)
                                class="w-20 rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-center text-sm shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900/10 disabled:bg-zinc-50"
                            >
                        </td>
                    @endforeach
                    <td class="border border-zinc-200 px-2 py-1 text-center font-semibold bg-zinc-50" x-text="rowTotal('{{ $regimeKey }}')"></td>
                </tr>
            @endforeach
            <tr class="bg-zinc-50 font-semibold">
                <td class="border border-zinc-200 px-2 py-2">TOTAL</td>
                <template x-for="col in columns" :key="col">
                    <td class="border border-zinc-200 px-2 py-2 text-center" x-text="colTotal(col)"></td>
                </template>
                <td class="border border-zinc-200 px-2 py-2 text-center bg-zinc-100" x-text="grandTotal()"></td>
            </tr>
        </tbody>
    </table>
</div>
