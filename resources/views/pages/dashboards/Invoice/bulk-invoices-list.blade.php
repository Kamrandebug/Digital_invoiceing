{{-- Bulk Invoicing Requests List --}}

@extends('layouts.master')

@section('styles')
<style>
    /* Action column */
    #bulkInvoicesTable th.action-col,
    #bulkInvoicesTable td:last-child {
        min-width: 9.5rem;
        max-width: none !important;
        width: auto !important;
        text-align: end;
        vertical-align: middle;
    }
    #bulkInvoicesTable .btn-group.btn-listing {
        position: relative;
    }
    #bulkInvoicesTable .dropdown-menu {
        z-index: 1060;
    }

    /* ── Compact Table Styling — mirrors #invoicesTable exactly ── */
    #bulkInvoicesTable {
        font-size: 12.5px;
        margin-bottom: 0;
    }
    #bulkInvoicesTable thead th {
        padding: 0.35rem 0.55rem !important;
        font-size: 12px;
        font-weight: 600;
        vertical-align: middle;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    #bulkInvoicesTable tbody td {
        padding: 0.28rem 0.55rem !important;
        vertical-align: middle;
        line-height: 1.3;
        border-bottom: 1px solid #f1f5f9;
    }
    #bulkInvoicesTable .badge {
        padding: 2px 7px;
        font-size: 10.5px;
        font-weight: 500;
    }
    /* Action buttons — same size as invoice list */
    #bulkInvoicesTable .btn-listing .btn {
        padding: 2px 8px !important;
        font-size: 11.5px !important;
        line-height: 1.35 !important;
    }
    #bulkInvoicesTable .btn-listing .btn i {
        font-size: 11px;
    }

    /* ── Rollback Confirmation Modal Premium UI ── */
    #rollbackBatchModal .modal-content {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
        font-weight: 500;
        transition: all 0.2s ease;
    }
    #rollbackBatchModal .btn-cancel:hover {
        background-color: #f1f5f9;
        border-color: #94a3b8;
        color: #0f172a;
    }
    #rollbackBatchModal .btn-rollback {
        background-color: #dc2626 !important;
        border-color: #dc2626 !important;
        color: #ffffff !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
    }
    #rollbackBatchModal .btn-rollback:hover {
        background-color: #b91c1c !important;
        border-color: #b91c1c !important;
        box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);
    }
</style>
@endsection

@section('content')

{{-- Page Header --}}
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Bulk Invoicing Requests</h1>
        <div class="d-flex float-end">
            <button type="button" class="btn btn-sm btn-primary btn-wave waves-light me-2" data-bs-toggle="modal" data-bs-target="#bulkInvoiceImportModal">
                <i class="fas fa-file-import align-middle me-1"></i> Import Invoices
            </button>
            <div class="dropdown">
                <button class="btn btn-icon btn-secondary-light btn-sm btn-wave waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('breakup-invoices.index') }}"><i class="ri-split-cells-horizontal me-1 text-primary"></i> Breakup Invoices</a></li>
                    <li><a class="dropdown-item" href="{{ route('bulk-invoices.import-template') }}"><i class="fas fa-download me-1 text-primary"></i> Download Template</a></li>
                    <li><a class="dropdown-item" href="{{ route('invoices.index') }}"><i class="fas fa-file-invoice me-1 text-primary"></i> View All Invoices</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Messages --}}
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

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card custom-card border-0 shadow-sm mb-0">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted fs-12 mb-1">Total Batches</span>
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
                        <span class="d-block text-muted fs-12 mb-1">Total Batch Value (Net)</span>
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

{{-- Filter Card --}}
<div class="card custom-card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form action="{{ route('bulk-invoices.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fs-12 text-muted mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-search-line"></i></span>
                    <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Title, Request ID, File..." value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-12 text-muted mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $filters['start_date'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fs-12 text-muted mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $filters['end_date'] ?? '' }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="ri-filter-line me-1"></i> Filter</button>
                <a href="{{ route('bulk-invoices.index') }}" class="btn btn-sm btn-light" title="Reset Filters"><i class="ri-refresh-line"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Main Table Card --}}
<div class="col-xs-12">
    <div class="card custom-card">
        {{-- Card header — matches invoice list tab-header style --}}
        <div class="card-header invoice-card-tab-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold fs-14 text-dark">Bulk Invoicing Requests</span>
                <span class="badge bg-primary-transparent text-primary fs-11">{{ $batches->total() }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap listing-table" id="bulkInvoicesTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Request Title / Source</th>
                            <th>Request ID</th>
                            <th>Source Type</th>
                            <th class="text-center">Invoices</th>
                            <th class="text-center">Items</th>
                            <th class="text-end">Total Ex-Tax (PKR)</th>
                            <th class="text-end">Total GST (PKR)</th>
                            <th class="text-end">Total Net (PKR)</th>
                            <th>Created Date</th>
                            <th class="action-col text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $index => $batch)
                        <tr>
                            <td class="text-muted">{{ $batches->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $batch->Title }}</div>
                                @if(!empty($batch->SourceReference))
                                    <small class="text-muted"><i class="fas fa-file-excel text-success me-1"></i> {{ $batch->SourceReference }}</small>
                                @endif
                            </td>
                            <td>
                                <code class="fs-11 text-secondary">{{ substr($batch->RequestID, 0, 8) }}...{{ substr($batch->RequestID, -4) }}</code>
                            </td>
                            <td>
                                @if($batch->SourceType === 'EXCEL_IMPORT')
                                    <span class="badge bg-primary-transparent text-primary"><i class="fas fa-file-import me-1"></i> Excel Import</span>
                                @else
                                    <span class="badge bg-info-transparent text-info"><i class="fas fa-layer-group me-1"></i> Purchase Breakup</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-transparent text-success fw-semibold fs-11" title="Live Invoices">{{ $batch->LiveTotalCount ?? $batch->TotalInvoices }}</span>
                                @if(($batch->LivePostedCount ?? 0) > 0)
                                    <small class="d-block text-success fs-10 mt-1" title="Posted to FBR">
                                        <i class="fas fa-check-circle"></i> {{ $batch->LivePostedCount }} posted
                                    </small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark fw-medium fs-11">{{ $batch->TotalItems }}</span>
                            </td>
                            <td class="text-end text-muted font-monospace">
                                {{ number_format($batch->TotalExTax ?? ($batch->TotalAmount - $batch->TotalTax), 2) }}
                            </td>
                            <td class="text-end text-muted font-monospace">
                                {{ number_format($batch->TotalTax, 2) }}
                            </td>
                            <td class="text-end fw-bold text-dark font-monospace">
                                {{ number_format($batch->TotalAmount, 2) }}
                            </td>
                            <td>
                                <div class="fs-12 text-dark">{{ date('d M Y', strtotime($batch->CreatedOn)) }}</div>
                                <small class="text-muted">{{ date('h:i A', strtotime($batch->CreatedOn)) }}</small>
                            </td>
                            <td class="action-col text-end">
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
                                                <a class="dropdown-item" href="{{ route('invoices.index', ['search' => $batch->RequestID, 'fbr_status' => 'all']) }}" target="_blank">
                                                    <i class="fas fa-external-link-alt me-2 text-secondary"></i> Open in Invoices Page
                                                </a>
                                            </li>
                                            @php
                                                $liveTotal = (int) ($batch->LiveTotalCount ?? $batch->TotalInvoices);
                                                $livePosted = (int) ($batch->LivePostedCount ?? $batch->FbrPostedCount ?? 0);
                                            @endphp
                                            @if($liveTotal > 0 && $livePosted >= $liveTotal)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <span class="dropdown-item text-muted disabled" style="cursor: not-allowed;" title="All invoices in this batch have already been submitted to FBR and cannot be deleted">
                                                        <i class="fas fa-lock me-2 text-muted"></i> Rollback Blocked (FBR Posted)
                                                    </span>
                                                </li>
                                            @else
                                                <li><hr class="dropdown-divider"></li>
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
                            <td colspan="11" class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-2">No bulk invoicing requests found.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkInvoiceImportModal">
                                    <i class="fas fa-file-import me-1"></i> Import Invoices
                                </button>
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

{{-- ── Shared Batch Invoices Modal (View invoices against this request) ── --}}
@include('pages.dashboards.Invoice._batch-invoices-view-modal')

{{-- Bulk Invoice Import Modal --}}
<div class="modal fade" id="bulkInvoiceImportModal" tabindex="-1" aria-labelledby="bulkInvoiceImportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('bulk-invoices.import') }}" method="POST" enctype="multipart/form-data" id="bulkInvoiceImportForm" onsubmit="handleBulkInvoiceImportSubmit(this)">
                @csrf
                <div class="modal-header border-bottom py-2 px-3 bg-light">
                    <h6 class="modal-title fw-semibold text-primary mb-0" id="bulkInvoiceImportModalLabel" style="font-size: 15px;">
                        <i class="fas fa-file-import me-2"></i> Import Bulk Invoices
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    {{-- Download Sample Template Strip --}}
                    <div class="d-flex align-items-center justify-content-between p-2 mb-3 bg-light border rounded">
                        <div>
                            <div class="fw-semibold text-dark fs-13"><i class="fas fa-file-excel text-success me-1"></i> Sample Template</div>
                            <small class="text-muted">Download pre-formatted 21-column Excel template</small>
                        </div>
                        <a href="{{ route('bulk-invoices.import-template') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i> Download Template
                        </a>
                    </div>

                    {{-- Batch Title Input (Optional) --}}
                    <div class="mb-3">
                        <label for="batch_title" class="form-label fw-semibold fs-13 text-dark">
                            Batch Title <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <input type="text" 
                               name="batch_title" 
                               id="batch_title" 
                               class="form-control form-control-sm" 
                               placeholder="e.g. September Stock Clearance Invoices">
                    </div>

                    {{-- File Input --}}
                    <div class="mb-0">
                        <label for="bulk_invoice_import_file" class="form-label fw-semibold fs-13 text-dark">
                            Select File <span class="text-danger">*</span>
                        </label>
                        <input type="file" 
                               name="import_file" 
                               id="bulk_invoice_import_file" 
                               class="form-control form-control-sm" 
                               accept=".xlsx, .xls, .csv, text/csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" 
                               required>
                        <small class="text-muted d-block mt-1">Supported formats: <strong>.xlsx, .xls, .csv</strong> (Max: 10MB)</small>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 border-top bg-light">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitBulkInvoiceImport">
                        <i class="fas fa-upload me-1"></i> <span id="btnSubmitBulkInvoiceImportText">Upload & Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Rollback Confirmation Modal (Enhanced UI/UX) --}}
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

@endsection

@section('scripts')
<script>
let currentBatchRequestId = '';
let currentBatchTab = 'all';

function handleBulkInvoiceImportSubmit(form) {
    const btn = document.getElementById('btnSubmitBulkInvoiceImport');
    const txt = document.getElementById('btnSubmitBulkInvoiceImportText');
    if (btn) {
        btn.disabled = true;
        if (txt) txt.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importing...';
    }
}

function confirmBatchRollback(requestId, title, totalCount, postedCount) {
    postedCount = parseInt(postedCount) || 0;
    totalCount = parseInt(totalCount) || 0;
    const unpostedCount = Math.max(0, totalCount - postedCount);

    document.getElementById('rollbackBatchTitle').textContent = title || 'Untitled Batch';
    document.getElementById('rollbackBatchReqBadge').textContent = requestId ? (requestId.substring(0, 8) + '...' + requestId.substring(requestId.length - 4)) : '';
    document.getElementById('rollbackStatTotal').textContent = totalCount;
    document.getElementById('rollbackStatPosted').textContent = postedCount;
    document.getElementById('rollbackStatUnposted').textContent = unpostedCount;

    document.getElementById('rollbackBatchForm').action = '/bulk-invoices/rollback/' + requestId;

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
        // CASE 2: Partial Rollback (Option B) — Preserves posted, deletes unposted
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
        submitBtn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Rollback All (' + totalCount + ' Invoices)';
    }

    const modal = new bootstrap.Modal(document.getElementById('rollbackBatchModal'));
    modal.show();
}

// Auto-dismiss success alert after 5s (errors stay until closed)
document.addEventListener('DOMContentLoaded', function() {
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
@endsection
