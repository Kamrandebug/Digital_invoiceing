@extends('layouts.master')

@section('content')
@php
    $displayReportName = trim((string) ($report->CustomName ?? '')) !== '' ? $report->CustomName : ($report->ReportName ?: 'Report');
    $queryParams       = request()->query();
    $exportUrl         = route('reports.export', array_merge(['reportId' => $reportId], $queryParams));
    $printUrl          = route('reports.print', array_merge(['reportId' => $reportId], $queryParams));
    $colCount          = $activeColumns->count();
    $firstTotalIdx     = $activeColumns->search(fn($c) => (int)($c->IsTotal ?? 0) === 1);
    if ($firstTotalIdx === false) {
        $firstTotalIdx = $colCount;
    }
    $selectedMultiLevel = $multiLevelCols[0] ?? '';
@endphp

<div class="container-fluid pt-2">
    {{-- Page Header & Action Toolbar --}}
    <div class="page-header-breadcrumb mb-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light border" title="Back to Reports Center">
                    <i class="ri-arrow-left-line"></i>
                </a>
                <div>
                    <h1 class="page-title fw-semibold fs-15 mb-0" id="headerReportTitle">{{ $displayReportName }}</h1>
                    @if ($displayReportName !== $report->ReportName)
                        <small class="text-muted">Original: {{ $report->ReportName }}</small>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-secondary-light" data-bs-toggle="offcanvas" data-bs-target="#reportSettingsDrawer" aria-controls="reportSettingsDrawer">
                    <i class="ri-settings-3-line me-1"></i> Report Settings
                </button>
                <a href="{{ $exportUrl }}" class="btn btn-sm btn-primary">
                    <i class="ri-file-excel-2-line me-1"></i> Export CSV
                </a>
                <button type="button" class="btn btn-sm btn-primary-light" id="reportPrintBtn">
                    <i class="ri-printer-line me-1"></i> Print / PDF
                </button>
            </div>
        </div>
    </div>

    {{-- Dynamic Filter & Grouping Card --}}
    <div class="card custom-card mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('reports.show', ['reportId' => $reportId]) }}" id="dynamicReportFilterForm">
                <input type="hidden" name="sort_by" id="sortByInput" value="{{ $sortBy ?? '' }}">
                <input type="hidden" name="sort_dir" id="sortDirInput" value="{{ $sortDir ?? 'asc' }}">

                {{-- Row 1: Dynamic Parameters from tbl_reports_parameters --}}
                <div class="row g-2 align-items-end">
                    @foreach ($parameters as $param)
                        @php
                            $pName  = $param->Parameter;
                            $pLabel = $param->CustomLabel ?: $pName;
                            $pVal   = $filters[$pName] ?? '';
                        @endphp
                        <div class="col-md-4 col-lg">
                            <label class="form-label mb-1">{{ $pLabel }}</label>
                            @if ($param->controlType === 'select')
                                <select name="{{ $pName }}" class="form-select form-select-sm">
                                    @if ($pName !== 'invoice_status')
                                        <option value="">All {{ strtolower($pLabel) }}s</option>
                                    @endif
                                    @foreach ($param->options as $opt)
                                        <option value="{{ $opt['id'] }}" @selected((string)$pVal === (string)$opt['id'])>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif ($param->controlType === 'date')
                                <input type="date" name="{{ $pName }}" class="form-control form-control-sm" value="{{ $pVal }}">
                            @else
                                <input type="text" name="{{ $pName }}" class="form-control form-control-sm" value="{{ $pVal }}" placeholder="{{ $pLabel }}">
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Row 2: Grouping, Multi-Level & Filter Action Buttons --}}
                <div class="row g-2 align-items-end pt-2 mt-2 border-top">
                    {{-- 1. Grouping Dropdown (Level 1) --}}
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label mb-1"><i class="ri-folder-chart-line me-1 text-primary"></i>Group By</label>
                        <select name="group_by" id="groupBySelect" class="form-select form-select-sm">
                            <option value="">— No Grouping —</option>
                            @foreach ($allColumns as $col)
                                @if ((int)($col->IsTotal ?? 0) === 0)
                                    <option value="{{ $col->ColumName }}" @selected($groupByCol === $col->ColumName)>
                                        {{ $col->EffectiveLabel }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    {{-- 2. Multi-Level Grouping Dropdown (Level 2) --}}
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label mb-1"><i class="ri-node-tree me-1 text-primary"></i>Multi-Level By</label>
                        <select name="multi_level" id="multiLevelSelect" class="form-select form-select-sm">
                            <option value="">— None —</option>
                            @foreach ($allColumns as $col)
                                @if ((int)($col->IsTotal ?? 0) === 0)
                                    <option value="{{ $col->ColumName }}"
                                        @selected($selectedMultiLevel === $col->ColumName)
                                        @disabled($groupByCol !== '' && $groupByCol === $col->ColumName)>
                                        {{ $col->EffectiveLabel }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    {{-- Apply / Clear Filter Actions --}}
                    <div class="col-md-4 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary px-4">
                            <i class="ri-filter-3-line me-1"></i>Apply
                        </button>
                        <a href="{{ route('reports.show', ['reportId' => $reportId]) }}" class="btn btn-sm btn-light border px-3">
                            Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Active Grouping Summary Pill Bar --}}
    @if (!empty($groupCols))
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 px-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small"><i class="ri-node-tree me-1"></i>Active Hierarchy:</span>
                @foreach ($groupCols as $gIdx => $gCol)
                    <span class="badge bg-primary-transparent text-primary">
                        Level {{ $gIdx + 1 }}: {{ $colLabelMap[$gCol] ?? $gCol }}
                    </span>
                    @if (!$loop->last)
                        <i class="ri-arrow-right-s-line text-muted"></i>
                    @endif
                @endforeach
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-light border py-0 px-2" onclick="expandAllReportGroups(true)">
                    <i class="ri-add-box-line me-1"></i>Expand All
                </button>
                <button type="button" class="btn btn-sm btn-light border py-0 px-2" onclick="expandAllReportGroups(false)">
                    <i class="ri-indeterminate-circle-line me-1"></i>Collapse All
                </button>
            </div>
        </div>
    @endif

    {{-- Main Dynamic Report Table --}}
    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap listing-table table-bordered table-hover mb-0" id="dynamicReportTable">
                    <thead>
                        <tr>
                            @foreach ($activeColumns as $col)
                                @php
                                    $alignClass = $col->EffectiveAlign === 'right' ? 'text-end' : ($col->EffectiveAlign === 'center' ? 'text-center' : 'text-start');
                                    $minW = $col->EffectiveWidthMin ?? null;
                                    $maxW = $col->EffectiveWidthMax ?? null;
                                    $styleParts = ['cursor: pointer;', 'user-select: none;'];
                                    if ($minW) $styleParts[] = 'min-width: ' . (int)$minW . 'px;';
                                    if ($maxW) $styleParts[] = 'max-width: ' . (int)$maxW . 'px;';

                                    $isSorted = ($sortBy ?? '') === $col->ColumName;
                                    $currentDir = strtolower($sortDir ?? 'asc');
                                    $sortIcon = 'ri-expand-up-down-line text-muted opacity-50';
                                    if ($isSorted) {
                                        $sortIcon = $currentDir === 'desc' ? 'ri-arrow-down-line text-primary fw-bold' : 'ri-arrow-up-line text-primary fw-bold';
                                    }
                                @endphp
                                <th class="{{ $alignClass }}"
                                    style="{{ implode(' ', $styleParts) }}"
                                    onclick="sortReportColumn('{{ $col->ColumName }}')"
                                    title="Click to sort by {{ $col->EffectiveLabel }}">
                                    <span>{{ $col->EffectiveLabel }}</span>
                                    <i class="{{ $sortIcon }} ms-1" style="font-size: 11px;"></i>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @if ($rows->isEmpty())
                            <tr>
                                <td colspan="{{ max(1, $colCount) }}" class="text-center text-muted py-4">
                                    No records match the selected filters.
                                </td>
                            </tr>
                        @elseif (!empty($groupCols) && !empty($groupTree))
                            @include('pages.dashboards.Reports.partials.group-nodes', [
                                'nodes'         => $groupTree,
                                'activeColumns' => $activeColumns,
                                'parentGroupId' => 'root',
                            ])
                        @else
                            @foreach ($rows as $r)
                                <tr>
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
                    </tbody>
                    @if ($rows->isNotEmpty())
                        <tfoot>
                            <tr class="table-light fw-bold">
                                @if ($firstTotalIdx > 0)
                                    <td colspan="{{ $firstTotalIdx }}" class="text-end fw-bold bg-light">
                                        Overall Grand Total ({{ (int) ($grandTotals['__count'] ?? 0) }} {{ \Illuminate\Support\Str::plural('record', (int) ($grandTotals['__count'] ?? 0)) }})
                                    </td>
                                @endif
                                @for ($i = $firstTotalIdx; $i < $colCount; $i++)
                                    @php
                                        $col = $activeColumns[$i];
                                        $alignClass = $col->EffectiveAlign === 'right' ? 'text-end' : ($col->EffectiveAlign === 'center' ? 'text-center' : 'text-start');
                                    @endphp
                                    @if ((int) ($col->IsTotal ?? 0) === 1)
                                        <td class="{{ $alignClass }} fw-bold bg-light">
                                            {{ \App\Models\ReportModel::formatValue($grandTotals[$col->ColumName] ?? 0, $col->EffectiveFormat) }}
                                        </td>
                                    @else
                                        <td class="bg-light"></td>
                                    @endif
                                @endfor
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     REPORT SETTINGS OFFCANVAS (Customize Report Name, Drag & Drop Columns, Reset)
══════════════════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="reportSettingsDrawer" aria-labelledby="reportSettingsDrawerLabel" style="width: 880px; max-width: 100vw; z-index: 1065;">
    <div class="offcanvas-header border-bottom">
        <div>
            <h6 id="reportSettingsDrawerLabel" class="offcanvas-title fw-semibold mb-0">
                <i class="ri-settings-3-line me-1 text-primary"></i>Report Settings &amp; Columns
            </h6>
            <small class="text-muted">Customize report title, drag &amp; drop column order, rename headers, or toggle visibility</small>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column justify-content-between p-3">
        <div>
            <div id="reportSettingsAlert" class="alert d-none py-2 mb-3"></div>

            {{-- Report Header Settings --}}
            @php
                $savedGroupCols      = array_values(array_filter(array_map('trim', explode(',', (string) ($report->RowGroupColumnName ?? '')))));
                $savedPrimaryGroup   = $savedGroupCols[0] ?? '';
                $savedSecondaryGroup = $savedGroupCols[1] ?? '';
            @endphp
            <div class="row g-2 mb-3 pb-3 border-bottom">
                <div class="col-md-3">
                    <label class="form-label mb-1">Report Display Name</label>
                    <input type="text" id="settingCustomReportName" class="form-control form-control-sm"
                           value="{{ $displayReportName }}" placeholder="Enter report display name">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Default Grouping</label>
                    <select id="settingDefaultGroupBy" class="form-select form-select-sm">
                        <option value="">— None —</option>
                        @foreach ($allColumns as $col)
                            @if ((int)($col->IsTotal ?? 0) === 0)
                                <option value="{{ $col->ColumName }}" @selected($savedPrimaryGroup === $col->ColumName)>
                                    {{ $col->EffectiveLabel }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Default Multi-Level</label>
                    <select id="settingDefaultMultiLevel" class="form-select form-select-sm">
                        <option value="">— None —</option>
                        @foreach ($allColumns as $col)
                            @if ((int)($col->IsTotal ?? 0) === 0)
                                <option value="{{ $col->ColumName }}"
                                    @selected($savedSecondaryGroup === $col->ColumName)
                                    @disabled($savedPrimaryGroup !== '' && $savedPrimaryGroup === $col->ColumName)>
                                    {{ $col->EffectiveLabel }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Print Orientation</label>
                    <select id="settingPrintOrientation" class="form-select form-select-sm">
                        <option value="Landscape" @selected(($report->PrintOrientation ?? 'Landscape') === 'Landscape')>Landscape</option>
                        <option value="Portrait" @selected(($report->PrintOrientation ?? '') === 'Portrait')>Portrait</option>
                    </select>
                </div>
            </div>

            {{-- Columns Drag & Drop Table --}}
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-semibold">
                    <i class="ri-drag-move-2-line me-1 text-muted"></i>Drag rows to reorder columns
                </span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light border py-0 px-2" onclick="toggleAllSettingColumns(true)">Check All</button>
                    <button type="button" class="btn btn-sm btn-light border py-0 px-2" onclick="toggleAllSettingColumns(false)">Uncheck All</button>
                </div>
            </div>

            <div class="table-responsive border rounded" style="max-height: calc(100vh - 310px); overflow-y: auto;">
                <table class="table text-nowrap listing-table mb-0" id="reportColumnsSettingsTable">
                    <thead class="sticky-top">
                        <tr>
                            <th style="width: 45px;">Order</th>
                            <th style="width: 55px;" class="text-center">On/Off</th>
                            <th style="width: 55px;" class="text-center" title="Include in Subtotals &amp; Grand Total">Total</th>
                            <th>Original Column</th>
                            <th>Custom Display Name</th>
                            <th style="width: 85px;">Min Width</th>
                            <th style="width: 85px;">Max Width</th>
                            <th style="width: 100px;">Alignment</th>
                        </tr>
                    </thead>
                    <tbody id="sortableColumnsTbody">
                        @foreach ($allColumns as $idx => $col)
                            <tr draggable="true" class="report-setting-row" data-col-name="{{ $col->ColumName }}">
                                <td class="text-muted" style="cursor: grab;">
                                    <i class="ri-draggable me-1"></i>
                                    <span class="col-order-badge">{{ $idx + 1 }}</span>
                                </td>
                                <td class="text-center">
                                    <input class="form-check-input setting-col-on" type="checkbox"
                                           @checked($col->EffectiveIsOn === 1)>
                                </td>
                                <td class="text-center">
                                    <input class="form-check-input setting-col-total" type="checkbox"
                                           @checked((int)($col->IsTotal ?? 0) === 1)>
                                </td>
                                <td class="text-muted">
                                    {{ $col->OrignalLabel }}
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm setting-col-label"
                                           value="{{ $col->EffectiveLabel }}"
                                           placeholder="{{ $col->OrignalLabel }}">
                                </td>
                                <td>
                                    <input type="number" min="30" max="999" class="form-control form-control-sm setting-col-width-min"
                                           value="{{ $col->EffectiveWidthMin }}"
                                           placeholder="{{ $col->OriginalWidthMin ?: 80 }}">
                                </td>
                                <td>
                                    <input type="number" min="30" max="999" class="form-control form-control-sm setting-col-width-max"
                                           value="{{ $col->EffectiveWidthMax }}"
                                           placeholder="{{ $col->OriginalWidthMax ?: 160 }}">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm setting-col-align">
                                        <option value="left" @selected($col->EffectiveAlign === 'left')>Left</option>
                                        <option value="center" @selected($col->EffectiveAlign === 'center')>Center</option>
                                        <option value="right" @selected($col->EffectiveAlign === 'right')>Right</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Offcanvas Footer Buttons --}}
        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-3">
            <button type="button" class="btn btn-sm btn-danger-light" id="resetReportSettingsBtn">
                <i class="ri-refresh-line me-1"></i>Reset to Default
            </button>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="saveReportSettingsBtn">
                    <i class="ri-save-line me-1"></i>Save Settings
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ── 1. Group Expand / Collapse Helpers ───────────────────────────────────────
function toggleReportGroup(groupId) {
    const icon = document.getElementById('icon_' + groupId);
    const isCollapsed = icon && icon.classList.contains('ri-arrow-right-s-line');
    setGroupChildrenVisible(groupId, isCollapsed);
    if (icon) {
        icon.classList.toggle('ri-arrow-down-s-line', isCollapsed);
        icon.classList.toggle('ri-arrow-right-s-line', !isCollapsed);
    }
}

function setGroupChildrenVisible(groupId, visible) {
    document.querySelectorAll('[data-parent-group="' + groupId + '"]').forEach(function (el) {
        el.style.display = visible ? '' : 'none';
        const childGroupId = el.getAttribute('data-group-id');
        if (childGroupId) {
            if (!visible) {
                setGroupChildrenVisible(childGroupId, false);
            } else {
                const childIcon = document.getElementById('icon_' + childGroupId);
                const childExpanded = !childIcon || childIcon.classList.contains('ri-arrow-down-s-line');
                setGroupChildrenVisible(childGroupId, childExpanded);
            }
        }
    });
}

function expandAllReportGroups(expand) {
    document.querySelectorAll('.report-detail-row, .report-subtotal-row').forEach(function (row) {
        row.style.display = expand ? '' : 'none';
    });
    document.querySelectorAll('.report-group-header').forEach(function (hdr) {
        const parentGrp = hdr.getAttribute('data-parent-group');
        if (parentGrp && parentGrp !== 'root') {
            hdr.style.display = expand ? '' : 'none';
        }
    });
    document.querySelectorAll('.group-toggle-icon').forEach(function (icon) {
        icon.classList.toggle('ri-arrow-down-s-line', expand);
        icon.classList.toggle('ri-arrow-right-s-line', !expand);
    });
}

function toggleAllSettingColumns(state) {
    document.querySelectorAll('#sortableColumnsTbody .setting-col-on').forEach(function (cb) {
        cb.checked = state;
    });
}

function sortReportColumn(colName) {
    const sortByInput = document.getElementById('sortByInput');
    const sortDirInput = document.getElementById('sortDirInput');
    const filterForm = document.getElementById('dynamicReportFilterForm');
    if (!sortByInput || !sortDirInput || !filterForm) return;

    if (sortByInput.value === colName) {
        sortDirInput.value = (sortDirInput.value.toLowerCase() === 'asc') ? 'desc' : 'asc';
    } else {
        sortByInput.value = colName;
        sortDirInput.value = 'asc';
    }
    filterForm.submit();
}

document.addEventListener('DOMContentLoaded', function () {
    // ── 2. Group By & Multi-Level Dropdown Coordination ──────────────────────
    const groupBySelect = document.getElementById('groupBySelect');
    const multiLevelSelect = document.getElementById('multiLevelSelect');
    const filterForm = document.getElementById('dynamicReportFilterForm');

    if (groupBySelect && multiLevelSelect) {
        groupBySelect.addEventListener('change', function () {
            const primaryVal = this.value;
            Array.from(multiLevelSelect.options).forEach(function (opt) {
                if (!opt.value) return;
                opt.disabled = (primaryVal !== '' && opt.value === primaryVal);
                if (opt.disabled && multiLevelSelect.value === primaryVal) {
                    multiLevelSelect.value = '';
                }
            });
            filterForm.submit();
        });

        multiLevelSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    // ── 3. Print / PDF Button ────────────────────────────────────────────────
    const printBtn = document.getElementById('reportPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.open(@json($printUrl), '_blank', 'noopener');
        });
    }

    // ── 4. Drag & Drop Reordering in Report Settings Table ───────────────────
    const tbody = document.getElementById('sortableColumnsTbody');
    let draggedRow = null;

    function refreshOrderNumbers() {
        if (!tbody) return;
        tbody.querySelectorAll('tr.report-setting-row').forEach(function (tr, idx) {
            const badge = tr.querySelector('.col-order-badge');
            if (badge) badge.textContent = (idx + 1);
        });
    }

    if (tbody) {
        tbody.addEventListener('dragstart', function (e) {
            const tr = e.target.closest('tr.report-setting-row');
            if (!tr) return;
            draggedRow = tr;
            tr.classList.add('table-active');
            e.dataTransfer.effectAllowed = 'move';
        });

        tbody.addEventListener('dragend', function () {
            if (draggedRow) {
                draggedRow.classList.remove('table-active');
                draggedRow = null;
            }
            refreshOrderNumbers();
        });

        tbody.addEventListener('dragover', function (e) {
            e.preventDefault();
            const targetRow = e.target.closest('tr.report-setting-row');
            if (!draggedRow || !targetRow || draggedRow === targetRow) return;
            const rect = targetRow.getBoundingClientRect();
            const isAfter = (e.clientY - rect.top) > (rect.height / 2);
            if (isAfter) {
                targetRow.after(draggedRow);
            } else {
                targetRow.before(draggedRow);
            }
        });
    }

    // ── 5. Save Report Settings (via temp_table + RequestID + edit_transaction)
    const saveBtn = document.getElementById('saveReportSettingsBtn');
    const resetBtn = document.getElementById('resetReportSettingsBtn');
    const alertBox = document.getElementById('reportSettingsAlert');
    const settingGroupBy = document.getElementById('settingDefaultGroupBy');
    const settingMultiLevel = document.getElementById('settingDefaultMultiLevel');

    if (settingGroupBy && settingMultiLevel) {
        settingGroupBy.addEventListener('change', function () {
            const pVal = this.value;
            Array.from(settingMultiLevel.options).forEach(function (opt) {
                if (!opt.value) return;
                opt.disabled = (pVal !== '' && opt.value === pVal);
                if (opt.disabled && settingMultiLevel.value === pVal) {
                    settingMultiLevel.value = '';
                }
            });
        });
    }

    function showSettingsAlert(type, msg) {
        if (!alertBox) return;
        alertBox.className = 'alert alert-' + type + ' py-2 mb-3';
        alertBox.textContent = msg;
        alertBox.classList.remove('d-none');
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            const customName = document.getElementById('settingCustomReportName').value.trim();
            const printOrientation = document.getElementById('settingPrintOrientation').value;
            const defaultGroup1 = settingGroupBy ? settingGroupBy.value : '';
            const defaultGroup2 = (settingMultiLevel && !settingMultiLevel.disabled) ? settingMultiLevel.value : '';
            const combinedDefaultGroups = [defaultGroup1, defaultGroup2].filter(Boolean).join(',');

            const columns = [];
            document.querySelectorAll('#sortableColumnsTbody tr.report-setting-row').forEach(function (tr, idx) {
                columns.push({
                    ColumName: tr.getAttribute('data-col-name'),
                    CustomDisplayOrder: idx + 1,
                    CustomIsOn: tr.querySelector('.setting-col-on').checked ? 1 : 0,
                    IsTotal: tr.querySelector('.setting-col-total').checked ? 1 : 0,
                    CustomLabel: tr.querySelector('.setting-col-label').value.trim(),
                    CustomWidthMin: tr.querySelector('.setting-col-width-min').value.trim(),
                    CustomWidthMax: tr.querySelector('.setting-col-width-max').value.trim(),
                    CustomAlignment: tr.querySelector('.setting-col-align').value
                });
            });

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

            fetch(@json(route('reports.settings.save', ['reportId' => $reportId])), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    CustomName: customName,
                    PrintOrientation: printOrientation,
                    RowGroupColumnName: combinedDefaultGroups,
                    columns: columns
                })
            })
            .then(async function (res) {
                const data = await res.json();
                return { ok: res.ok, data: data };
            })
            .then(function (result) {
                if (!result.ok || !result.data.status) {
                    showSettingsAlert('danger', result.data.message || 'Validation failed.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="ri-save-line me-1"></i>Save Settings';
                    return;
                }
                showSettingsAlert('success', result.data.message || 'Settings saved! Reloading...');
                setTimeout(function () {
                    const nextUrl = new URL(window.location.href);
                    nextUrl.searchParams.delete('group_by');
                    nextUrl.searchParams.delete('multi_level');
                    window.location.href = nextUrl.toString();
                }, 400);
            })
            .catch(function (err) {
                showSettingsAlert('danger', 'Error saving settings: ' + err.message);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="ri-save-line me-1"></i>Save Settings';
            });
        });
    }

    // ── 6. Reset Report Settings from Master (`core_digital_invoicing`) ──────
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (!confirm('Reset this report to its original default columns, labels, and display order from the master template?')) {
                return;
            }
            resetBtn.disabled = true;
            resetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Resetting...';

            fetch(@json(route('reports.settings.reset', ['reportId' => $reportId])), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.status) {
                    showSettingsAlert('danger', data.message || 'Reset failed.');
                    resetBtn.disabled = false;
                    resetBtn.innerHTML = '<i class="ri-refresh-line me-1"></i>Reset to Default';
                    return;
                }
                showSettingsAlert('success', data.message || 'Reset complete! Reloading...');
                setTimeout(function () {
                    window.location.href = @json(route('reports.show', ['reportId' => $reportId]));
                }, 400);
            })
            .catch(function (err) {
                showSettingsAlert('danger', 'Error resetting settings: ' + err.message);
                resetBtn.disabled = false;
                resetBtn.innerHTML = '<i class="ri-refresh-line me-1"></i>Reset to Default';
            });
        });
    }
});
</script>
@endsection

