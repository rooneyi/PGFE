@php
    $kind = $kind ?? null;
    $sexKeys = $sexKeys ?? ['G', 'F'];
@endphp

@if($kind === 'simple_matrix')
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
            <thead>
                <tr class="bg-zinc-50">
                    <th class="border border-zinc-200 px-2 py-1 text-left">Régime</th>
                    @foreach($columns as $colKey => $colLabel)
                        <th class="border border-zinc-200 px-2 py-1 text-center">{{ $colLabel }}</th>
                    @endforeach
                    <th class="border border-zinc-200 px-2 py-1 text-center bg-zinc-100">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($regimes as $regimeKey => $regimeLabel)
                    @php
                        $rowSum = 0;
                        foreach (array_keys($columns) as $col) {
                            $rowSum += (int) ($matrix[$regimeKey][$col] ?? 0);
                        }
                    @endphp
                    <tr>
                        <td class="border border-zinc-200 px-2 py-1">{{ $regimeLabel }}</td>
                        @foreach($columns as $colKey => $colLabel)
                            <td class="border border-zinc-200 px-2 py-1 text-center">{{ (int) ($matrix[$regimeKey][$colKey] ?? 0) }}</td>
                        @endforeach
                        <td class="border border-zinc-200 px-2 py-1 text-center font-semibold bg-zinc-50">{{ $rowSum }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif($kind === 'gender_matrix')
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
            <thead>
                <tr class="bg-zinc-50">
                    <th class="border border-zinc-200 px-2 py-1 text-left" rowspan="2">Régime</th>
                    @foreach($columns as $colKey => $colLabel)
                        <th class="border border-zinc-200 px-2 py-1 text-center" colspan="{{ count($sexKeys) }}">{{ $colLabel }}</th>
                    @endforeach
                </tr>
                <tr class="bg-zinc-50">
                    @foreach($columns as $colKey => $colLabel)
                        @foreach($sexKeys as $sex)
                            <th class="border border-zinc-200 px-1 py-1 text-center text-xs">{{ $sex }}</th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($regimes as $regimeKey => $regimeLabel)
                    <tr>
                        <td class="border border-zinc-200 px-2 py-1">{{ $regimeLabel }}</td>
                        @foreach($columns as $colKey => $colLabel)
                            @foreach($sexKeys as $sex)
                                <td class="border border-zinc-200 px-1 py-1 text-center">{{ (int) ($matrix[$regimeKey][$colKey][$sex] ?? 0) }}</td>
                            @endforeach
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif($kind === 'type_matrix')
    <div class="space-y-3">
        @foreach($regimes as $regimeKey => $regimeLabel)
            <div>
                <p class="mb-1 text-xs font-semibold text-zinc-500">{{ $regimeLabel }}</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead>
                            <tr class="bg-zinc-50">
                                <th class="border border-zinc-200 px-2 py-1 text-left">Type</th>
                                @foreach($columns as $colKey => $colLabel)
                                    <th class="border border-zinc-200 px-2 py-1 text-center" colspan="2">{{ $colLabel }}</th>
                                @endforeach
                            </tr>
                            <tr class="bg-zinc-50">
                                <th class="border border-zinc-200"></th>
                                @foreach($columns as $colKey => $colLabel)
                                    <th class="border border-zinc-200 px-1 py-1 text-xs text-center">G</th>
                                    <th class="border border-zinc-200 px-1 py-1 text-xs text-center">F</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teachingTypes as $typeKey => $typeLabel)
                                <tr>
                                    <td class="border border-zinc-200 px-2 py-1">{{ $typeLabel }}</td>
                                    @foreach($columns as $colKey => $colLabel)
                                        @foreach(['G', 'F'] as $sex)
                                            <td class="border border-zinc-200 px-1 py-1 text-center">{{ (int) ($matrix[$regimeKey][$typeKey][$colKey][$sex] ?? 0) }}</td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endif
