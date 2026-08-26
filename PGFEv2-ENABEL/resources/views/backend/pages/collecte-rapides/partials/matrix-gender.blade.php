@php
    $readonly = $readonly ?? false;
    $prefix = $prefix ?? 'data';
    $sexKeys = $sexKeys ?? ['G', 'F'];
@endphp

<div class="overflow-x-auto" x-data="collecteGenderMatrix(@js($matrix), @js(array_keys($columns)), @js(array_keys($regimes)), @js($sexKeys))">
    <table class="min-w-full border-collapse text-sm">
        <thead>
            <tr class="bg-zinc-50">
                <th class="border border-zinc-200 px-2 py-2 text-left" rowspan="2">Régime</th>
                @foreach($columns as $colKey => $colLabel)
                    <th class="border border-zinc-200 px-2 py-2 text-center" colspan="{{ count($sexKeys) }}">{{ $colLabel }}</th>
                @endforeach
                <th class="border border-zinc-200 px-2 py-2 text-center bg-zinc-100" colspan="{{ count($sexKeys) + 1 }}">TOTAL</th>
            </tr>
            <tr class="bg-zinc-50">
                @foreach($columns as $colKey => $colLabel)
                    @foreach($sexKeys as $sex)
                        <th class="border border-zinc-200 px-1 py-1 text-center text-xs">{{ $sex }}</th>
                    @endforeach
                @endforeach
                @foreach($sexKeys as $sex)
                    <th class="border border-zinc-200 px-1 py-1 text-center text-xs bg-zinc-100">{{ $sex }}</th>
                @endforeach
                <th class="border border-zinc-200 px-1 py-1 text-center text-xs bg-zinc-100">Σ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($regimes as $regimeKey => $regimeLabel)
                <tr>
                    <td class="border border-zinc-200 px-2 py-1.5 font-medium">{{ $regimeLabel }}</td>
                    @foreach($columns as $colKey => $colLabel)
                        @foreach($sexKeys as $sex)
                            <td class="border border-zinc-200 px-0.5 py-1">
                                <input
                                    type="number"
                                    min="0"
                                    step="1"
                                    name="{{ $prefix }}[{{ $regimeKey }}][{{ $colKey }}][{{ $sex }}]"
                                    x-model.number="matrix['{{ $regimeKey }}']['{{ $colKey }}']['{{ $sex }}']"
                                    @disabled($readonly)
                                    class="w-14 rounded-md border border-zinc-200 bg-white px-1 py-1.5 text-center text-sm shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900/10 disabled:bg-zinc-50"
                                >
                            </td>
                        @endforeach
                    @endforeach
                    @foreach($sexKeys as $sex)
                        <td class="border border-zinc-200 px-1 py-1 text-center bg-zinc-50 font-medium" x-text="rowSexTotal('{{ $regimeKey }}', '{{ $sex }}')"></td>
                    @endforeach
                    <td class="border border-zinc-200 px-1 py-1 text-center bg-zinc-100 font-semibold" x-text="rowGrand('{{ $regimeKey }}')"></td>
                </tr>
            @endforeach
            <tr class="bg-zinc-50 font-semibold">
                <td class="border border-zinc-200 px-2 py-2">TOTAL</td>
                @foreach($columns as $colKey => $colLabel)
                    @foreach($sexKeys as $sex)
                        <td class="border border-zinc-200 px-1 py-2 text-center" x-text="colSexTotal('{{ $colKey }}', '{{ $sex }}')"></td>
                    @endforeach
                @endforeach
                @foreach($sexKeys as $sex)
                    <td class="border border-zinc-200 px-1 py-2 text-center bg-zinc-100" x-text="allSexTotal('{{ $sex }}')"></td>
                @endforeach
                <td class="border border-zinc-200 px-1 py-2 text-center bg-zinc-100" x-text="grandTotal()"></td>
            </tr>
        </tbody>
    </table>
</div>
