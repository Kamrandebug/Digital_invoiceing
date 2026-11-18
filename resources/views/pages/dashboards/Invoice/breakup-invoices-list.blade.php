@extends('layouts.master')

@section('styles')
<style>
    /* Action column */
    #breakupBatchesTable th.action-col,
    #breakupBatchesTable td:last-child {
        min-width: 9.5rem;
        max-width: none !important;
        width: auto !important;
        text-align: end;
        vertical-align: middle;
    }
    #breakupBatchesTable .btn-group.btn-listing {
        position: relative;
    }
    /* Popper uses strategy:fixed on toggle; keep menu above table scroll layers */
    #breakupBatchesTable .dropdown-menu {
        z-index: 1060;
    }

    /* ── Compact Table Styling — mirrors bulkInvoicesTable & invoicesTable exactly ── */
    #breakupBatchesTable {
        font-size: 12.5px;
        margin-bottom: 0;
    }
    #breakupBatchesTable thead th {
        padding: 0.35rem 0.55rem !important;
        font-size: 12px;
        font-weight: 600;
        vertical-align: middle;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    #breakupBatchesTable tbody td {
        padding: 0.28rem 0.55rem !important;
        vertical-align: middle;
        line-height: 1.3;
        border-bottom: 1px solid #f1f5f9;
    }
    #breakupBatchesTable .badge {
        padding: 2px 7px;
        font-size: 10.5px;
        font-weight: 500;
    }
    /* Action buttons — same size as invoice list */
    #breakupBatchesTable .btn-listing .btn {
        padding: 2px 8px !important;
        font-size: 11.5px !important;
        line-height: 1.35 !important;
    }
    #breakupBatchesTable .btn-listing .btn i {
        font-size: 11px;
    }

    /* ── Enhanced Rollback Modal Custom Styling ── */
    #rollbackBatchModal .modal-content {
        border-radius: 12px;
        overflow: hidden;
    }
    #rollbackBatchModal .modal-header {
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        padding: 16px 20px;
    }
    #rollbackBatchModal .rollback-icon-circle {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }
    #rollbackBatchModal .rollback-icon-circle.danger {
        background-color: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    #rollbackBatchModal .rollback-icon-circle.warning {
        background-color: #fef3c7;
        color: #d97706;
        border: 1px solid #fde68a;
    }
    #rollbackBatchModal .rollback-icon-circle.info {
        background-color: #e0f2fe;
        color: #0284c7;
        border: 1px solid #bae6fd;
    }
    #rollbackBatchModal .rollback-icon-circle.secondary {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    #rollbackBatchModal .batch-preview-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
    }
    #rollbackBatchModal .batch-stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 11.5px;
        font-weight: 500;
        color: #334155;
    }

    #rollbackBatchModal .rollback-notice-card {
        border-radius: 8px;
        padding: 12px 14px;
        font-size: 12.5px;
        line-height: 1.5;
    }
    #rollbackBatchModal .rollback-notice-card.danger {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        border-left: 4px solid #ef4444;
        color: #1e293b;
    }
    #rollbackBatchModal .rollback-notice-card.warning {
        background-color: #fffbeb;
        border: 1px solid #fde68a;
        border-left: 4px solid #f59e0b;
        color: #1e293b;
    }
    #rollbackBatchModal .rollback-notice-card.info {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-left: 4px solid #22c55e;
        color: #1e293b;
    }
    #rollbackBatchModal .rollback-notice-title {
        font-weight: 700;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 4px;
    }
    #rollbackBatchModal .rollback-notice-body {
        font-size: 12px;
        color: #334155;
    }

    #rollbackBatchModal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 12px 20px;
    }
    #rollbackBatchModal .btn-cancel {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
    }
    #rollbackBatchModal .btn-cancel:hover {
        background-color: #f1f5f9;
        color: #1e293b;
    }
    #rollbackBatchModal .btn-rollback {
        background-color: #dc2626;
        border: 1px solid #b91c1c;
        color: #ffffff;
    }
    #rollbackBatchModal .btn-rollback:hover {
        background-color: #b91c1c;
        color: #ffffff;
    }

    /* ── Header tab/card styling — mirrors invoice-list and bulk-invoices ── */
    .invoice-card-tab-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 14px;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    /* ── Breakup Modal Sizing, Typography & Theme Alignment ── */
    #breakupGeneratorModal .modal-dialog {
        max-height: 94vh;
        margin-top: 3vh;
        margin-bottom: 3vh;
    }
    #breakupGeneratorModal .modal-content {
        max-height: 94vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }
    #breakupGeneratorModal .modal-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.65rem 1.15rem;
    }
    #breakupGeneratorModal .modal-footer {
        background-color: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 0.55rem 1.15rem;
    }
    #breakupGeneratorModal .modal-body {
        overflow-y: auto !important;
        scrollbar-width: thin !important;
        scrollbar-color: #94a3b8 #f1f5f9 !important;
        padding: 1rem 1.15rem !important;
    }
    #breakupGeneratorModal .modal-body::-webkit-scrollbar {
        width: 8px !important;
        height: 8px !important;
        display: block !important;
    }
    #breakupGeneratorModal .modal-body::-webkit-scrollbar-track {
        background: #f1f5f9 !important;
        border-radius: 4px;
    }
    #breakupGeneratorModal .modal-body::-webkit-scrollbar-thumb {
        background: #94a3b8 !important;
        border-radius: 4px;
    }
    #breakupGeneratorModal .modal-body::-webkit-scrollbar-thumb:hover {
        background: #64748b !important;
    }

    /* Preview Table Typography & Compact Cells (Strictly Aligned with Batch View Modal) */
    #breakupPreviewTable {
        font-size: 12px;
        margin-bottom: 0;
    }
    #breakupPreviewTable thead th {
        padding: 0.35rem 0.55rem !important;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        white-space: nowrap;
    }
    #breakupPreviewTable tbody td,
    #breakupPreviewTable tfoot td {
        padding: 0.28rem 0.55rem !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        line-height: 1.3;
        font-size: 12px;
    }
    #breakupPreviewTable tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Preview Table Scrollbar */
    #breakupPreviewTableWrap {
        scrollbar-width: thin !important;
        scrollbar-color: #94a3b8 #f1f5f9 !important;
    }
    #breakupPreviewTableWrap::-webkit-scrollbar {
        width: 8px !important;
        height: 8px !important;
        display: block !important;
    }
    #breakupPreviewTableWrap::-webkit-scrollbar-track {
        background: #f8fafc !important;
        border-radius: 4px;
    }
    #breakupPreviewTableWrap::-webkit-scrollbar-thumb {
        background: #cbd5e1 !important;
        border-radius: 4px;
    }
    #breakupPreviewTableWrap::-webkit-scrollbar-thumb:hover {
        background: #94a3b8 !important;
    }

    /* Searchable Choices.js in Modal (Matching Theme fs-12 and form-control-sm) */
    #breakupGeneratorModal .choices {
        margin-bottom: 0 !important;
        font-size: 12px !important;
    }
    #breakupGeneratorModal .choices__inner {
        min-height: 31px !important;
        padding: 0.2rem 0.5rem !important;
        font-size: 12px !important;
        border-radius: 0.25rem !important;
        border: 1px solid #e2e8ee !important;
        background-color: #fff !important;
        line-height: 1.4 !important;
    }
    #breakupGeneratorModal .choices__list--single {
        padding: 0 !important;
        font-size: 12px !important;
    }
    #breakupGeneratorModal .choices__list--dropdown {
        z-index: 1060 !important;
        border: 1px solid #e2e8ee !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
        font-size: 12px !important;
    }
    #breakupGeneratorModal .choices__list--dropdown .choices__item {
        font-size: 12px !important;
        padding: 0.35rem 0.6rem !important;
    }
    #breakupGeneratorModal .choices__input {
        font-size: 12px !important;
        background-color: #fff !important;
        margin-bottom: 0.25rem !important;
        padding: 0.25rem 0.5rem !important;
    }
</style>
@endsection

@section('content')

{{-- Page Header --}}
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Breakup Invoices</h1>
        <div class="d-flex float-end">
            <button type="button" class="btn btn-sm btn-primary btn-wave waves-light me-2" onclick="openBreakupModal()">
                <i class="ri-split-cells-horizontal align-middle me-1"></i> Generate Breakup Invoices
            </button>
            <div class="dropdown">
                <button class="btn btn-icon btn-secondary-light btn-sm btn-wave waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('invoices.index') }}"><i class="fas fa-file-invoice me-1 text-primary"></i> View All Invoices</a></li>
                    <li><a class="dropdown-item" href="{{ route('bulk-invoices.index') }}"><i class="fas fa-file-import me-1 text-primary"></i> Bulk Invoices (Excel)</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Alert Messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-1"></i> {!! session('error') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button>
</div>
@endif

{{-- Summary Metric Cards --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card custom-card border-0 shadow-sm mb-0">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted fs-12 mb-1">Total Breakup Batches</span>
                        <h5 class="fw-bold mb-0 text-dark">{{ number_format($summary->total_batches ?? 0) }}</h5>
                    </div>
                    <div class="avatar avatar-md bg-primary-transparent rounded-circle">
                        <i class="ri-stack-line fs-18 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card custom-card border-0 shadow-sm mb-0">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted fs-12 mb-1">Generated Invoices</span>
                        <h5 class="fw-bold mb-0 text-dark">{{ number_format($summary->total_invoices ?? 0) }}</h5>
                    </div>
                    <div class="avatar avatar-md bg-success-transparent rounded-circle">
                        <i class="ri-file-list-3-line fs-18 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card custom-card border-0 shadow-sm mb-0">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted fs-12 mb-1">Total Value (Net)</span>
                        <h5 class="fw-bold mb-0 text-dark">PKR {{ number_format($summary->total_amount ?? 0, 2) }}</h5>
                    </div>
                    <div class="avatar avatar-md bg-info-transparent rounded-circle">
                        <i class="ri-money-dollar-circle-line fs-18 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card custom-card border-0 shadow-sm mb-0">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted fs-12 mb-1">Total GST</span>
                        <h5 class="fw-bold mb-0 text-dark">PKR {{ number_format($summary->total_tax ?? 0, 2) }}</h5>
                    </div>
                    <div class="avatar avatar-md bg-warning-transparent rounded-circle">
                        <i class="ri-percent-line fs-18 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters Card --}}
<div class="card custom-card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form action="{{ route('breakup-invoices.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label fs-12 text-muted mb-1">Search Batch / Ref</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="ri-search-line text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by Title, Request ID, or Product..." value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-12 text-muted mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $filters['start_date'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-12 text-muted mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $filters['end_date'] ?? '' }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">
                    <i class="ri-filter-3-line align-middle me-1"></i> Filter
                </button>
                <a href="{{ route('breakup-invoices.index') }}" class="btn btn-sm btn-light">
                    <i class="ri-refresh-line align-middle"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Listing Table Card --}}
<div class="col-xs-12">
    <div class="card custom-card">
        <div class="card-header invoice-card-tab-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold fs-14 text-dark">Breakup Invoice Batches</span>
                <span class="badge bg-primary-transparent text-primary fs-11">{{ $batches->total() }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap listing-table table-hover align-middle mb-0" id="breakupBatchesTable">
                <thead class="table-light">
                    <tr class="fs-12">
                        <th class="ps-3">Product / Title</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Total Tax</th>
                        <th class="text-center" style="width: 190px;">FBR Status</th>
                        <th>Created By</th>
                        <th>Created On</th>
                        <th class="action-col text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody class="fs-12">
                    @forelse($batches as $batch)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold text-dark fs-12">{{ $batch->Title }}</div>
                            <div class="d-flex align-items-center gap-1 mt-0.5">
                                @if(!empty($batch->SourceReference))
                                    <span class="text-muted fs-11">{{ $batch->SourceReference }}</span>
                                    <span class="text-muted fs-10">&bull;</span>
                                @endif
                                <code class="text-primary font-monospace fs-10" title="Request ID: {{ $batch->RequestID }}">
                                    {{ substr($batch->RequestID, 0, 8) }}...{{ substr($batch->RequestID, -4) }}
                                </code>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-transparent fs-11 fw-semibold">{{ $batch->LiveTotalCount ?? $batch->TotalInvoices }}</span>
                        </td>
                        <td class="text-end fw-semibold">
                            PKR {{ number_format($batch->TotalAmount, 2) }}
                        </td>
                        <td class="text-end text-muted">
                            PKR {{ number_format($batch->TotalTax, 2) }}
                        </td>
                        <td class="text-center">
                            @php
                                $liveTotal = (int) ($batch->LiveTotalCount ?? $batch->TotalInvoices);
                                $livePosted = (int) ($batch->LivePostedCount ?? $batch->FbrPostedCount ?? 0);
                            @endphp
                            @if($liveTotal === 0)
                                <span class="badge bg-light text-muted">Empty Batch</span>
                            @elseif($livePosted >= $liveTotal)
                                <span class="badge rounded-pill bg-success-subtle text-success text-nowrap" style="font-size: 11px; padding: 4px 8px;">
                                    <i class="fas fa-check-circle me-1"></i> {{ $livePosted }} of {{ $liveTotal }} Successfully Posted
                                </span>
                            @elseif($livePosted > 0)
                                <span class="badge rounded-pill bg-warning-subtle text-warning text-nowrap" style="font-size: 11px; padding: 4px 8px;">
                                    <i class="fas fa-paper-plane me-1"></i> {{ $livePosted }} of {{ $liveTotal }} Successfully Posted
                                </span>
                            @else
                                <span class="badge rounded-pill bg-light text-muted border text-nowrap" style="font-size: 11px; padding: 4px 8px;">
                                    <i class="fas fa-clock me-1 text-muted"></i> 0 of {{ $liveTotal }} Successfully Posted
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-medium text-dark">{{ $batch->CreatedByName ?: 'System' }}</span>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($batch->CreatedOn)->format('d-M-Y H:i') }}</td>
                        <td class="action-col text-end pe-3">
                            <div class="action-wrapper d-inline-flex justify-content-end">
                                <div class="btn-group btn-listing">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-list-left" 
                                            onclick="viewBatchInvoices('{{ $batch->RequestID }}', '{{ addslashes($batch->Title) }}')"
                                            title="View Invoices in this batch">
                                        <i class="fas fa-eye me-1"></i> View Invoices
                                    </button>
                                    <button type="button" 
                                            class="btn btn-primary btn-list-right dropdown-toggle dropdown-toggle-split" 
                                            data-bs-toggle="dropdown" 
                                            data-bs-auto-close="true"
                                            data-bs-popper-config='{"strategy":"fixed"}'
                                            aria-expanded="false">
                                        <span class="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="viewBatchInvoices('{{ $batch->RequestID }}', '{{ addslashes($batch->Title) }}')">
                                                <i class="fas fa-list me-2 text-primary"></i> View Invoices ({{ $batch->TotalInvoices }})
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="triggerBatchPrint('{{ $batch->RequestID }}', {{ (int) $batch->TotalInvoices }}, '{{ addslashes($batch->Title) }}')">
                                                <i class="fas fa-print me-2 text-success"></i> Print Invoices ({{ $batch->TotalInvoices }})
                                            </button>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('invoices.index', ['search' => $batch->RequestID, 'fbr_status' => 'all']) }}" target="_blank">
                                                <i class="fas fa-external-link-alt me-2 text-secondary"></i> Open in Invoices Page
                                            </a>
                                        </li>
                                        @php
                                            $liveTotal = (int) ($batch->LiveTotalCount ?? $batch->TotalInvoices);
                                            $livePosted = (int) ($batch->LivePostedCount ?? $batch->FbrPostedCount ?? 0);
                                        @endphp
                                        @if($liveTotal > 0 && $livePosted >= $liveTotal)
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <span class="dropdown-item text-muted disabled" style="cursor: not-allowed;" title="All invoices in this batch have already been submitted to FBR and cannot be deleted">
                                                    <i class="fas fa-lock me-2 text-muted"></i> Rollback Blocked (FBR Posted)
                                                </span>
                                            </li>
                                        @else
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" onclick="confirmBatchRollback('{{ $batch->RequestID }}', '{{ addslashes($batch->Title) }}', {{ $liveTotal }}, {{ $livePosted }})">
                                                    <i class="fas fa-trash-alt me-2 text-danger"></i> Rollback Batch
                                                </button>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="ri-inbox-line fs-24 d-block mb-1"></i>
                            No breakup invoice batches found. Click <strong>"Generate Breakup Invoices"</strong> to create your first batch.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
        <div class="card-footer border-top-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-2">
                <span class="text-muted fs-12">
                    Showing {{ $batches->firstItem() ?? 0 }}
                    to {{ $batches->lastItem() ?? 0 }}
                    of {{ $batches->total() }} entries
                </span>
                <nav class="pagination-style-2">
                    {{ $batches->appends($filters)->links('pagination::bootstrap-5') }}
                </nav>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 1: BREAKUP INVOICES GENERATOR --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="breakupGeneratorModal" tabindex="-1" aria-labelledby="breakupGeneratorModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fs-14 fw-semibold" id="breakupGeneratorModalLabel">
                    <i class="ri-split-cells-horizontal text-primary me-1"></i> Breakup Invoices Generator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                {{-- Form Setup Card --}}
                <form id="breakupGeneratorForm" novalidate onsubmit="generateBreakupPreview(event)">
                    @csrf
                    <input type="hidden" name="request_id" id="form_request_id" value="">
                    
                    <div class="card border rounded mb-3" id="breakupFormCard">
                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                <span class="fs-12 fw-semibold text-dark text-nowrap"><i class="ri-equalizer-line text-primary me-1"></i> Generation Parameters</span>
                                <span id="formCollapsedSummary" class="text-truncate" style="display: none;"></span>
                            </div>
                            <button type="button" class="btn btn-xs btn-light border text-dark py-1 px-2 text-nowrap" id="btnToggleFormCard" onclick="toggleFormCardCollapse()">
                                <i class="ri-arrow-up-s-line me-1" id="formCollapseIcon"></i><span id="formCollapseText">Collapse</span>
                            </button>
                        </div>
                        <div id="breakupFormCardBody">
                            <div class="card-body p-3 bg-light-subtle">
                                {{-- Panel 1: Customer, Product & Pricing --}}
                                <div class="border rounded p-3 mb-2 bg-white">
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="form-label fs-12 fw-semibold mb-1">Customer <span class="text-danger">*</span></label>
                                            <select name="customer_id" id="modal_customer_id" class="form-select form-select-sm fs-12">
                                                <option value="">-- Select Customer --</option>
                                                @foreach($customers as $c)
                                                    <option value="{{ $c->ID }}">
                                                        {{ $c->DisplayName ?? $c->BusinessName ?? ('Customer #' . $c->ID) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <label class="form-label fs-12 fw-semibold mb-1">Product <span class="text-danger">*</span></label>
                                            <select name="product_id" id="modal_product_id" class="form-select form-select-sm fs-12" onchange="onProductSelect(this.value)">
                                                <option value="">-- Select Product --</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->ID }}" 
                                                            data-saleprice="{{ (float) $p->SalePrice }}"
                                                            data-tprate="{{ (float) $p->TPRate }}"
                                                            data-isfixed="{{ (int) ($p->IsFixedRate ?? $p->IsFixedValue ?? 0) }}"
                                                            data-uom="{{ $p->Uom_Desc }}"
                                                            data-saletype="{{ $p->SaleTypeDesc }}"
                                                            data-salerate="{{ $p->SaleTypeToRateDesc }}">
                                                        {{ $p->Name }} {{ !empty($p->Code) ? "({$p->Code})" : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        {{-- Product Attributes Compact Meta Strip (Auto-loaded) --}}
                                        <div class="col-12" id="productInfoBanner" style="display: none;">
                                            <div class="py-1 px-2 border rounded bg-light d-flex flex-wrap align-items-center gap-2 fs-11 text-muted">
                                                <span><i class="ri-scales-3-line text-primary me-1"></i>UOM: <strong id="infoUom" class="text-dark">-</strong></span>
                                                <span>&bull;</span>
                                                <span><i class="ri-file-text-line text-primary me-1"></i>Sale Type: <strong id="infoSaleType" class="text-dark">-</strong></span>
                                                <span>&bull;</span>
                                                <span><i class="ri-percent-line text-primary me-1"></i>Tax Rate: <strong id="infoTaxRate" class="text-primary">-</strong></span>
                                                <span>&bull;</span>
                                                <span><i class="ri-price-tag-3-line text-primary me-1"></i>Fixed TPRate: <strong id="infoFixedRate" class="text-dark">-</strong></span>
                                                <span>&bull;</span>
                                                <span id="unitGrossPriceHint" class="text-success fw-semibold"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fs-12 fw-semibold mb-1">Sale Rate (PKR) <span class="text-danger">*</span></label>
                                            <input type="number" step="any" min="0.01" name="sale_rate" id="modal_sale_rate" class="form-control form-control-sm fs-12 text-end" placeholder="0.00" required oninput="syncAllLimitsFromRates()">
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fs-12 fw-semibold mb-1">Fixed Rate (TPRate)</label>
                                            <input type="number" step="any" min="0" name="tp_rate" id="modal_tp_rate" class="form-control form-control-sm fs-12 text-end" placeholder="Optional" oninput="syncFixedRateFlag(this.value); syncAllLimitsFromRates();">
                                            <input type="hidden" name="is_fixed_value" id="modal_is_fixed_value" value="0">
                                        </div>
                                        <div class="col-md-4 col-sm-12">
                                            <label class="form-label fs-12 fw-semibold mb-1">Sale of Term</label>
                                            <select name="sale_of_term" class="form-select form-select-sm fs-12">
                                                <option value="CASH">CASH</option>
                                                <option value="CREDIT">CREDIT</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Panel 2: Target Quantity, Schedule & Options --}}
                                <div class="border rounded p-3 mb-2 bg-white">
                                    <div class="row g-2 mb-2 align-items-stretch">
                                        <div class="col-md-6 col-sm-12">
                                            <div class="p-2 rounded border bg-light-subtle h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fs-12 fw-semibold text-primary"><i class="ri-pie-chart-line me-1"></i>Total Target</span>
                                                    <span class="text-muted fs-11"><i class="ri-repeat-line me-1"></i>Qty & Amount sync</span>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <label class="form-label fs-11 text-muted mb-1">Target Qty <span class="text-danger">*</span></label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="any" min="0.0001" name="total_target_qty" id="modal_target_qty" class="form-control form-control-sm fs-12 text-end fw-bold bg-white" placeholder="e.g. 1000" required oninput="onTargetQtyChange(this.value)">
                                                            <span class="input-group-text bg-light text-muted fs-11 px-2" id="targetUomAddon">Units</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-11 text-muted mb-1">Target Amount (PKR)</label>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-light text-muted fs-11 px-2">PKR</span>
                                                            <input type="number" step="any" min="0" name="total_target_amount" id="modal_target_amount" class="form-control form-control-sm fs-12 text-end fw-bold bg-white" placeholder="e.g. 750000" oninput="onTargetAmountChange(this.value)" onblur="onTargetAmountBlur()">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <div class="p-2 rounded border bg-white h-100 d-flex flex-column justify-content-between">
                                                <label class="form-label fs-12 fw-semibold mb-1">From Date <span class="text-danger">*</span></label>
                                                <input type="date" name="from_date" id="modal_from_date" class="form-control form-control-sm fs-12" value="{{ date('Y-m-01') }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <div class="p-2 rounded border bg-white h-100 d-flex flex-column justify-content-between">
                                                <label class="form-label fs-12 fw-semibold mb-1">To Date <span class="text-danger">*</span></label>
                                                <input type="date" name="to_date" id="modal_to_date" class="form-control form-control-sm fs-12" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-2 align-items-center pt-1">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="form-label fs-12 fw-semibold mb-1">Invoice Numbering</label>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="form-check form-check-inline mb-0">
                                                    <input class="form-check-input" type="radio" name="numbering_mode" id="numModeAuto" value="auto" checked onchange="toggleNumberingMode()">
                                                    <label class="form-check-label fs-12 fw-medium" for="numModeAuto">Auto</label>
                                                </div>
                                                <div class="form-check form-check-inline mb-0">
                                                    <input class="form-check-input" type="radio" name="numbering_mode" id="numModeCustom" value="custom" onchange="toggleNumberingMode()">
                                                    <label class="form-check-label fs-12 fw-medium" for="numModeCustom">Prefix</label>
                                                </div>
                                                <div id="customPrefixWrapper" style="display: none; width: 140px;">
                                                    <input type="text" name="invoice_prefix" id="modal_invoice_prefix" class="form-control form-control-sm fs-12" value="INV-{{ date('Y') }}" placeholder="e.g. INV-2026">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-12 text-md-end pt-2 pt-md-0">
                                            <div class="form-check form-switch d-inline-block mb-0 text-start">
                                                <input class="form-check-input cursor-pointer" type="checkbox" name="allow_decimal_qty" id="modal_allow_decimal" value="1" onchange="onDecimalToggleChange()">
                                                <label class="form-check-label fs-12 fw-medium text-dark cursor-pointer" for="modal_allow_decimal">
                                                    Allow Decimal Quantities
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Panel 3: Per-Invoice Limits (Min & Max Comparison) --}}
                                <div class="border rounded p-3 mb-0 bg-white">
                                    <div class="row g-3">
                                        <div class="col-md-6 col-sm-12">
                                            <div class="p-2 rounded border bg-light-subtle">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fs-12 fw-semibold text-dark"><i class="ri-arrow-down-line text-success me-1"></i>Min per Invoice</span>
                                                    <span class="text-muted fs-11"><i class="ri-repeat-line me-1"></i>Qty & Amount sync</span>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <label class="form-label fs-11 text-muted mb-1">Min Qty <span class="text-danger">*</span></label>
                                                        <input type="number" step="any" min="0.0001" name="min_qty" id="modal_min_qty" class="form-control form-control-sm fs-12 text-end bg-white" placeholder="e.g. 10" required oninput="onMinQtyChange(this.value)">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-11 text-muted mb-1">Min Amount (PKR)</label>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-light text-muted fs-11 px-2">PKR</span>
                                                            <input type="number" step="any" min="0" name="min_amount" id="modal_min_amount" class="form-control form-control-sm fs-12 text-end bg-white" placeholder="e.g. 15000" oninput="onMinAmountChange(this.value)">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-sm-12">
                                            <div class="p-2 rounded border bg-light-subtle">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fs-12 fw-semibold text-dark"><i class="ri-arrow-up-line text-danger me-1"></i>Max per Invoice</span>
                                                    <span class="text-muted fs-11"><i class="ri-repeat-line me-1"></i>Qty & Amount sync</span>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <label class="form-label fs-11 text-muted mb-1">Max Qty <span class="text-danger">*</span></label>
                                                        <input type="number" step="any" min="0.0001" name="max_qty" id="modal_max_qty" class="form-control form-control-sm fs-12 text-end bg-white" placeholder="e.g. 50" required oninput="onMaxQtyChange(this.value)">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-11 text-muted mb-1">Max Amount (PKR)</label>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-light text-muted fs-11 px-2">PKR</span>
                                                            <input type="number" step="any" min="0" name="max_amount" id="modal_max_amount" class="form-control form-control-sm fs-12 text-end bg-white" placeholder="e.g. 50000" oninput="onMaxAmountChange(this.value)">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-light py-2 text-end">
                                <button type="submit" class="btn btn-sm btn-primary px-3" id="btnGeneratePreview">
                                    <i class="ri-sparkling-fill me-1"></i> Generate Preview
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Loading Spinner Container --}}
                <div id="previewLoadingSpinner" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Generating invoices...</span>
                    </div>
                    <div class="text-muted fs-12 mt-2">Computing target distribution and calculating FBR taxes...</div>
                </div>

                {{-- Staging Preview Container (Loaded via AJAX) --}}
                <div id="breakupPreviewContainer"></div>
            </div>
            <div class="modal-footer bg-light py-2" id="breakupModalFooter">
                <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2">
                    <div id="footerSummaryPills" class="d-none d-flex align-items-center gap-2">
                        <span class="badge bg-primary-transparent text-primary fs-11" id="footerInvoicesCount">0 Invoices</span>
                        <span class="badge bg-success-transparent text-success fs-11" id="footerTotalAmount">PKR 0.00</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <button type="button" class="btn btn-sm btn-light border text-dark d-none" id="btnFooterReroll" onclick="reRollBreakup()">
                            <i class="ri-refresh-line me-1"></i> Re-roll
                        </button>
                        <button type="button" class="btn btn-sm btn-success px-3 d-none" id="btnFooterCommit" onclick="commitBreakupInvoices()">
                            <i class="ri-check-double-line me-1"></i> Confirm & Move to Invoices
                        </button>
                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 2: VIEW BATCH INVOICES (Shared with Bulk Invoices)       --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@include('pages.dashboards.Invoice._batch-invoices-view-modal')
@include('pages.dashboards.Invoice._batch-print-chunk-modal')

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 3: ROLLBACK BATCH CONFIRMATION (Enhanced UI/UX)         --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="rollbackBatchModal" tabindex="-1" aria-labelledby="rollbackBatchModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content shadow-lg border-0">
            <form id="rollbackBatchForm" method="POST" action="">
                @csrf
                <div class="modal-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rollback-icon-circle danger" id="rollbackHeaderIconBadge">
                            <i class="fas fa-trash-alt" id="rollbackHeaderIcon"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0 fs-15" id="rollbackModalHeaderTitle">
                                Rollback Batch
                            </h5>
                            <small class="text-muted fs-11" id="rollbackModalHeaderSubtitle">
                                Confirm invoice removal & history archiving
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    {{-- Target Batch Preview Card --}}
                    <div class="batch-preview-card mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fs-11 fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">
                                <i class="fas fa-layer-group me-1 text-primary"></i> Target Batch
                            </span>
                            <span class="badge bg-white text-muted border font-monospace fs-10" id="rollbackBatchReqBadge"></span>
                        </div>
                        <div class="fw-bold text-dark fs-13 mb-2" id="rollbackBatchTitle"></div>
                        <div class="d-flex flex-wrap gap-2 pt-2 border-top border-slate-200">
                            <div class="batch-stat-pill">
                                <span class="text-muted">Total Invoices:</span>
                                <strong class="text-dark" id="rollbackStatTotal">0</strong>
                            </div>
                            <div class="batch-stat-pill">
                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>FBR Posted:</span>
                                <strong class="text-success" id="rollbackStatPosted">0</strong>
                            </div>
                            <div class="batch-stat-pill">
                                <span class="text-danger"><i class="fas fa-trash-alt me-1"></i>To Delete:</span>
                                <strong class="text-danger" id="rollbackStatUnposted">0</strong>
                            </div>
                        </div>
                    </div>

                    {{-- High-Contrast Notice / Alert Card --}}
                    <div class="rollback-notice-card danger" id="rollbackAlertBox">
                        <div class="rollback-notice-title" id="rollbackNoticeTitle">
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                            <span>Confirm Deletion</span>
                        </div>
                        <div class="rollback-notice-body" id="rollbackNoticeBody">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-cancel px-3 py-1-5 fs-12 fw-semibold" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm btn-rollback px-3 py-1-5 fs-12 fw-semibold shadow-sm d-inline-flex align-items-center gap-1" id="rollbackSubmitBtn">
                        <i class="fas fa-trash-alt me-1"></i> <span id="rollbackSubmitBtnText">Confirm Rollback</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 4: DIFFERENCE / TARGET MISMATCH CONFIRMATION MODAL        --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="differenceWarningModal" tabindex="-1" aria-labelledby="differenceWarningModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="rollback-icon-circle warning">
                        <i class="fas fa-balance-scale text-warning"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 fs-15">
                            Target Quantity Mismatch
                        </h5>
                        <small class="text-muted fs-11">
                            Generated total differs from target allocation
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                {{-- Difference Summary Preview Card --}}
                <div class="batch-preview-card mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fs-11 fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">
                            <i class="fas fa-layer-group me-1 text-primary"></i> Allocation Reconciliation
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 pt-2 border-top border-slate-200">
                        <div class="batch-stat-pill">
                            <span class="text-muted">Target Qty:</span>
                            <strong class="text-dark" id="diffWarnTargetQty">0.00</strong>
                        </div>
                        <div class="batch-stat-pill">
                            <span class="text-primary"><i class="fas fa-calculator me-1"></i>Generated Sum:</span>
                            <strong class="text-primary" id="diffWarnGeneratedQty">0.00</strong>
                        </div>
                        <div class="batch-stat-pill">
                            <span class="text-warning"><i class="fas fa-exclamation-circle me-1"></i>Difference:</span>
                            <strong id="diffWarnDifferenceVal">0.00</strong>
                        </div>
                    </div>
                </div>

                {{-- Notice Alert Card --}}
                <div class="rollback-notice-card warning" id="diffWarnAlertBox">
                    <div class="rollback-notice-title" id="diffWarnNoticeTitle">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <span>Unallocated Difference Detected</span>
                    </div>
                    <div class="rollback-notice-body" id="diffWarnNoticeBody">
                        The generated total quantity does not match your target quantity.
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-sm btn-cancel px-3 py-1-5 fs-12 fw-semibold" data-bs-dismiss="modal">
                    Cancel & Adjust
                </button>
                <button type="button" class="btn btn-sm btn-success px-3 py-1-5 fs-12 fw-semibold shadow-sm d-inline-flex align-items-center gap-1" onclick="executeCommitBreakupInvoices(true)">
                    <i class="ri-check-double-line me-1"></i> Yes, Proceed with Difference
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 5: COMMIT SUCCESS MODAL                                   --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="commitSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content shadow-lg border-0 text-center p-3">
            <div class="modal-body p-3">
                <div class="avatar avatar-xl bg-success-transparent rounded-circle mx-auto mb-3" style="width: 56px; height: 56px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-check-circle fs-28 text-success"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1 fs-16">Invoices Created Successfully!</h5>
                <p class="text-muted fs-12 mb-3" id="commitSuccessMessage">The invoices have been successfully generated and saved to your invoice list.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-sm btn-success px-4 py-1-5 fs-12 fw-semibold" onclick="window.location.reload();">
                        <i class="ri-check-line me-1"></i> Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 6: THEMED ALERT / NOTICE MODAL                            --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="themedAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content shadow-lg border-0 text-center p-3">
            <div class="modal-body p-3">
                <div class="avatar avatar-xl rounded-circle mx-auto mb-3" id="themedAlertIconBg" style="width: 56px; height: 56px; display: flex; align-items: center; justify-content: center;">
                    <i class="fs-28" id="themedAlertIcon"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1 fs-16" id="themedAlertTitle">Notice</h5>
                <p class="fs-12 mb-3 text-muted" id="themedAlertMessage">Message</p>
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-sm btn-secondary px-4 py-1-5 fs-12 fw-semibold" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const CSRF_TOKEN = '{{ csrf_token() }}';
    let lastEditedLimitField = null;

    // ── Pre-compiled Products Metadata Map (Immune to DOM dropdown manipulation) ──
    const PRODUCTS_MAP = {
        @foreach($products as $p)
        "{{ $p->ID }}": {
            id: {{ $p->ID }},
            name: {!! json_encode($p->Name . (!empty($p->Code) ? " ({$p->Code})" : '')) !!},
            salePrice: {{ (float) ($p->SalePrice ?? 0) }},
            tpRate: {{ (float) ($p->TPRate ?? 0) }},
            isFixed: {{ (int) ($p->IsFixedRate ?? $p->IsFixedValue ?? 0) }},
            uom: {!! json_encode($p->Uom_Desc ?? '-') !!},
            saleType: {!! json_encode($p->SaleTypeDesc ?? '-') !!},
            taxRate: {!! json_encode($p->SaleTypeToRateDesc ?? '-') !!}
        },
        @endforeach
    };

    let customerChoicesInstance = null;
    let productChoicesInstance = null;

    function initChoicesDropdowns() {
        try {
            if (typeof Choices === 'undefined') return;

            const custEl = document.getElementById('modal_customer_id');
            if (custEl && !customerChoicesInstance && !custEl.closest('.choices')) {
                customerChoicesInstance = new Choices(custEl, {
                    allowHTML: true,
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false,
                    removeItemButton: false,
                    searchPlaceholderValue: 'Search customer...'
                });
            }

            const prodEl = document.getElementById('modal_product_id');
            if (prodEl && !productChoicesInstance && !prodEl.closest('.choices')) {
                productChoicesInstance = new Choices(prodEl, {
                    allowHTML: true,
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false,
                    removeItemButton: false,
                    searchPlaceholderValue: 'Search product...'
                });

                prodEl.addEventListener('change', function(e) {
                    onProductSelect(this.value);
                });
                prodEl.addEventListener('addItem', function(e) {
                    if (e.detail && e.detail.value) {
                        onProductSelect(e.detail.value);
                    }
                });
            }
        } catch(err) {
            console.warn('Choices initialization skipped:', err);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('breakupGeneratorModal');
        if (modalEl) {
            modalEl.addEventListener('shown.bs.modal', function() {
                initChoicesDropdowns();
            });
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function updateCollapsedSummary() {
        const custSel = document.getElementById('modal_customer_id');
        const prodSel = document.getElementById('modal_product_id');
        const custOpt = custSel ? (custSel.querySelector(`option[value="${custSel.value}"]`) || (custSel.selectedIndex >= 0 ? custSel.options[custSel.selectedIndex] : null)) : null;
        const prodId = prodSel ? prodSel.value : '';
        const p = PRODUCTS_MAP[prodId];
        const targetQty = document.getElementById('modal_target_qty').value || '0';
        const targetAmt = parseFloat(document.getElementById('modal_target_amount')?.value) || 0;
        const uom = document.getElementById('targetUomAddon').textContent.trim() || 'Units';
        const amtStr = targetAmt > 0 ? ` (≈ PKR ${formatMoney(targetAmt)})` : '';

        const custName = custOpt && custOpt.value ? custOpt.textContent.trim() : 'Customer';
        const prodName = p && p.name ? p.name : (prodSel && prodSel.selectedIndex >= 0 && prodSel.options[prodSel.selectedIndex] ? prodSel.options[prodSel.selectedIndex].textContent.trim() : 'Product');

        const summaryEl = document.getElementById('formCollapsedSummary');
        if (summaryEl) {
            summaryEl.innerHTML = `<span class="badge bg-light text-dark border fs-11 fw-normal px-2 py-1"><span class="text-primary fw-medium">${escapeHtml(custName)}</span> &bull; ${escapeHtml(prodName)} &bull; <strong class="text-dark">${escapeHtml(targetQty)} ${escapeHtml(uom)}${amtStr}</strong></span>`;
        }
    }

    // ── Unified 2-State Workflow (Setup vs Review) ────────────────────
    function switchMode(mode) {
        const formBody = document.getElementById('breakupFormCardBody');
        const previewContainer = document.getElementById('breakupPreviewContainer');
        const summaryEl = document.getElementById('formCollapsedSummary');
        const icon = document.getElementById('formCollapseIcon');
        const text = document.getElementById('formCollapseText');
        const footerSummary = document.getElementById('footerSummaryPills');
        const btnFooterReroll = document.getElementById('btnFooterReroll');
        const btnFooterCommit = document.getElementById('btnFooterCommit');

        if (mode === 'review') {
            // State 2: Review Mode -> Parameters collapsed, Preview visible, sticky footer actions active
            if (formBody) formBody.style.display = 'none';
            if (previewContainer) previewContainer.style.display = 'block';
            if (summaryEl) {
                updateCollapsedSummary();
                summaryEl.style.display = 'inline-block';
            }
            if (icon) icon.className = 'ri-edit-line me-1';
            if (text) text.textContent = 'Edit Parameters';

            if (footerSummary) footerSummary.classList.remove('d-none');
            if (btnFooterReroll) btnFooterReroll.classList.remove('d-none');
            if (btnFooterCommit) btnFooterCommit.classList.remove('d-none');
        } else {
            // State 1: Setup / Edit Mode -> Parameters visible, Preview hidden, sticky footer actions hidden
            if (formBody) formBody.style.display = 'block';
            if (previewContainer) previewContainer.style.display = 'none';
            if (summaryEl) summaryEl.style.display = 'none';
            if (icon) icon.className = 'ri-arrow-up-s-line me-1';
            if (text) text.textContent = 'Collapse';

            if (footerSummary) footerSummary.classList.add('d-none');
            if (btnFooterReroll) btnFooterReroll.classList.add('d-none');
            if (btnFooterCommit) btnFooterCommit.classList.add('d-none');
        }
    }

    // ── Toggle Parameters Card Collapse ───────────────────────────────
    function toggleFormCardCollapse() {
        const formBody = document.getElementById('breakupFormCardBody');
        const isCollapsed = formBody && formBody.style.display === 'none';
        const hasPreview = !!document.getElementById('form_request_id').value;

        if (isCollapsed) {
            // User clicked "Edit Parameters" -> expand parameters, hide preview table
            switchMode('edit');
        } else {
            // User clicked "Collapse"
            if (hasPreview) {
                switchMode('review');
            } else {
                formBody.style.display = 'none';
                const icon = document.getElementById('formCollapseIcon');
                const text = document.getElementById('formCollapseText');
                if (icon) icon.className = 'ri-arrow-down-s-line me-1';
                if (text) text.textContent = 'Expand';
            }
        }
    }

    // ── Open Generator Modal ──────────────────────────────────────────
    function openBreakupModal() {
        try {
            const reqInput = document.getElementById('form_request_id');
            if (reqInput) reqInput.value = '';
            const prevContainer = document.getElementById('breakupPreviewContainer');
            if (prevContainer) prevContainer.innerHTML = '';
            switchMode('edit');
        } catch (e) {
            console.error('Modal prep error:', e);
        }

        const modalEl = document.getElementById('breakupGeneratorModal');
        if (modalEl) {
            try {
                if (window.bootstrap && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                } else if (window.$) {
                    $('#breakupGeneratorModal').modal('show');
                }
            } catch (err) {
                console.error('Error showing modal via bootstrap:', err);
                if (window.$) {
                    $('#breakupGeneratorModal').modal('show');
                }
            }
        }
    }

    // ── Gross Unit Price Calculation (Accounting for FBR Sales Tax & 3rd Sched Fixed Rate) ──
    function getGrossUnitPrice() {
        const sel = document.getElementById('modal_product_id');
        const productId = sel ? sel.value : '';
        const p = PRODUCTS_MAP[productId];
        const saleRate = parseFloat(document.getElementById('modal_sale_rate').value) || 0;
        const tpRate = parseFloat(document.getElementById('modal_tp_rate').value) || 0;
        const isFixed = parseInt(document.getElementById('modal_is_fixed_value').value) || 0;

        let taxPercent = 0;
        if (p && p.taxRate) {
            const m = p.taxRate.match(/(\d+(\.\d+)?)%/);
            if (m) {
                taxPercent = parseFloat(m[1]) || 0;
            } else {
                const num = parseFloat(p.taxRate);
                if (!isNaN(num)) taxPercent = num;
            }
        }

        if (isFixed === 1 && tpRate > 0) {
            return saleRate + (tpRate * (taxPercent / 100));
        }
        return saleRate * (1 + (taxPercent / 100));
    }

    let lastEditedTargetField = 'target_qty'; // 'target_qty' or 'target_amount'

    // ── Two-Way Synchronizer: Target Qty <-> Target Amount ─────────────
    function onTargetQtyChange(val) {
        lastEditedTargetField = 'target_qty';
        const grossPrice = getGrossUnitPrice();
        const targetAmountInput = document.getElementById('modal_target_amount');
        if (!targetAmountInput) return;

        const qty = parseFloat(val);
        if (isNaN(qty) || qty <= 0 || grossPrice <= 0) {
            targetAmountInput.value = '';
            updateCollapsedSummary();
            return;
        }

        const amount = Math.round(qty * grossPrice * 100) / 100;
        targetAmountInput.value = amount.toFixed(2);
        updateCollapsedSummary();
    }

    function onTargetAmountChange(val) {
        lastEditedTargetField = 'target_amount';
        const grossPrice = getGrossUnitPrice();
        const targetQtyInput = document.getElementById('modal_target_qty');
        if (!targetQtyInput) return;

        const amount = parseFloat(val);
        if (isNaN(amount) || amount <= 0 || grossPrice <= 0) {
            targetQtyInput.value = '';
            updateCollapsedSummary();
            return;
        }

        const allowDecimal = document.getElementById('modal_allow_decimal').checked;
        let qty;
        if (allowDecimal) {
            qty = parseFloat((amount / grossPrice).toFixed(4));
        } else {
            qty = Math.max(1, Math.round(amount / grossPrice));
        }
        targetQtyInput.value = qty;
        updateCollapsedSummary();
    }

    function onTargetAmountBlur() {
        const grossPrice = getGrossUnitPrice();
        const targetQtyInput = document.getElementById('modal_target_qty');
        const targetAmountInput = document.getElementById('modal_target_amount');
        if (!targetQtyInput || !targetAmountInput || grossPrice <= 0) return;

        const allowDecimal = document.getElementById('modal_allow_decimal').checked;
        const qty = parseFloat(targetQtyInput.value);
        if (!allowDecimal && !isNaN(qty) && qty > 0) {
            // Snap amount to the exact whole-unit physical amount
            const snapped = Math.round(qty * grossPrice * 100) / 100;
            targetAmountInput.value = snapped.toFixed(2);
            updateCollapsedSummary();
        }
    }

    // ── Live Batch Total Amount & Unit Gross Rate Display ─────────────
    function updateBatchTotalAmountDisplay() {
        const grossPrice = getGrossUnitPrice();
        const targetQty = parseFloat(document.getElementById('modal_target_qty').value) || 0;
        const estTotal = targetQty * grossPrice;

        const hint = document.getElementById('unitGrossPriceHint');
        if (hint) {
            if (grossPrice > 0) {
                hint.innerHTML = '<i class="ri-money-dollar-circle-line text-success me-1"></i>Gross Unit: <strong class="text-dark">PKR ' + formatMoney(grossPrice) + '</strong> <small class="text-muted">(incl. tax)</small>';
            } else {
                hint.innerHTML = '';
            }
        }
    }

    // ── Two-Way Synchronizers: Min/Max Qty <-> Amount ──────────────────
    function onMinQtyChange(val) {
        lastEditedLimitField = 'min_qty';
        const grossPrice = getGrossUnitPrice();
        const minAmountInput = document.getElementById('modal_min_amount');
        if (!minAmountInput) return;

        const qty = parseFloat(val);
        if (isNaN(qty) || qty <= 0 || grossPrice <= 0) {
            minAmountInput.value = '';
            return;
        }

        const amount = Math.round(qty * grossPrice * 100) / 100;
        minAmountInput.value = amount.toFixed(2);
    }

    function onMinAmountChange(val) {
        lastEditedLimitField = 'min_amount';
        const grossPrice = getGrossUnitPrice();
        const minQtyInput = document.getElementById('modal_min_qty');
        if (!minQtyInput) return;

        const amount = parseFloat(val);
        if (isNaN(amount) || amount <= 0 || grossPrice <= 0) {
            minQtyInput.value = '';
            return;
        }

        const allowDecimal = document.getElementById('modal_allow_decimal').checked;
        let qty;
        if (allowDecimal) {
            qty = parseFloat((amount / grossPrice).toFixed(4));
        } else {
            qty = Math.max(1, Math.ceil(amount / grossPrice));
        }
        minQtyInput.value = qty;
    }

    function onMaxQtyChange(val) {
        lastEditedLimitField = 'max_qty';
        const grossPrice = getGrossUnitPrice();
        const maxAmountInput = document.getElementById('modal_max_amount');
        if (!maxAmountInput) return;

        const qty = parseFloat(val);
        if (isNaN(qty) || qty <= 0 || grossPrice <= 0) {
            maxAmountInput.value = '';
            return;
        }

        const amount = Math.round(qty * grossPrice * 100) / 100;
        maxAmountInput.value = amount.toFixed(2);
    }

    function onMaxAmountChange(val) {
        lastEditedLimitField = 'max_amount';
        const grossPrice = getGrossUnitPrice();
        const maxQtyInput = document.getElementById('modal_max_qty');
        if (!maxQtyInput) return;

        const amount = parseFloat(val);
        if (isNaN(amount) || amount <= 0 || grossPrice <= 0) {
            maxQtyInput.value = '';
            return;
        }

        const allowDecimal = document.getElementById('modal_allow_decimal').checked;
        let qty;
        if (allowDecimal) {
            qty = parseFloat((amount / grossPrice).toFixed(4));
        } else {
            const minQty = parseFloat(document.getElementById('modal_min_qty').value) || 1;
            qty = Math.max(minQty, Math.floor(amount / grossPrice));
        }
        maxQtyInput.value = qty;
    }

    function onDecimalToggleChange() {
        const grossPrice = getGrossUnitPrice();
        if (grossPrice <= 0) return;

        // Recalculate Target Qty from Target Amount if user edited Amount
        const targetAmt = parseFloat(document.getElementById('modal_target_amount').value);
        if (lastEditedTargetField === 'target_amount' && !isNaN(targetAmt) && targetAmt > 0) {
            onTargetAmountChange(targetAmt);
            onTargetAmountBlur();
        }

        // Recalculate Qty from Amount if user specified amounts
        const minAmt = parseFloat(document.getElementById('modal_min_amount').value);
        if (!isNaN(minAmt) && minAmt > 0) {
            onMinAmountChange(minAmt);
        }
        const maxAmt = parseFloat(document.getElementById('modal_max_amount').value);
        if (!isNaN(maxAmt) && maxAmt > 0) {
            onMaxAmountChange(maxAmt);
        }
    }

    function syncAllLimitsFromRates() {
        updateBatchTotalAmountDisplay();
        const grossPrice = getGrossUnitPrice();
        if (grossPrice <= 0) return;

        const targetAmt = parseFloat(document.getElementById('modal_target_amount').value);
        const targetQty = parseFloat(document.getElementById('modal_target_qty').value);
        if (lastEditedTargetField === 'target_amount' && !isNaN(targetAmt) && targetAmt > 0) {
            onTargetAmountChange(targetAmt);
            onTargetAmountBlur();
        } else if (!isNaN(targetQty) && targetQty > 0) {
            onTargetQtyChange(targetQty);
        }

        const minAmt = parseFloat(document.getElementById('modal_min_amount').value);
        const minQty = parseFloat(document.getElementById('modal_min_qty').value);
        if (lastEditedLimitField === 'min_amount' && !isNaN(minAmt) && minAmt > 0) {
            onMinAmountChange(minAmt);
        } else if (!isNaN(minQty) && minQty > 0) {
            onMinQtyChange(minQty);
        }

        const maxAmt = parseFloat(document.getElementById('modal_max_amount').value);
        const maxQty = parseFloat(document.getElementById('modal_max_qty').value);
        if (lastEditedLimitField === 'max_amount' && !isNaN(maxAmt) && maxAmt > 0) {
            onMaxAmountChange(maxAmt);
        } else if (!isNaN(maxQty) && maxQty > 0) {
            onMaxQtyChange(maxQty);
        }
    }

    // ── Product Selection Auto-Loader ─────────────────────────────────
    function onProductSelect(productId) {
        if (!productId) {
            document.getElementById('productInfoBanner').style.display = 'none';
            return;
        }

        const p = PRODUCTS_MAP[productId];
        if (!p) {
            document.getElementById('productInfoBanner').style.display = 'none';
            return;
        }

        const salePrice = p.salePrice || 0;
        const tpRate = p.tpRate || 0;
        const isFixed = p.isFixed || 0;
        const uom = p.uom || '-';
        const saleType = p.saleType || '-';
        const taxRate = p.taxRate || '-';

        document.getElementById('modal_sale_rate').value = salePrice;
        document.getElementById('modal_tp_rate').value = tpRate > 0 ? tpRate : '';
        document.getElementById('modal_is_fixed_value').value = isFixed;

        document.getElementById('infoUom').textContent = uom;
        document.getElementById('targetUomAddon').textContent = uom !== '-' ? uom : 'Units';
        document.getElementById('infoSaleType').textContent = saleType;
        document.getElementById('infoTaxRate').textContent = taxRate;
        document.getElementById('infoFixedRate').textContent = isFixed == 1 ? (tpRate + ' (Active)') : 'None';

        document.getElementById('productInfoBanner').style.display = 'block';

        syncAllLimitsFromRates();
    }

    // ── Sync Fixed Rate Flag on user typing ───────────────────────────
    function syncFixedRateFlag(val) {
        const isFixed = (parseFloat(val) || 0) > 0 ? 1 : 0;
        document.getElementById('modal_is_fixed_value').value = isFixed;
        const bannerFixed = document.getElementById('infoFixedRate');
        if (bannerFixed) {
            bannerFixed.textContent = isFixed == 1 ? (val + ' (Active)') : 'None';
        }
    }

    // ── Toggle Invoice Numbering Mode (Auto vs Custom Prefix) ───────────
    function toggleNumberingMode() {
        const isCustom = document.getElementById('numModeCustom').checked;
        const prefixWrap = document.getElementById('customPrefixWrapper');
        if (prefixWrap) {
            prefixWrap.style.display = isCustom ? 'block' : 'none';
            if (isCustom) {
                document.getElementById('modal_invoice_prefix').focus();
            }
        }
    }

    // ── Generate Staging Preview via AJAX ──────────────────────────────
    function generateBreakupPreview(e) {
        if (e) e.preventDefault();

        // Input validation before dispatch
        const custVal = document.getElementById('modal_customer_id').value;
        const prodVal = document.getElementById('modal_product_id').value;
        const saleRate = parseFloat(document.getElementById('modal_sale_rate').value) || 0;
        const targetQty = parseFloat(document.getElementById('modal_target_qty').value) || 0;
        const minQty = parseFloat(document.getElementById('modal_min_qty').value) || 0;
        const maxQty = parseFloat(document.getElementById('modal_max_qty').value) || 0;

        if (!custVal) {
            showThemedAlert('Customer Required', 'Please select a Customer.', 'warning');
            return;
        }
        if (!prodVal) {
            showThemedAlert('Product Required', 'Please select a Product.', 'warning');
            return;
        }
        if (saleRate <= 0) {
            showThemedAlert('Invalid Rate', 'Please enter a valid Sale Rate.', 'warning');
            document.getElementById('modal_sale_rate').focus();
            return;
        }
        if (targetQty <= 0) {
            showThemedAlert('Invalid Target', 'Please enter a valid Total Target Quantity or Amount.', 'warning');
            document.getElementById('modal_target_qty').focus();
            return;
        }
        if (minQty <= 0) {
            showThemedAlert('Invalid Min Quantity', 'Please enter a valid Min Quantity.', 'warning');
            document.getElementById('modal_min_qty').focus();
            return;
        }
        if (maxQty <= 0) {
            showThemedAlert('Invalid Max Quantity', 'Please enter a valid Max Quantity.', 'warning');
            document.getElementById('modal_max_qty').focus();
            return;
        }
        if (minQty > maxQty) {
            showThemedAlert('Range Mismatch', 'Min Quantity cannot exceed Max Quantity.', 'warning');
            document.getElementById('modal_min_qty').focus();
            return;
        }
        if (maxQty > targetQty) {
            showThemedAlert('Limit Exceeded', 'Max Quantity cannot exceed Total Target Quantity.', 'warning');
            document.getElementById('modal_max_qty').focus();
            return;
        }

        const form = document.getElementById('breakupGeneratorForm');
        const formData = new FormData(form);

        if (document.getElementById('numModeAuto').checked) {
            formData.set('invoice_prefix', '');
            formData.set('numbering_mode', 'auto');
        } else {
            formData.set('numbering_mode', 'custom');
        }

        document.getElementById('previewLoadingSpinner').style.display = 'block';
        document.getElementById('breakupPreviewContainer').innerHTML = '';
        document.getElementById('breakupPreviewContainer').style.display = 'none';
        document.getElementById('btnGeneratePreview').disabled = true;

        fetch('{{ route("breakup-invoices.generate-preview") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('previewLoadingSpinner').style.display = 'none';
            document.getElementById('btnGeneratePreview').disabled = false;

            if (!data.status) {
                showThemedAlert('Generation Error', data.message || 'Failed to generate preview.', 'error');
                return;
            }

            document.getElementById('form_request_id').value = data.request_id;
            document.getElementById('breakupPreviewContainer').innerHTML = data.preview_html;

            // Switch to Review Mode: parameters collapsed, preview table visible, footer action buttons shown
            switchMode('review');

            const footCount = document.getElementById('footerInvoicesCount');
            const footAmt = document.getElementById('footerTotalAmount');
            if (footCount && data.totals) footCount.textContent = data.totals.total_invoices + ' Invoices';
            if (footAmt && data.totals) footAmt.textContent = 'PKR ' + formatMoney(data.totals.total_amount);

            // Restore extended taxes toggle state if it was enabled
            const toggleCheckbox = document.getElementById('toggleExtendedTaxes');
            if (toggleCheckbox && showExtendedTaxesState) {
                toggleCheckbox.checked = true;
                toggleExtendedTaxColumns(true);
            }

            // Smooth scroll down to the preview section
            setTimeout(() => {
                const previewEl = document.getElementById('breakupPreviewContainer');
                if (previewEl) {
                    previewEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        })
        .catch(err => {
            document.getElementById('previewLoadingSpinner').style.display = 'none';
            document.getElementById('btnGeneratePreview').disabled = false;
            showThemedAlert('Connection Error', 'Failed to connect to server: ' + err, 'error');
        });
    }

    // ── Toggle Extended Tax Columns (Discount, FED, Extra Tax, Further Tax) ──
    let showExtendedTaxesState = false;

    function toggleExtendedTaxColumns(show) {
        showExtendedTaxesState = show;
        const cols = document.querySelectorAll('.col-ext-tax');
        cols.forEach(el => {
            if (show) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        });
    }

    // ── Re-roll / Regenerate with same parameters ──────────────────────
    function reRollBreakup() {
        generateBreakupPreview(null);
    }

    // ── Inline Row Edit Handler (Recalculates Line & Batch Totals) ─────
    function onPreviewRowChange(tempId) {
        const qtyInput = document.getElementById('row-qty-' + tempId);
        const row = document.getElementById('preview-row-' + tempId);
        const dateInput = row.querySelector('.preview-date-input');

        const newQty = parseFloat(qtyInput.value) || 0;
        const newDate = dateInput ? dateInput.value : null;

        if (newQty <= 0) {
            showThemedAlert('Invalid Quantity', 'Quantity must be greater than zero.', 'warning');
            return;
        }

        fetch('{{ route("breakup-invoices.update-row") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                temp_id: tempId,
                qty: newQty,
                date: newDate
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.status) {
                showThemedAlert('Update Failed', data.message || 'Row update failed.', 'error');
                return;
            }

            // Update row cells
            document.getElementById('row-excl-' + tempId).textContent = formatMoney(data.row.value_excl_st);
            if (document.getElementById('row-fixed-' + tempId) && data.row.fixed_notified != null) {
                document.getElementById('row-fixed-' + tempId).textContent = formatMoney(data.row.fixed_notified);
            }
            document.getElementById('row-tax-' + tempId).textContent = formatMoney(data.row.tax);
            document.getElementById('row-total-' + tempId).textContent = formatMoney(data.row.total);

            // Update batch summary counters
            syncPreviewTotals(data.batch_totals);
        })
        .catch(err => showThemedAlert('Update Error', 'Failed to update row: ' + err, 'error'));
    }

    // ── Delete Single Row from Preview ─────────────────────────────────
    function deletePreviewRow(tempId) {
        if (!confirm('Are you sure you want to delete this invoice from the preview?')) return;

        fetch('{{ route("breakup-invoices.delete-row") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ temp_id: tempId })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.status) {
                showThemedAlert('Delete Failed', data.message || 'Delete row failed.', 'error');
                return;
            }

            const tr = document.getElementById('preview-row-' + tempId);
            if (tr) tr.remove();

            syncPreviewTotals(data.batch_totals);
        })
        .catch(err => showThemedAlert('Delete Error', 'Failed to delete row: ' + err, 'error'));
    }

    // ── Sync Footers & Difference Counters ─────────────────────────────
    function syncPreviewTotals(totals) {
        const targetQty = parseFloat(document.getElementById('modal_target_qty').value) || 0;
        const totalQty = totals.total_qty;
        const diff = targetQty - totalQty;

        const qtyDisp = document.getElementById('previewTotalQtyDisplay');
        if (qtyDisp) qtyDisp.textContent = formatMoney(totalQty);

        const cntDisp = document.getElementById('previewTotalCountDisplay');
        if (cntDisp) cntDisp.textContent = totals.total_invoices;

        const amtDisp = document.getElementById('previewTotalAmountDisplay');
        if (amtDisp) amtDisp.textContent = formatMoney(totals.total_amount);

        const footInvs = document.getElementById('footerInvoicesCount');
        if (footInvs) footInvs.textContent = totals.total_invoices + ' Invoices';

        const footAmt = document.getElementById('footerTotalAmount');
        if (footAmt) footAmt.textContent = 'PKR ' + formatMoney(totals.total_amount);

        const footQty = document.getElementById('tableFootQty');
        if (footQty) footQty.textContent = formatMoney(totalQty);

        const footExcl = document.getElementById('tableFootExcl');
        if (footExcl) footExcl.textContent = formatMoney(totals.total_excl_tax);

        const footFixed = document.getElementById('tableFootFixed');
        if (footFixed && totals.total_fixed_notified != null) footFixed.textContent = formatMoney(totals.total_fixed_notified);

        const footTax = document.getElementById('tableFootTax');
        if (footTax) footTax.textContent = formatMoney(totals.total_tax);

        const footTotal = document.getElementById('tableFootTotal');
        if (footTotal) footTotal.textContent = formatMoney(totals.total_amount);

        // Update Difference Card
        const diffCard = document.getElementById('previewDiffCard');
        const diffDisp = document.getElementById('previewDiffDisplay');
        if (diffCard && diffDisp) {
            if (Math.abs(diff) < 0.0001) {
                diffCard.className = 'p-2 border rounded bg-success-transparent border-success';
                diffDisp.className = 'fs-15 fw-bold text-success';
                diffDisp.innerHTML = '<i class="ri-checkbox-circle-line me-1"></i> 0.00 (Balanced)';
            } else if (diff > 0) {
                diffCard.className = 'p-2 border rounded bg-warning-transparent border-warning';
                diffDisp.className = 'fs-15 fw-bold text-warning';
                diffDisp.innerHTML = '<i class="ri-alert-line me-1"></i> -' + formatMoney(diff) + ' (Short)';
            } else {
                diffCard.className = 'p-2 border rounded bg-warning-transparent border-warning';
                diffDisp.className = 'fs-15 fw-bold text-warning';
                diffDisp.innerHTML = '<i class="ri-alert-line me-1"></i> +' + formatMoney(Math.abs(diff)) + ' (Over)';
            }
        }
    }

    // ── Themed Alert Modal Helper ────────────────────────────────────
    function showThemedAlert(title, message, type = 'error') {
        const titleEl = document.getElementById('themedAlertTitle');
        const msgEl = document.getElementById('themedAlertMessage');
        const iconBg = document.getElementById('themedAlertIconBg');
        const icon = document.getElementById('themedAlertIcon');

        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;

        if (type === 'error') {
            if (iconBg) iconBg.className = 'avatar avatar-xl bg-danger-transparent rounded-circle mx-auto mb-3';
            if (icon) icon.className = 'fas fa-times-circle fs-28 text-danger';
            if (msgEl) msgEl.className = 'fs-12 mb-3 text-danger';
        } else if (type === 'warning') {
            if (iconBg) iconBg.className = 'avatar avatar-xl bg-warning-transparent rounded-circle mx-auto mb-3';
            if (icon) icon.className = 'fas fa-exclamation-triangle fs-28 text-warning';
            if (msgEl) msgEl.className = 'fs-12 mb-3 text-dark';
        } else {
            if (iconBg) iconBg.className = 'avatar avatar-xl bg-info-transparent rounded-circle mx-auto mb-3';
            if (icon) icon.className = 'fas fa-info-circle fs-28 text-info';
            if (msgEl) msgEl.className = 'fs-12 mb-3 text-muted';
        }

        const modalEl = document.getElementById('themedAlertModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    // ── Confirm & Move to Live Invoices (with Difference Balance Guard) ──
    function commitBreakupInvoices() {
        const requestId = document.getElementById('form_request_id').value;
        if (!requestId) {
            showThemedAlert('No Batch Active', 'No preview batch active. Please generate a preview first.', 'warning');
            return;
        }

        // Calculate reconciliation difference between Target Qty and current sum of Qty in preview
        const targetQty = parseFloat(document.getElementById('modal_target_qty').value) || 0;
        let totalGenQty = 0;
        document.querySelectorAll('.preview-qty-input').forEach(inp => {
            totalGenQty += (parseFloat(inp.value) || 0);
        });

        // Round to 4 decimal places to avoid floating point precision artifacts
        const diff = Math.round((targetQty - totalGenQty) * 10000) / 10000;

        // If difference is significant (> 0.001), prompt Difference Warning Modal
        if (Math.abs(diff) > 0.001) {
            document.getElementById('diffWarnTargetQty').textContent = formatMoney(targetQty);
            document.getElementById('diffWarnGeneratedQty').textContent = formatMoney(totalGenQty);

            const alertBox = document.getElementById('diffWarnAlertBox');
            const noticeTitle = document.getElementById('diffWarnNoticeTitle');
            const noticeBody = document.getElementById('diffWarnNoticeBody');
            const diffValBadge = document.getElementById('diffWarnDifferenceVal');

            if (diff > 0) {
                // Short / Under-allocated
                diffValBadge.textContent = '+' + formatMoney(diff) + ' (Short)';
                diffValBadge.className = 'text-warning';
                alertBox.className = 'rollback-notice-card warning';
                noticeTitle.innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-1"></i> Under-Allocated Batch (Short by ' + formatMoney(diff) + ')';
                noticeBody.innerHTML = 'Your generated total quantity (<strong>' + formatMoney(totalGenQty) + '</strong>) is less than your target (<strong>' + formatMoney(targetQty) + '</strong>).<br>There is an unallocated difference of <strong class="text-danger">+' + formatMoney(diff) + '</strong>.<br><br>Do you want to proceed and save this partial batch, or adjust your invoice quantities?';
            } else {
                // Over-allocated
                diffValBadge.textContent = '-' + formatMoney(Math.abs(diff)) + ' (Over)';
                diffValBadge.className = 'text-danger';
                alertBox.className = 'rollback-notice-card danger';
                noticeTitle.innerHTML = '<i class="fas fa-exclamation-circle text-danger me-1"></i> Over-Allocated Batch (Exceeds by ' + formatMoney(Math.abs(diff)) + ')';
                noticeBody.innerHTML = 'Your generated total quantity (<strong>' + formatMoney(totalGenQty) + '</strong>) exceeds your target (<strong>' + formatMoney(targetQty) + '</strong>) by <strong class="text-danger">' + formatMoney(Math.abs(diff)) + '</strong>.<br><br>Do you want to proceed and save this batch with the excess quantity, or adjust your invoice quantities?';
            }

            const diffModal = new bootstrap.Modal(document.getElementById('differenceWarningModal'));
            diffModal.show();
            return;
        }

        // If perfectly balanced, execute save directly
        executeCommitBreakupInvoices(false);
    }

    function executeCommitBreakupInvoices(fromWarningModal) {
        if (fromWarningModal) {
            const warnEl = document.getElementById('differenceWarningModal');
            if (warnEl) {
                const warnModal = bootstrap.Modal.getInstance(warnEl);
                if (warnModal) warnModal.hide();
            }
        }

        const requestId = document.getElementById('form_request_id').value;
        const batchTitleInput = document.getElementById('commitBatchTitle');
        const batchTitle = batchTitleInput ? batchTitleInput.value : '';
        const btn = document.getElementById('btnCommitInvoices');
        const btnFooter = document.getElementById('btnFooterCommit');

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Moving to Invoices...';
        }
        if (btnFooter) {
            btnFooter.disabled = true;
            btnFooter.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Moving...';
        }

        fetch('{{ route("breakup-invoices.save") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                request_id: requestId,
                batch_title: batchTitle
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.status) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ri-check-double-line me-1"></i> Confirm & Move to Invoices';
                }
                if (btnFooter) {
                    btnFooter.disabled = false;
                    btnFooter.innerHTML = '<i class="ri-check-double-line me-1"></i> Confirm & Move to Invoices';
                }
                showThemedAlert('Commit Failed', data.message || 'Error saving invoices.', 'error');
                return;
            }

            // Close generator modal
            const genModalEl = document.getElementById('breakupGeneratorModal');
            if (genModalEl) {
                const genModal = bootstrap.Modal.getInstance(genModalEl);
                if (genModal) genModal.hide();
            }

            // Show themed success modal
            document.getElementById('commitSuccessMessage').textContent = data.message || 'Invoices have been saved and moved to your Invoices list.';
            const successModal = new bootstrap.Modal(document.getElementById('commitSuccessModal'));
            successModal.show();
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-check-double-line me-1"></i> Confirm & Move to Invoices';
            }
            if (btnFooter) {
                btnFooter.disabled = false;
                btnFooter.innerHTML = '<i class="ri-check-double-line me-1"></i> Confirm & Move to Invoices';
            }
            showThemedAlert('Server Error', 'Failed to communicate with the server: ' + err, 'error');
        });
    }

    // ── Enhanced Rollback Confirmation Modal (Same as Bulk Invoices) ──────
    function confirmBatchRollback(requestId, title, totalCount, postedCount) {
        postedCount = parseInt(postedCount) || 0;
        totalCount = parseInt(totalCount) || 0;
        const unpostedCount = Math.max(0, totalCount - postedCount);

        document.getElementById('rollbackBatchTitle').textContent = title || 'Untitled Batch';
        document.getElementById('rollbackBatchReqBadge').textContent = requestId ? (requestId.substring(0, 8) + '...' + requestId.substring(requestId.length - 4)) : '';
        document.getElementById('rollbackStatTotal').textContent = totalCount;
        document.getElementById('rollbackStatPosted').textContent = postedCount;
        document.getElementById('rollbackStatUnposted').textContent = unpostedCount;

        document.getElementById('rollbackBatchForm').action = '{{ url("breakup-invoices/rollback") }}/' + requestId;

        const iconBadge = document.getElementById('rollbackHeaderIconBadge');
        const headerIcon = document.getElementById('rollbackHeaderIcon');
        const headerTitle = document.getElementById('rollbackModalHeaderTitle');
        const headerSubtitle = document.getElementById('rollbackModalHeaderSubtitle');

        const alertBox = document.getElementById('rollbackAlertBox');
        const noticeTitle = document.getElementById('rollbackNoticeTitle');
        const noticeBody = document.getElementById('rollbackNoticeBody');
        const submitBtn = document.getElementById('rollbackSubmitBtn');

        if (totalCount > 0 && unpostedCount === 0) {
            // CASE 1: All invoices already posted to FBR — BLOCK
            iconBadge.className = 'rollback-icon-circle secondary';
            headerIcon.className = 'fas fa-lock';
            headerTitle.textContent = 'Rollback Blocked';
            headerSubtitle.textContent = 'FBR submitted invoices cannot be rolled back';

            alertBox.className = 'rollback-notice-card danger';
            noticeTitle.innerHTML = '<i class="fas fa-ban text-danger"></i> <span style="color: #991b1b;">Rollback Prohibited</span>';
            noticeBody.innerHTML = 'All <strong>' + totalCount + '</strong> invoice(s) in this batch have already been submitted to FBR and cannot be deleted via batch rollback.<br><small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i> If you need to void an FBR-submitted invoice, use the Credit Note / Cancel feature in the Invoices screen.</small>';

            submitBtn.disabled = true;
            submitBtn.className = 'btn btn-sm btn-secondary px-3 py-1-5 fs-12 fw-semibold';
            submitBtn.innerHTML = '<i class="fas fa-lock me-1"></i> Cannot Rollback';
        } else if (postedCount > 0) {
            // CASE 2: Partial Rollback — Preserves posted, deletes unposted
            iconBadge.className = 'rollback-icon-circle warning';
            headerIcon.className = 'fas fa-shield-alt';
            headerTitle.textContent = 'Partial Batch Rollback';
            headerSubtitle.textContent = 'Preserves FBR invoices and deletes unposted';

            alertBox.className = 'rollback-notice-card warning';
            noticeTitle.innerHTML = '<i class="fas fa-shield-alt text-warning"></i> <span style="color: #92400e;">FBR Invoices Protected (' + postedCount + ' Posted)</span>';
            noticeBody.innerHTML = '<strong>' + postedCount + ' out of ' + totalCount + '</strong> invoices have already been submitted to FBR and will remain <span class="badge bg-success text-white fw-semibold">Safely Preserved</span>.<br><br>Do you want to proceed with rolling back the remaining <strong class="text-danger">' + unpostedCount + '</strong> unposted invoice(s)?';

            submitBtn.disabled = false;
            submitBtn.className = 'btn btn-sm btn-rollback px-3 py-1-5 fs-12 fw-semibold shadow-sm d-inline-flex align-items-center gap-1';
            submitBtn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Rollback (' + unpostedCount + ' Unposted Invoices)';
        } else if (totalCount === 0) {
            // CASE 3: Empty Batch — Remove orphaned record
            iconBadge.className = 'rollback-icon-circle info';
            headerIcon.className = 'fas fa-info-circle';
            headerTitle.textContent = 'Remove Empty Batch';
            headerSubtitle.textContent = 'Clean up orphaned batch metadata';

            alertBox.className = 'rollback-notice-card info';
            noticeTitle.innerHTML = '<i class="fas fa-info-circle text-success"></i> <span style="color: #166534;">Clean Up Empty Batch</span>';
            noticeBody.innerHTML = 'No live invoices remain under this batch. This action will cleanly remove the empty batch request record from the database.';

            submitBtn.disabled = false;
            submitBtn.className = 'btn btn-sm btn-rollback px-3 py-1-5 fs-12 fw-semibold shadow-sm d-inline-flex align-items-center gap-1';
            submitBtn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Remove Empty Batch';
        } else {
            // CASE 4: Full Rollback (0 posted, all unposted)
            iconBadge.className = 'rollback-icon-circle danger';
            headerIcon.className = 'fas fa-trash-alt';
            headerTitle.textContent = 'Rollback Batch Invoices';
            headerSubtitle.textContent = 'Archived to history and permanently removed';

            alertBox.className = 'rollback-notice-card danger';
            noticeTitle.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i> <span style="color: #991b1b;">Confirm Batch Removal</span>';
            noticeBody.innerHTML = 'Are you sure? This will delete all <strong>' + totalCount + '</strong> unposted invoice(s) in this batch and remove the batch request.<br><small class="text-muted mt-1 d-block"><i class="fas fa-history me-1"></i> All deleted invoices and line items are safely recorded in the audit history table.</small>';

            submitBtn.disabled = false;
            submitBtn.className = 'btn btn-sm btn-rollback px-3 py-1-5 fs-12 fw-semibold shadow-sm d-inline-flex align-items-center gap-1';
            submitBtn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Confirm Rollback';
        }

        const modal = new bootstrap.Modal(document.getElementById('rollbackBatchModal'));
        modal.show();
    }

    // Alias for backwards compatibility
    function confirmRollbackBatch(requestId, title, count) {
        confirmBatchRollback(requestId, title, count, 0);
    }

    function formatMoney(num) {
        return (parseFloat(num) || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
</script>
@endsection

