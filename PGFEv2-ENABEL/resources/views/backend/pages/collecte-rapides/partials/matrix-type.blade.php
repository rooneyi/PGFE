@php
    $readonly = $readonly ?? false;
    $prefix = $prefix ?? 'data';
@endphp

<div class="space-y-6" x-data="collecteTypeMatrix(@js($matrix), @js(array_keys($columns)), @js(array_keys($regimes)), @js(array_keys($teachingTypes)))">
    @foreach($regimes as $regimeKey => $regimeLabel)
        <div class="rounded-lg border border-zinc-200">
            <div class="border-b bg-zinc-50 px-3 py-2 text-sm font-semibold">{{ $regimeLabel }}</div>
            <div class="overflow-x-auto p-2">
                <table class="min-w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="border border-zinc-200 px-2 py-2 text-left">Type</th>
                            @foreach($columns as $colKey => $colLabel)
                                <th class="border border-zinc-200 px-2 py-2 text-center" colspan="2">{{ $colLabel }}</th>
                            @endforeach
                            <th class="border border-zinc-200 px-2 py-2 text-center bg-zinc-100" colspan="3">TOTAL</th>
                        </tr>
                        <tr class="bg-zinc-50">
                            <th class="border border-zinc-200"></th>
                            @foreach($columns as $colKey => $colLabel)
                                <th class="border border-zinc-200 px-1 py-1 text-xs text-center">G</th>
                                <th class="border border-zinc-200 px-1 py-1 text-xs text-center">F</th>
                            @endforeach
                            <th class="border border-zinc-200 px-1 py-1 text-xs text-center bg-zinc-100">G</th>
                            <th class="border border-zinc-200 px-1 py-1 text-xs text-center bg-zinc-100">F</th>
                            <th class="border border-zinc-200 px-1 py-1 text-xs text-center bg-zinc-100">Σ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teachingTypes as $typeKey => $typeLabel)
                            <tr>
                                <td class="border border-zinc-200 px-2 py-1.5 font-medium whitespace-nowrap">{{ $typeLabel }}</td>
                                @foreach($columns as $colKey => $colLabel)
                                    @foreach(['G', 'F'] as $sex)
                                        <td class="border border-zinc-200 px-0.5 py-1">
                                            <input
                                                type="number"
                                                min="0"
                                                step="1"
                                                name="{{ $prefix }}[{{ $regimeKey }}][{{ $typeKey }}][{{ $colKey }}][{{ $sex }}]"
                                                x-model.number="matrix['{{ $regimeKey }}']['{{ $typeKey }}']['{{ $colKey }}']['{{ $sex }}']"
                                                @disabled($readonly)
                                                class="w-14 rounded-md border border-zinc-200 bg-white px-1 py-1.5 text-center text-sm shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900/10 disabled:bg-zinc-50"
                                            >
                                        </td>
                                    @endforeach
                                @endforeach
                                <td class="border border-zinc-200 px-1 py-1 text-center bg-zinc-50 font-medium" x-text="typeRowSex('{{ $regimeKey }}', '{{ $typeKey }}', 'G')"></td>
                                <td class="border border-zinc-200 px-1 py-1 text-center bg-zinc-50 font-medium" x-text="typeRowSex('{{ $regimeKey }}', '{{ $typeKey }}', 'F')"></td>
                                <td class="border border-zinc-200 px-1 py-1 text-center bg-zinc-100 font-semibold" x-text="typeRowGrand('{{ $regimeKey }}', '{{ $typeKey }}')"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
