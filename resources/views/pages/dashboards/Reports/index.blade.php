@extends('layouts.master')

@section('content')
<div class="container-fluid pt-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h5 class="fw-semibold mb-0">Reports Center</h5>
            <small class="text-muted">Select a report below to view, filter, group, or customize columns</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text bg-light border-end-0"><i class="ri-search-line text-muted"></i></span>
                <input type="text" id="reportSearchInput" class="form-control border-start-0" placeholder="Search reports...">
            </div>
        </div>
    </div>

    <div class="row g-3" id="reportsGrid">
        @forelse ($reports as $rep)
            @php
                $reportId    = $rep->ReportID ?: $rep->ModuleID;
                $displayName = trim((string) ($rep->CustomName ?? '')) !== '' ? $rep->CustomName : ($rep->ReportName ?: $rep->ModuleName);
                $origName    = $rep->ReportName ?: $rep->ModuleName;
                $icon        = $rep->MainModuleIcon ?: 'ri-file-chart-line';
                $reportUrl   = route('reports.show', ['reportId' => $reportId]);
            @endphp
            <div class="col-xl-4 col-lg-6 col-md-6 report-card-item" data-search="{{ strtolower($displayName . ' ' . $origName . ' ' . ($rep->Description ?? '')) }}">
                <div class="card custom-card h-100 mb-0">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-md bg-primary-transparent text-primary rounded">
                                        <i class="{{ $icon }} fs-18"></i>
                                    </span>
                                    <div>
                                        <h6 class="fw-semibold mb-0">
                                            <a href="{{ $reportUrl }}" class="text-dark text-decoration-none">{{ $displayName }}</a>
                                        </h6>
                                        @if ($displayName !== $origName)
                                            <small class="text-muted">Default: {{ $origName }}</small>
                                        @else
                                            <small class="text-muted">Report ID #{{ $reportId }}</small>
                                        @endif
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark border">
                                    {{ $rep->PrintOrientation ?: 'Landscape' }}
                                </span>
                            </div>
                            <p class="text-muted mb-3">
                                {{ $rep->Description ?: 'Dynamic customizable report with column controls, multi-level grouping, and export.' }}
                            </p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="text-muted small">
                                @if (!empty($rep->RowGroupColumnName))
                                    <i class="ri-node-tree me-1 text-primary"></i>Grouped by default
                                @else
                                    <i class="ri-table-line me-1"></i>Tabular &amp; Multi-Level
                                @endif
                            </span>
                            <a href="{{ $reportUrl }}" class="btn btn-sm btn-primary">
                                <i class="ri-external-link-line me-1"></i>Open Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-body text-center py-5">
                        <i class="ri-bar-chart-box-line fs-32 text-muted d-block mb-2"></i>
                        <h6 class="fw-semibold mb-1">No Reports Available</h6>
                        <p class="text-muted mb-0">You do not have permission to view any reports or none are configured yet.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('reportSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.report-card-item').forEach(function (card) {
                const hay = card.getAttribute('data-search') || '';
                card.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }
});
</script>
@endsection

