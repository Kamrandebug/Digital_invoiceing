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

    /* Compact Table Styling matching Invoice List */
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
<div class="alert alert-success alert-dismissible fade show custom-alert-icon shadow-sm mb-3" role="alert">
    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm mb-3" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i> {!! session('error') !!}
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
                        <span class="d-block text-muted fs-12 mb-1">Total Batch Value</span>
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
                        <span class="d-block text-muted fs-12 mb-1">Total Sales Tax</span>
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
                    <span class="input-group-text bg-light border-end-0"><i class="ri-search-line"></i></span>
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
<div class="card custom-card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="bulkInvoicesTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Request Title / Source</th>
                        <th>Request ID</th>
                        <th>Source Type</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total Amount (PKR)</th>
                        <th class="text-end">Total Tax (PKR)</th>
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
                        <td class="text-end fw-semibold text-dark">
                            {{ number_format($batch->TotalAmount, 2) }}
                        </td>
                        <td class="text-end text-muted">
                            {{ number_format($batch->TotalTax, 2) }}
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
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger" onclick="confirmBatchRollback('{{ $batch->RequestID }}', '{{ addslashes($batch->Title) }}', {{ $batch->TotalInvoices }})">
                                                <i class="fas fa-trash-alt me-2 text-danger"></i> Rollback Batch
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fs-24 d-block mb-2 text-secondary"></i>
                            No bulk invoicing requests found. Click <strong>Import Invoices</strong> to upload your first batch.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($batches->hasPages())
    <div class="card-footer py-2 px-3 border-top bg-light d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing {{ $batches->firstItem() }} to {{ $batches->lastItem() }} of {{ $batches->total() }} batches</small>
        {{ $batches->appends($filters)->links() }}
    </div>
    @endif
</div>

{{-- ── Batch Invoices Modal (View invoices against this request) ── --}}
<div class="modal fade" id="batchInvoicesModal" tabindex="-1" aria-labelledby="batchInvoicesModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-2 px-3 bg-light d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="modal-title fw-semibold text-primary mb-0 fs-15" id="batchInvoicesModalLabel">
                        <i class="fas fa-file-invoice-dollar me-2"></i> <span id="batchModalTitle">Batch Invoices</span>
                    </h6>
                    <small class="text-muted" id="batchModalSubtitle">Request ID: <code id="batchModalRequestId"></code></small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-transparent text-primary fw-semibold fs-11" id="batchModalInvoicesPill">0 Invoices</span>
                    <span class="badge bg-success-transparent text-success fw-semibold fs-11" id="batchModalAmountPill">PKR 0.00</span>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                {{-- Quick search in modal --}}
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-light border-end-0"><i class="ri-search-line"></i></span>
                        <input type="text" id="batchInvoiceFilterInput" class="form-control border-start-0" placeholder="Filter invoices in this batch..." onkeyup="filterBatchInvoicesTable()">
                    </div>
                    <div class="fs-12 text-muted" id="batchModalSummaryText">
                        Showing all invoices generated in this request. Click <i class="fas fa-chevron-circle-down text-primary"></i> to view line items.
                    </div>
                </div>

                {{-- Loading Spinner --}}
                <div id="batchModalLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading batch invoices...</span>
                    </div>
                    <p class="text-muted mt-2 fs-13">Loading invoices and line items...</p>
                </div>

                {{-- Invoices Table Container --}}
                <div id="batchModalTableContainer" class="table-responsive d-none">
                    <table class="table table-sm table-hover align-middle border mb-0" id="batchInvoicesDetailTable" style="font-size: 12px;">
                        <thead class="bg-light text-dark">
                            <tr>
                                <th style="width: 35px;" class="text-center">#</th>
                                <th style="width: 95px;">Date</th>
                                <th>Customer</th>
                                <th>DocNo</th>
                                <th class="text-end">Net (PKR)</th>
                                <th class="text-end">Tax (PKR)</th>
                                <th class="text-end">Total (PKR)</th>
                                <th>FBR Trans ID</th>
                                <th class="text-center">FBR Status</th>
                                <th class="text-center" style="width: 85px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="batchInvoicesTableBody">
                            {{-- Populated via AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 border-top bg-light d-flex justify-content-between">
                <a href="#" id="batchModalFullViewBtn" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt me-1"></i> Open in Full Invoices List
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
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
                    <div class="alert alert-warning py-1 px-2 fs-11 text-start mb-0">
                        <i class="fas fa-info-circle me-1"></i> This will delete all <strong id="rollbackInvoiceCount"></strong> invoices associated with this request.
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 border-top bg-light">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function handleBulkInvoiceImportSubmit(form) {
    const btn = document.getElementById('btnSubmitBulkInvoiceImport');
    const txt = document.getElementById('btnSubmitBulkInvoiceImportText');
    if (btn) {
        btn.disabled = true;
        if (txt) txt.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importing...';
    }
}

function confirmBatchRollback(requestId, title, count) {
    document.getElementById('rollbackBatchTitle').textContent = title;
    document.getElementById('rollbackInvoiceCount').textContent = count;
    document.getElementById('rollbackBatchForm').action = '/bulk-invoices/rollback/' + requestId;
    const modal = new bootstrap.Modal(document.getElementById('rollbackBatchModal'));
    modal.show();
}

/**
 * Open Batch Invoices Modal & Load Invoices via AJAX
 */
function viewBatchInvoices(requestId, title) {
    document.getElementById('batchModalTitle').textContent = title || 'Batch Invoices';
    document.getElementById('batchModalRequestId').textContent = requestId;
    document.getElementById('batchModalFullViewBtn').href = '/invoices?search=' + encodeURIComponent(requestId) + '&fbr_status=all';

    const loader = document.getElementById('batchModalLoader');
    const container = document.getElementById('batchModalTableContainer');
    const tbody = document.getElementById('batchInvoicesTableBody');

    loader.classList.remove('d-none');
    container.classList.add('d-none');
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

            document.getElementById('batchModalInvoicesPill').textContent = (batch.TotalInvoices || invoices.length) + ' Invoices';
            document.getElementById('batchModalAmountPill').textContent = 'PKR ' + Number(batch.TotalAmount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

            if (invoices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">No invoices found for this batch.</td></tr>';
                return;
            }

            let html = '';
            invoices.forEach((inv, idx) => {
                const isPosted = inv.FbrTransactionID && inv.FbrTransactionID.trim() !== '';
                const fbrStatusBadge = isPosted 
                    ? '<span class="badge bg-success-transparent text-success"><i class="fas fa-check-circle me-1"></i> Posted</span>'
                    : '<span class="badge bg-info-transparent text-info"><i class="fas fa-clock me-1"></i> Not Posted</span>';

                const customerDisplay = inv.CustomerName || inv.CustomerNTN || inv.CustomerCode || 'Walk-In';
                const customerCodeSubtitle = inv.CustomerCode ? '<small class="text-muted d-block">' + inv.CustomerCode + '</small>' : '';

                html += `
                <tr class="batch-invoice-row" id="inv-row-${inv.HeaderID}">
                    <td class="text-center">
                        <button class="btn btn-xs btn-link p-0 text-primary toggle-items-btn" onclick="toggleBatchInvoiceItems(${inv.HeaderID})" title="Toggle Line Items">
                            <i class="fas fa-chevron-circle-right fs-14" id="toggle-icon-${inv.HeaderID}"></i>
                        </button>
                    </td>
                    <td>${inv.InvoiceDate || ''}</td>
                    <td>
                        <div class="fw-semibold text-dark">${customerDisplay}</div>
                        ${customerCodeSubtitle}
                    </td>
                    <td><strong class="text-primary">${inv.DocNo || ''}</strong></td>
                    <td class="text-end">${Number(inv.ValueSalesExcludingST || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    <td class="text-end">${Number(inv.SalesTaxApplicable || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    <td class="text-end fw-semibold text-dark">${Number(inv.TotalValues || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    <td><code class="fs-11 text-secondary">${inv.FbrTransactionID || '-'}</code></td>
                    <td class="text-center">${fbrStatusBadge}</td>
                    <td class="text-center">
                        <a href="/invoices/${inv.HeaderID}/print-view" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2" title="Print Invoice">
                            <i class="fas fa-print"></i>
                        </a>
                    </td>
                </tr>
                <tr class="d-none bg-light batch-items-row" id="items-row-${inv.HeaderID}">
                    <td colspan="10" class="p-2">
                        <div class="card card-body bg-white p-2 border shadow-none mb-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold text-dark fs-11"><i class="fas fa-boxes me-1 text-primary"></i> Line Items (${inv.items_count || 0})</span>
                                <small class="text-muted">Sale of Term: <strong>${inv.SaleOfTerm || 'CASH'}</strong></small>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0" style="font-size: 11px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Product / Code</th>
                                            <th>Description</th>
                                            <th>HS Code</th>
                                            <th>UOM</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Rate</th>
                                            <th class="text-end">Excl. Tax</th>
                                            <th class="text-end">Tax</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${(inv.items || []).map((it, i) => `
                                            <tr>
                                                <td class="text-center">${i + 1}</td>
                                                <td><strong>${it.ProductCode || ''}</strong> ${it.ProductName ? '('+it.ProductName+')' : ''}</td>
                                                <td>${it.ProductDescription || '-'}</td>
                                                <td><code>${it.HsCode || '-'}</code></td>
                                                <td>${it.Uom_Desc || '-'}</td>
                                                <td class="text-end">${Number(it.Quantity || 0).toFixed(2)}</td>
                                                <td class="text-end">${Number(it.Rate || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-end">${Number(it.Amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-end">${Number(it.GSTAmount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-end fw-semibold">${Number(it.TotalValues || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
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
        })
        .catch(err => {
            loader.classList.add('d-none');
            container.classList.remove('d-none');
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-danger">Error loading batch invoices: ' + err.message + '</td></tr>';
        });
}

/**
 * Toggle Line Items Accordion in Modal
 */
function toggleBatchInvoiceItems(headerId) {
    const itemsRow = document.getElementById('items-row-' + headerId);
    const icon = document.getElementById('toggle-icon-' + headerId);
    if (!itemsRow) return;

    if (itemsRow.classList.contains('d-none')) {
        itemsRow.classList.remove('d-none');
        if (icon) {
            icon.classList.remove('fa-chevron-circle-right');
            icon.classList.add('fa-chevron-circle-down');
        }
    } else {
        itemsRow.classList.add('d-none');
        if (icon) {
            icon.classList.remove('fa-chevron-circle-down');
            icon.classList.add('fa-chevron-circle-right');
        }
    }
}

/**
 * Live search filter within the modal
 */
function filterBatchInvoicesTable() {
    const filter = document.getElementById('batchInvoiceFilterInput').value.toLowerCase();
    const rows = document.querySelectorAll('.batch-invoice-row');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const headerId = row.id.replace('inv-row-', '');
        const itemsRow = document.getElementById('items-row-' + headerId);

        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
            if (itemsRow) itemsRow.classList.add('d-none');
        }
    });
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
