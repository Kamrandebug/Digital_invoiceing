@php
    $colCount = $activeColumns->count();
    $firstTotalIdx = $activeColumns->search(fn($c) => (int)($c->IsTotal ?? 0) === 1);
    if ($firstTotalIdx === false) {
        $firstTotalIdx = $colCount;
    }
@endphp

@foreach ($nodes as $nIdx => $node)
    @php
        $groupId   = ($parentGroupId ?? 'grp') . '_' . $node['level'] . '_' . $nIdx;
        $indentPx  = max(0, ($node['level'] - 1) * 20);
        $isLevel1  = $node['level'] === 1;
    @endphp

    {{-- Group Header Row --}}
    <tr class="report-group-header {{ $isLevel1 ? 'table-secondary' : 'table-light' }}"
        data-group-id="{{ $groupId }}"
        data-parent-group="{{ $parentGroupId ?? '' }}"
        style="cursor: pointer;"
        onclick="toggleReportGroup('{{ $groupId }}')">
        <td colspan="{{ $colCount }}" class="fw-semibold py-1" style="padding-left: {{ 15 + $indentPx }}px !important;">
            <i class="ri-arrow-down-s-line me-1 group-toggle-icon" id="icon_{{ $groupId }}"></i>
            <span class="badge {{ $isLevel1 ? 'bg-primary-transparent text-primary' : 'bg-secondary-transparent text-secondary' }} me-1">
                {{ $node['columnLabel'] }}
            </span>
            <span class="text-dark">{{ $node['value'] }}</span>
            <span class="text-muted small ms-1">({{ $node['count'] }} {{ \Illuminate\Support\Str::plural('line', $node['count']) }})</span>
        </td>
    </tr>

    {{-- Nested Child Groups OR Detail Rows --}}
    @if (!empty($node['children']))
        @include('pages.dashboards.Reports.partials.group-nodes', [
            'nodes'         => $node['children'],
            'activeColumns' => $activeColumns,
            'parentGroupId' => $groupId,
        ])
    @else
        @foreach ($node['rows'] as $r)
            <tr class="report-detail-row" data-parent-group="{{ $groupId }}">
                @foreach ($activeColumns as $col)
                    @php
                        $alignClass = $col->EffectiveAlign === 'right' ? 'text-end' : ($col->EffectiveAlign === 'center' ? 'text-center' : 'text-start');
                        $rawVal     = $r->{$col->ColumName} ?? null;
                        $formatted  = \App\Models\ReportModel::formatValue($rawVal, $col->EffectiveFormat);
                        $minW       = $col->EffectiveWidthMin ?? null;
                        $maxW       = $col->EffectiveWidthMax ?? null;
                        $styleParts = [];
                        if ($minW) $styleParts[] = 'min-width: ' . (int)$minW . 'px;';
                        if ($maxW) $styleParts[] = 'max-width: ' . (int)$maxW . 'px; overflow: hidden; text-overflow: ellipsis;';
                    @endphp
                    <td class="{{ $alignClass }}" @if(!empty($styleParts)) style="{{ implode(' ', $styleParts) }}" @endif title="{{ $formatted }}">{{ $formatted }}</td>
                @endforeach
            </tr>
        @endforeach
    @endif

    {{-- Group Subtotal Row --}}
    <tr class="report-subtotal-row {{ $isLevel1 ? 'table-light' : '' }}" data-parent-group="{{ $groupId }}">
        @if ($firstTotalIdx > 0)
            <td colspan="{{ $firstTotalIdx }}" class="text-end fw-semibold bg-light" style="padding-left: {{ 15 + $indentPx }}px !important;">
                {{ $isLevel1 ? 'Total' : 'Subtotal' }} — {{ $node['columnLabel'] }}: {{ $node['value'] }} ({{ $node['count'] }})
            </td>
        @endif
        @for ($i = $firstTotalIdx; $i < $colCount; $i++)
            @php
                $col = $activeColumns[$i];
                $alignClass = $col->EffectiveAlign === 'right' ? 'text-end' : ($col->EffectiveAlign === 'center' ? 'text-center' : 'text-start');
            @endphp
            @if ((int) ($col->IsTotal ?? 0) === 1)
                <td class="{{ $alignClass }} fw-semibold bg-light">
                    {{ \App\Models\ReportModel::formatValue($node['totals'][$col->ColumName] ?? 0, $col->EffectiveFormat) }}
                </td>
            @else
                <td class="bg-light"></td>
            @endif
        @endfor
    </tr>
@endforeach

