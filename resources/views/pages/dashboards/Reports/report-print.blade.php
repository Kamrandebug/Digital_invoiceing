@php
    $displayReportName = trim((string) ($report->CustomName ?? '')) !== '' ? $report->CustomName : ($report->ReportName ?: 'Report');
    $orientation       = strtolower($report->PrintOrientation ?? 'Landscape') === 'portrait' ? 'portrait' : 'landscape';
    $colCount          = $activeColumns->count();
    $firstTotalIdx     = $activeColumns->search(fn($c) => (int)($c->IsTotal ?? 0) === 1);
    if ($firstTotalIdx === false) {
        $firstTotalIdx = $colCount;
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $displayReportName }} — Print</title>
    <link href="{{ asset('build/assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('build/assets/themecss/custom.css') }}" rel="stylesheet">
    <style>
        @page { size: A4 {{ $orientation }}; margin: 10mm; }
        body { background: #fff !important; color: #000 !important; padding: 12px; }
        .listing-table th, .listing-table td { font-size: 10px !important; padding: 4px 6px !important; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
        <div>
            <h5 class="fw-bold mb-0">{{ $displayReportName }}</h5>
            <small class="text-muted">
                Printed: {{ $printed_at->format('Y-m-d H:i') }}
                @if (!empty($filters['start_date']) || !empty($filters['end_date']))
                    | Date Range: {{ $filters['start_date'] ?: 'Start' }} to {{ $filters['end_date'] ?: 'End' }}
                @endif
                @if (!empty($groupCols))
                    | Grouped By:
                    @foreach ($groupCols as $gc)
                        {{ $colLabelMap[$gc] ?? $gc }}{{ !$loop->last ? ' > ' : '' }}
                    @endforeach
                @endif
            </small>
        </div>
        <div class="no-print d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">Print / Save PDF</button>
            <button type="button" class="btn btn-sm btn-light border" onclick="window.close()">Close</button>
        </div>
    </div>

    <table class="table table-bordered table-sm listing-table mb-0">
        <thead>
            <tr>
                @foreach ($activeColumns as $col)
                    @php
                        $alignClass = $col->EffectiveAlign === 'right' ? 'text-end' : ($col->EffectiveAlign === 'center' ? 'text-center' : 'text-start');
                        $minW = $col->EffectiveWidthMin ?? null;
                        $maxW = $col->EffectiveWidthMax ?? null;
                        $styleParts = [];
                        if ($minW) $styleParts[] = 'min-width: ' . (int)$minW . 'px;';
                        if ($maxW) $styleParts[] = 'max-width: ' . (int)$maxW . 'px;';
                    @endphp
                    <th class="{{ $alignClass }}" @if(!empty($styleParts)) style="{{ implode(' ', $styleParts) }}" @endif>{{ $col->EffectiveLabel }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if ($rows->isEmpty())
                <tr>
                    <td colspan="{{ max(1, $colCount) }}" class="text-center text-muted py-3">No records found.</td>
                </tr>
            @elseif (!empty($groupCols) && !empty($groupTree))
                @include('pages.dashboards.Reports.partials.group-nodes', [
                    'nodes'         => $groupTree,
                    'activeColumns' => $activeColumns,
                    'parentGroupId' => 'print_root',
                ])
            @else
                @foreach ($rows as $r)
                    <tr>
                        @foreach ($activeColumns as $col)
                            @php
                                $alignClass = $col->EffectiveAlign === 'right' ? 'text-end' : ($col->EffectiveAlign === 'center' ? 'text-center' : 'text-start');
                                $formatted  = \App\Models\ReportModel::formatValue($r->{$col->ColumName} ?? null, $col->EffectiveFormat);
                                $minW       = $col->EffectiveWidthMin ?? null;
                                $maxW       = $col->EffectiveWidthMax ?? null;
                                $styleParts = [];
                                if ($minW) $styleParts[] = 'min-width: ' . (int)$minW . 'px;';
                                if ($maxW) $styleParts[] = 'max-width: ' . (int)$maxW . 'px; overflow: hidden; text-overflow: ellipsis;';
                            @endphp
                            <td class="{{ $alignClass }}" @if(!empty($styleParts)) style="{{ implode(' ', $styleParts) }}" @endif>{{ $formatted }}</td>
                        @endforeach
                    </tr>
                @endforeach
            @endif
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr class="table-light fw-bold">
                    @if ($firstTotalIdx > 0)
                        <td colspan="{{ $firstTotalIdx }}" class="text-end fw-bold">
                            Overall Grand Total ({{ (int) ($grandTotals['__count'] ?? 0) }} records)
                        </td>
                    @endif
                    @for ($i = $firstTotalIdx; $i < $colCount; $i++)
                        @php
                            $col = $activeColumns[$i];
                            $alignClass = $col->EffectiveAlign === 'right' ? 'text-end' : ($col->EffectiveAlign === 'center' ? 'text-center' : 'text-start');
                        @endphp
                        @if ((int) ($col->IsTotal ?? 0) === 1)
                            <td class="{{ $alignClass }} fw-bold">
                                {{ \App\Models\ReportModel::formatValue($grandTotals[$col->ColumName] ?? 0, $col->EffectiveFormat) }}
                            </td>
                        @else
                            <td></td>
                        @endif
                    @endfor
                </tr>
            </tfoot>
        @endif
    </table>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 250);
        });
    </script>
</body>
</html>

