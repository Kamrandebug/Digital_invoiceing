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

    /* ── Batch Invoices Modal (View Invoices UI Aligned with Portal Theme) ── */
    #batchInvoicesModal .modal-content {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    #batchInvoicesModal .modal-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.65rem 1.15rem;
    }
    #batchInvoicesModal .modal-footer {
        background-color: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 0.55rem 1.15rem;
    }
    #batchInvoicesDetailTable {
        font-size: 12.5px;
        margin-bottom: 0;
        font-family: inherit;
    }
    #batchInvoicesDetailTable thead th {
        padding: 0.35rem 0.55rem !important;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        white-space: nowrap;
    }
    #batchInvoicesDetailTable tbody td {
        padding: 0.28rem 0.55rem !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        line-height: 1.3;
        font-size: 12px;
    }
    #batchInvoicesDetailTable .batch-invoice-row {
        transition: background-color 0.15s ease;
    }
    #batchInvoicesDetailTable .batch-invoice-row:hover {
        background-color: #f8fafc;
    }
    .toggle-items-btn {
        width: 22px;
        height: 22px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #475569;
        background-color: #f1f5f9;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .toggle-items-btn:hover {
        background-color: rgba(var(--primary-rgb, 111, 66, 193), 0.12);
        color: var(--primary-color, #6f42c1);
        border-color: rgba(var(--primary-rgb, 111, 66, 193), 0.3);
    }
    .toggle-items-btn.active {
        background-color: var(--primary-color, #6f42c1);
        color: #ffffff;
        border-color: var(--primary-color, #6f42c1);
    }
    .batch-items-container {
        background-color: #f8fafc;
        border-top: 1px dashed #cbd5e1;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 14px;
    }
    .batch-inner-table {
        font-size: 12px;
        background-color: #ffffff;
        margin-bottom: 0;
        font-family: inherit;
    }
    .batch-inner-table thead th {
        background-color: #f8fafc !important;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        padding: 0.35rem 0.55rem !important;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        white-space: nowrap;
    }
    .batch-inner-table tbody td {
        padding: 0.28rem 0.55rem !important;
        font-size: 11.5px;
        vertical-align: middle;
        border-color: #f1f5f9;
        white-space: nowrap;
    }

    /* ── Modal Tabs Styling (Mirrors Invoice Listing Tabs) ── */
    .batch-modal-tabs-container {
        background-color: transparent;
        border-bottom: 1px solid #e2e8f0;
        padding: 0;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    .modal-invoice-tabs {
        border-bottom: none;
        margin-bottom: 0;
        display: flex;
        gap: 6px;
        flex-wrap: nowrap;
        overflow-x: auto;
    }
    .modal-invoice-tabs .nav-link {
        border: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        padding: 6px 13px;
        font-size: 12px;
        font-weight: 500;
        color: #475569;
        background: #ffffff;
        margin-bottom: -1px;
        transition: all 0.15s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .modal-invoice-tabs .nav-link:hover {
        color: #1e293b;
        background: #f8fafc;
        border-color: #cbd5e1;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }
    .modal-invoice-tabs .nav-link.active {
        background: #ffffff;
        border-color: #cbd5e1 #cbd5e1 #ffffff;
        border-bottom: 1px solid #ffffff !important;
        font-weight: 600;
        position: relative;
        z-index: 2;
        box-shadow: 0 -2px 6px rgba(0,0,0,0.04);
    }
    .modal-invoice-tabs .nav-link .badge {
        font-size: 10.5px;
        padding: 2px 7px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.15s ease-in-out;
    }
    .modal-invoice-tabs .tab-not-posted { border-color: #dbeafe; }
    .modal-invoice-tabs .tab-not-posted:hover { border-color: #60a5fa; }
    .modal-invoice-tabs .tab-not-posted .tab-icon { color: #0284c7; }
    .modal-invoice-tabs .tab-not-posted .tab-badge { background-color: #e0f2fe; color: #0369a1; }
    .modal-invoice-tabs .tab-not-posted.active {
        color: #0284c7;
        border-top: 3px solid #0284c7 !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-not-posted.active .tab-badge {
        background-color: #0284c7;
        color: #ffffff;
    }

    .modal-invoice-tabs .tab-posted { border-color: #dcfce7; }
    .modal-invoice-tabs .tab-posted:hover { border-color: #4ade80; }
    .modal-invoice-tabs .tab-posted .tab-icon { color: #16a34a; }
    .modal-invoice-tabs .tab-posted .tab-badge { background-color: #dcfce7; color: #15803d; }
    .modal-invoice-tabs .tab-posted.active {
        color: #16a34a;
        border-top: 3px solid #16a34a !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-posted.active .tab-badge {
        background-color: #16a34a;
        color: #ffffff;
    }

    .modal-invoice-tabs .tab-error { border-color: #fee2e2; }
    .modal-invoice-tabs .tab-error:hover { border-color: #f87171; }
    .modal-invoice-tabs .tab-error .tab-icon { color: #dc2626; }
    .modal-invoice-tabs .tab-error .tab-badge { background-color: #fee2e2; color: #b91c1c; }
    .modal-invoice-tabs .tab-error.active {
        color: #dc2626;
        border-top: 3px solid #dc2626 !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-error.active .tab-badge {
        background-color: #dc2626;
        color: #ffffff;
    }

    .modal-invoice-tabs .tab-cancel { border-color: #ffedd5; }
    .modal-invoice-tabs .tab-cancel:hover { border-color: #fb923c; }
    .modal-invoice-tabs .tab-cancel .tab-icon { color: #ea580c; }
    .modal-invoice-tabs .tab-cancel .tab-badge { background-color: #ffedd5; color: #c2410c; }
    .modal-invoice-tabs .tab-cancel.active {
        color: #ea580c;
        border-top: 3px solid #ea580c !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-cancel.active .tab-badge {
        background-color: #ea580c;
        color: #ffffff;
    }

    .modal-invoice-tabs .tab-all { border-color: #e2e8f0; }
    .modal-invoice-tabs .tab-all:hover { border-color: #94a3b8; }
    .modal-invoice-tabs .tab-all .tab-icon { color: #64748b; }
    .modal-invoice-tabs .tab-all .tab-badge { background-color: #f1f5f9; color: #475569; }
    .modal-invoice-tabs .tab-all.active {
        color: #1e293b;
        border-top: 3px solid #475569 !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-all.active .tab-badge {
        background-color: #475569;
        color: #ffffff;
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
                                <span class="badge bg-success-transparent text-success fw-semibold fs-11">{{ $batch->TotalInvoices }}</span>
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
                                                $canRollback = ($liveTotal === 0 || $livePosted < $liveTotal);
                                            @endphp
                                            @if($canRollback)
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

{{-- ── Batch Invoices Modal (View invoices against this request) ── --}}
<div class="modal fade" id="batchInvoicesModal" tabindex="-1" aria-labelledby="batchInvoicesModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div>
                        <h6 class="modal-title fw-semibold text-dark mb-0 fs-14 d-flex align-items-center" id="batchInvoicesModalLabel">
                            <i class="ri-file-list-3-line text-primary me-2 fs-16"></i> <span id="batchModalTitle">Batch Invoices</span>
                        </h6>
                        <div class="mt-1 d-flex align-items-center gap-1">
                            <span class="badge bg-light text-muted border font-monospace fs-10">Request ID: <span id="batchModalRequestId"></span></span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-transparent text-primary fw-semibold fs-11 px-2 py-1" id="batchModalInvoicesPill">0 Invoices</span>
                    <span class="badge bg-success-transparent text-success fw-semibold fs-11 px-2 py-1" id="batchModalAmountPill">PKR 0.00</span>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                {{-- Quick search & helper text in modal --}}
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-search-line"></i></span>
                        <input type="text" id="batchInvoiceFilterInput" class="form-control border-start-0" placeholder="Filter invoices in this batch..." onkeyup="filterBatchInvoicesTable()">
                    </div>
                    <div class="fs-11 text-muted d-flex align-items-center gap-1" id="batchModalSummaryText">
                        <i class="ri-information-line text-primary fs-13"></i> Showing all invoices in this batch. Click <i class="ri-arrow-down-s-line text-primary fw-bold"></i> to view line items.
                    </div>
                </div>

                {{-- Tabs Strip (Under Search, Above Table) --}}
                <div class="batch-modal-tabs-container">
                    <ul class="nav modal-invoice-tabs" id="batchModalStatusTabs">
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-not-posted" data-tab-status="not_posted" onclick="switchBatchModalTab('not_posted')">
                                <i class="fas fa-clock tab-icon me-1"></i> Not Posted
                                <span class="badge tab-badge" id="batchTabCountNotPosted">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-posted" data-tab-status="posted" onclick="switchBatchModalTab('posted')">
                                <i class="fas fa-check-circle tab-icon me-1"></i> Successfully Posted
                                <span class="badge tab-badge" id="batchTabCountPosted">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-error" data-tab-status="error" onclick="switchBatchModalTab('error')">
                                <i class="fas fa-exclamation-triangle tab-icon me-1"></i> FBR Error
                                <span class="badge tab-badge" id="batchTabCountError">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-cancel" data-tab-status="cancelled" onclick="switchBatchModalTab('cancelled')">
                                <i class="fas fa-ban tab-icon me-1"></i> FBR Cancelled
                                <span class="badge tab-badge" id="batchTabCountCancelled">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-all active" data-tab-status="all" onclick="switchBatchModalTab('all')">
                                <i class="fas fa-list tab-icon me-1"></i> All Invoices
                                <span class="badge tab-badge" id="batchTabCountAll">0</span>
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- Loading Spinner --}}
                <div id="batchModalLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading batch invoices...</span>
                    </div>
                    <p class="text-muted mt-2 fs-12">Loading invoices and line items...</p>
                </div>

                {{-- Invoices Table Container --}}
                <div id="batchModalTableContainer" class="table-responsive rounded border d-none">
                    <table class="table text-nowrap listing-table table-hover align-middle mb-0" id="batchInvoicesDetailTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th>Invoice Date</th>
                                <th>Customer</th>
                                <th>Doc No</th>
                                <th class="text-end">Amount (PKR)</th>
                                <th class="text-end">GST (PKR)</th>
                                <th class="text-end">Total (PKR)</th>
                                <th class="text-center">FBR Response</th>
                                <th class="text-center" style="width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="batchInvoicesTableBody">
                            {{-- Populated via AJAX --}}
                        </tbody>
                    </table>
                    <div id="batchModalEmptyState" class="text-center py-5 d-none">
                        <i class="fas fa-search fa-2x text-muted mb-2 d-block"></i>
                        <span class="text-muted fs-12" id="batchModalEmptyMsg">No invoices match the current filter.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <a href="#" id="batchModalFullViewBtn" target="_blank" class="btn btn-sm btn-outline-primary btn-wave waves-light">
                    <i class="ri-external-link-line me-1"></i> Open in Full Invoices List
                </a>
                <button type="button" class="btn btn-sm btn-light border btn-wave waves-light" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

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

{{-- Rollback Confirmation Modal --}}
<div class="modal fade" id="rollbackBatchModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form id="rollbackBatchForm" method="POST" action="">
                @csrf
                <div class="modal-header border-bottom py-2 px-3 bg-danger-transparent">
                    <h6 class="modal-title fw-semibold text-danger mb-0 fs-14">
                        <i class="fas fa-exclamation-triangle me-1"></i> Rollback Batch
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 text-center">
                    <p class="fs-13 text-dark mb-2">Are you sure you want to rollback batch:</p>
                    <strong class="d-block text-danger mb-2" id="rollbackBatchTitle"></strong>
                    <div class="alert alert-warning py-2 px-2 fs-11 text-start mb-0" id="rollbackAlertBox">
                        <i class="fas fa-info-circle me-1"></i> This will delete all <strong id="rollbackInvoiceCount"></strong> invoices associated with this request.
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 border-top bg-light">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger" id="rollbackSubmitBtn">Confirm Delete</button>
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

    document.getElementById('rollbackBatchTitle').textContent = title;
    document.getElementById('rollbackBatchForm').action = '/bulk-invoices/rollback/' + requestId;

    const alertBox = document.getElementById('rollbackAlertBox');
    const submitBtn = document.getElementById('rollbackSubmitBtn');

    if (totalCount > 0 && unpostedCount === 0) {
        alertBox.className = 'alert alert-danger py-2 px-2 fs-11 text-start mb-0';
        alertBox.innerHTML = '<i class="fas fa-ban me-1"></i> All <strong>' + totalCount + '</strong> invoice(s) in this batch have already been submitted to FBR and cannot be deleted.';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Cannot Rollback';
    } else if (postedCount > 0) {
        alertBox.className = 'alert alert-warning py-2 px-2 fs-11 text-start mb-0';
        alertBox.innerHTML = '<i class="fas fa-info-circle me-1"></i> <strong>' + postedCount + '</strong> invoice(s) are posted to FBR and will remain preserved.<br>Only the remaining <strong>' + unpostedCount + '</strong> unposted invoice(s) will be deleted.';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Rollback (' + unpostedCount + ' Unposted)';
    } else {
        alertBox.className = 'alert alert-warning py-2 px-2 fs-11 text-start mb-0';
        alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> This will delete all <strong>' + totalCount + '</strong> invoices and remove this batch request.';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Confirm Delete';
    }

    const modal = new bootstrap.Modal(document.getElementById('rollbackBatchModal'));
    modal.show();
}

/**
 * Open Batch Invoices Modal & Load Invoices via AJAX
 */
function viewBatchInvoices(requestId, title) {
    currentBatchRequestId = requestId;
    currentBatchTab = 'all';

    document.getElementById('batchModalTitle').textContent = title || 'Batch Invoices';
    document.getElementById('batchModalRequestId').textContent = requestId;
    document.getElementById('batchInvoiceFilterInput').value = '';

    // Reset tab active states
    document.querySelectorAll('#batchModalStatusTabs .nav-link').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab-status') === 'all');
    });

    updateFullViewLink('all');

    const loader = document.getElementById('batchModalLoader');
    const container = document.getElementById('batchModalTableContainer');
    const tbody = document.getElementById('batchInvoicesTableBody');
    const emptyState = document.getElementById('batchModalEmptyState');

    loader.classList.remove('d-none');
    container.classList.add('d-none');
    if (emptyState) emptyState.classList.add('d-none');
    tbody.innerHTML = '';

    const modal = new bootstrap.Modal(document.getElementById('batchInvoicesModal'));
    modal.show();

    fetch('/bulk-invoices/' + requestId + '/invoices')
        .then(res => res.json())
        .then(data => {
            loader.classList.add('d-none');
            container.classList.remove('d-none');

            if (!data.status || data.status !== 'success') {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-danger">' + (data.message || 'Failed to load invoices.') + '</td></tr>';
                return;
            }

            const batch = data.batch || {};
            const invoices = data.invoices || [];
            const counts = data.counts || {
                all: invoices.length,
                not_posted: 0,
                posted: 0,
                error: 0,
                cancelled: 0
            };

            // Update Counts on Badges
            document.getElementById('batchTabCountNotPosted').textContent = counts.not_posted ?? 0;
            document.getElementById('batchTabCountPosted').textContent = counts.posted ?? 0;
            document.getElementById('batchTabCountError').textContent = counts.error ?? 0;
            document.getElementById('batchTabCountCancelled').textContent = counts.cancelled ?? 0;
            document.getElementById('batchTabCountAll').textContent = counts.all ?? invoices.length;

            const liveTotalInvoices = (data.summary && data.summary.total_invoices !== undefined) ? data.summary.total_invoices : invoices.length;
            const liveTotalAmount = (data.summary && data.summary.total_amount !== undefined) ? data.summary.total_amount : invoices.reduce((acc, inv) => acc + (parseFloat(inv.TotalValues) || 0), 0);

            document.getElementById('batchModalInvoicesPill').innerHTML = '<i class="ri-file-text-line me-1"></i> ' + liveTotalInvoices + ' Invoices';
            document.getElementById('batchModalAmountPill').innerHTML = '<i class="ri-money-dollar-circle-line me-1"></i> PKR ' + Number(liveTotalAmount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

            if (invoices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No invoices found for this batch.</td></tr>';
                return;
            }

            let html = '';
            invoices.forEach((inv, idx) => {
                const statusKey = inv.status_key || 'not_posted';
                const isPosted = inv.FbrTransactionID && inv.FbrTransactionID.trim() !== '';
                let fbrStatusBadge = '';

                if (statusKey === 'posted') {
                    fbrStatusBadge = '<span class="badge rounded-pill bg-success-subtle text-success" style="font-size:11px; padding:5px 10px;"><i class="fas fa-check-circle me-1"></i>Posted</span>';
                } else if (statusKey === 'error') {
                    fbrStatusBadge = '<span class="badge rounded-pill bg-danger-subtle text-danger" style="font-size:11px; padding:5px 10px;" title="FBR Error"><i class="fas fa-exclamation-triangle me-1"></i>FBR Error</span>';
                } else if (statusKey === 'cancelled') {
                    fbrStatusBadge = '<span class="badge rounded-pill bg-warning-subtle text-warning" style="font-size:11px; padding:5px 10px;"><i class="fas fa-ban me-1"></i>FBR Cancelled</span>';
                } else {
                    fbrStatusBadge = '<span class="badge rounded-pill bg-light text-muted">Not Posted</span>';
                }

                const customerDisplay = inv.CustomerName || inv.CustomerNTN || inv.CustomerCode || 'Walk-In';
                const customerCodeSubtitle = inv.CustomerCode ? '<small class="text-muted font-monospace fs-10 d-block">' + inv.CustomerCode + '</small>' : '';

                html += `
                <tr class="batch-invoice-row" id="inv-row-${inv.HeaderID}" data-status="${statusKey}">
                    <td class="text-center">
                        <button type="button" class="btn btn-xs toggle-items-btn" id="toggle-btn-${inv.HeaderID}" onclick="toggleBatchInvoiceItems(${inv.HeaderID})" title="Toggle Line Items">
                            <i class="ri-arrow-right-s-line fs-14" id="toggle-icon-${inv.HeaderID}"></i>
                        </button>
                    </td>
                    <td><span class="fs-12 text-dark font-monospace">${inv.InvoiceDate || ''}</span></td>
                    <td>
                        <div class="fw-semibold text-dark fs-12">${customerDisplay}</div>
                        ${customerCodeSubtitle}
                    </td>
                    <td><span class="badge bg-primary-transparent text-primary font-monospace fw-bold fs-11">${inv.DocNo || ''}</span></td>
                    <td class="text-end font-monospace">${Number(inv.ValueSalesExcludingST || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    <td class="text-end font-monospace text-muted">${Number(inv.SalesTaxApplicable || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    <td class="text-end font-monospace fw-bold text-dark">${Number(inv.TotalValues || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    <td class="text-center">
                        ${fbrStatusBadge}
                        ${isPosted ? `<small class="d-block text-muted font-monospace fs-10 mt-1">TxID: <strong>${inv.FbrTransactionID}</strong></small>` : ''}
                    </td>
                    <td class="text-center">
                        <a href="/invoices/${inv.HeaderID}/print-view" target="_blank" class="btn btn-xs btn-icon btn-outline-light text-muted hover-primary" title="Print Invoice">
                            <i class="ri-printer-line fs-13"></i>
                        </a>
                    </td>
                </tr>
                <tr class="d-none batch-items-row" id="items-row-${inv.HeaderID}" data-parent-status="${statusKey}">
                    <td colspan="9" class="p-0 border-0">
                        <div class="batch-items-container">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary-transparent text-primary fw-semibold fs-11">
                                    <i class="ri-shopping-bag-3-line me-1"></i> Products & Services (${inv.items_count || (inv.items ? inv.items.length : 0)})
                                </span>
                                <span class="badge bg-white text-dark border font-monospace fs-11">
                                    <span class="text-muted fw-normal">Sale of Term:</span> <strong class="text-primary">${inv.SaleOfTerm || 'CASH'}</strong>
                                </span>
                            </div>
                            <div class="table-responsive rounded border bg-white shadow-none">
                                <table class="table table-sm table-bordered align-middle text-nowrap mb-0 batch-inner-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 32px;" class="text-center">#</th>
                                            <th>Product / Services</th>
                                            <th>Description</th>
                                            <th class="text-center">UOM</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Unit Rate</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">GST Amount</th>
                                            <th class="text-end">Net</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${(inv.items || []).map((it, i) => `
                                            <tr>
                                                <td class="text-center text-muted fs-11">${i + 1}</td>
                                                <td>
                                                    <strong class="text-dark">${it.ProductCode || ''}</strong>
                                                    ${it.ProductName ? '<span class="text-muted fs-11 ms-1">('+it.ProductName+')</span>' : ''}
                                                </td>
                                                <td class="text-dark fs-11">${it.ProductDescription || '-'}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-secondary border font-monospace fs-10">${it.Uom_Desc || '-'}</span>
                                                    ${it.HsCode ? '<small class="text-muted font-monospace fs-9 d-block mt-0.5">HS: ' + it.HsCode + '</small>' : ''}
                                                </td>
                                                <td class="text-end font-monospace">${Number(it.Quantity || 0).toFixed(2)}</td>
                                                <td class="text-end font-monospace">${Number(it.Rate || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-end font-monospace">${Number(it.Amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-end font-monospace text-muted">${Number(it.GSTAmount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-end font-monospace fw-bold text-dark">${Number(it.TotalValues || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
                `;
            });

            tbody.innerHTML = html;
            applyBatchModalFilter();
        })
        .catch(err => {
            loader.classList.add('d-none');
            container.classList.remove('d-none');
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger">Error loading batch invoices: ' + err.message + '</td></tr>';
        });
}

/**
 * Switch Active Tab in Modal
 */
function switchBatchModalTab(tabStatus) {
    currentBatchTab = tabStatus;

    document.querySelectorAll('#batchModalStatusTabs .nav-link').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab-status') === tabStatus);
    });

    updateFullViewLink(tabStatus);

    const summaryText = document.getElementById('batchModalSummaryText');
    const labelMap = {
        'all': 'all invoices',
        'not_posted': 'Not Posted invoices',
        'posted': 'Successfully Posted invoices',
        'error': 'FBR Error invoices',
        'cancelled': 'FBR Cancelled invoices'
    };
    if (summaryText) {
        summaryText.innerHTML = `<i class="ri-information-line text-primary fs-13"></i> Showing ${labelMap[tabStatus] || 'invoices'} in this batch. Click <i class="ri-arrow-down-s-line text-primary fw-bold"></i> to view line items.`;
    }

    applyBatchModalFilter();
}

/**
 * Update bottom "Open in Full Invoices List" link
 */
function updateFullViewLink(tabStatus) {
    const fullViewBtn = document.getElementById('batchModalFullViewBtn');
    if (fullViewBtn && currentBatchRequestId) {
        fullViewBtn.href = '/invoices?search=' + encodeURIComponent(currentBatchRequestId) + '&fbr_status=' + encodeURIComponent(tabStatus);
    }
}

/**
 * Apply both active Tab filter and Search input
 */
function applyBatchModalFilter() {
    const filter = (document.getElementById('batchInvoiceFilterInput').value || '').toLowerCase();
    const rows = document.querySelectorAll('.batch-invoice-row');
    const emptyState = document.getElementById('batchModalEmptyState');
    const tableEl = document.getElementById('batchInvoicesDetailTable');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        const text = row.textContent.toLowerCase();
        const headerId = row.id.replace('inv-row-', '');
        const itemsRow = document.getElementById('items-row-' + headerId);

        const matchesTab = (currentBatchTab === 'all' || rowStatus === currentBatchTab);
        const matchesSearch = text.includes(filter);

        if (matchesTab && matchesSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
            if (itemsRow) itemsRow.classList.add('d-none');
        }
    });

    if (emptyState && tableEl) {
        if (visibleCount === 0 && rows.length > 0) {
            emptyState.classList.remove('d-none');
            const emptyMsg = document.getElementById('batchModalEmptyMsg');
            if (emptyMsg) {
                const tabTitles = {
                    'not_posted': 'Not Posted',
                    'posted': 'Successfully Posted',
                    'error': 'FBR Error',
                    'cancelled': 'FBR Cancelled',
                    'all': 'All Invoices'
                };
                emptyMsg.textContent = `No invoices found in '${tabTitles[currentBatchTab] || currentBatchTab}' status matching your filter.`;
            }
        } else {
            emptyState.classList.add('d-none');
        }
    }
}

/**
 * Toggle Line Items Accordion in Modal
 */
function toggleBatchInvoiceItems(headerId) {
    const itemsRow = document.getElementById('items-row-' + headerId);
    const icon = document.getElementById('toggle-icon-' + headerId);
    const btn = document.getElementById('toggle-btn-' + headerId);
    if (!itemsRow) return;

    if (itemsRow.classList.contains('d-none')) {
        itemsRow.classList.remove('d-none');
        if (icon) {
            icon.classList.remove('ri-arrow-right-s-line');
            icon.classList.add('ri-arrow-down-s-line');
        }
        if (btn) btn.classList.add('active');
    } else {
        itemsRow.classList.add('d-none');
        if (icon) {
            icon.classList.remove('ri-arrow-down-s-line');
            icon.classList.add('ri-arrow-right-s-line');
        }
        if (btn) btn.classList.remove('active');
    }
}

/**
 * Live search filter within the modal
 */
function filterBatchInvoicesTable() {
    applyBatchModalFilter();
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
