{{-- Purchase List --}}

@extends('layouts.master')

@section('styles')
<style>
    /* Purchase list: action column — match invoice listing */
    #purchasesTable th.action-col,
    #purchasesTable td:last-child {
        min-width: 10rem;
        max-width: none !important;
        width: auto !important;
        text-align: end;
        vertical-align: middle;
    }
    #purchasesTable .btn-group.btn-listing {
        position: relative;
    }
    #purchasesTable .dropdown-menu {
        z-index: 1060;
    }
    #breakupPreviewModal .modal-dialog {
        max-height: calc(100vh - 2rem);
    }
    #breakupPreviewModal .modal-content {
        max-height: calc(100vh - 2rem);
        display: flex;
        flex-direction: column;
    }
    #breakupPreviewModal .modal-body {
        overflow: hidden;
        flex: 1 1 auto;
        min-height: 0;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/css/bootstrap-datepicker3.min.css" rel="stylesheet">
@endsection

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Purchase Management</h1>
        <div class="d-flex float-end">
            <button type="button"
                    class="btn btn-sm btn-primary btn-wave waves-light"
                    onclick="openPurchaseModal('create')">
                <i class="ri-add-line fw-medium align-middle me-1"></i> Add Purchase
            </button>
            <div class="dropdown ms-2">
                <button class="btn btn-icon btn-secondary-light btn-sm btn-wave waves-light"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('purchases.export-csv') }}">Export CSV</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle"></i>
    @foreach ($errors->all() as $error) {{ $error }} @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filter bar (same layout as invoice listing) --}}
<div class="row">
    <div class="col-xs-12">
        <div class="card custom-card mb-1">
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-md-9">
                        <form action="{{ route('purchases.index') }}" method="GET" class="listing-filters-form">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="header-search d-md-block d-none">
                                        <label>Search Purchase</label>
                                        <input type="text"
                                               class="header-search-bar form-control form-control-sm bg-white"
                                               name="search" id="searchPurchases"
                                               value="{{ request('search') }}"
                                               placeholder="Search by doc no, supplier..."
                                               onkeyup="debounceSearch()">
                                        <a href="javascript:void(0);" class="header-search-icon border-0" style="top:25px;">
                                            <i class="bi bi-search fs-12"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="filter-date-label">Start Date</label>
                                            <div class="input-group input-group-sm filter-date-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </span>
                                                <input type="text"
                                                       class="form-control filter-date-picker"
                                                       name="start_date" id="startDate"
                                                       value="{{ request('start_date') }}"
                                                       autocomplete="off"
                                                       placeholder="YYYY-MM-DD">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="filter-date-label">End Date</label>
                                            <div class="input-group input-group-sm filter-date-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-calendar-check"></i>
                                                </span>
                                                <input type="text"
                                                       class="form-control filter-date-picker"
                                                       name="end_date" id="endDate"
                                                       value="{{ request('end_date') }}"
                                                       autocomplete="off"
                                                       placeholder="YYYY-MM-DD">
                                            </div>
                                        </div>
                                        <div class="col listing-filter-actions-col">
                                            <button class="btn btn-sm btn-primary btn-margin" type="button"
                                                    onclick="window.location.href='{{ route('purchases.index') }}'">
                                                <i class="fas fa-times"></i> Clear Filters
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Purchase table --}}
<div class="col-xs-12">
    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="purchases-table table text-nowrap listing-table" id="purchasesTable">
                    <thead>
                        <tr>
                            <th>Purchase Date <i class="fas fa-sort"></i></th>
                            <th width="25%">Supplier</th>
                            <th>Doc No <i class="fas fa-sort"></i></th>
                            <th>Total</th>
                            <th>Breakup</th>
                            <th class="action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                        <tr>
                            <td>
                                {{ $purchase->PurchaseDate
                                    ? \Carbon\Carbon::parse($purchase->PurchaseDate)->format('d/m/Y')
                                    : '-' }}
                            </td>
                            <td>{{ $purchase->SupplierDisplayName ?? 'N/A' }}</td>
                            <td>{{ $purchase->DocNo ?? '-' }}</td>
                            <td>{{ number_format($purchase->TotalAmount ?? 0, 2) }} PKR</td>
                            @php
                                $lineCount = (int) ($purchase->PurchaseLineCount ?? 0);
                                $singleLine = $lineCount === 1;
                                $breakupStatus = $singleLine
                                    ? (int) ($purchase->BreakupStatus ?? \App\Services\PurchaseBreakupService::STATUS_NOT_GENERATED)
                                    : -1;
                                $breakupSvc = app(\App\Services\PurchaseBreakupService::class);
                                $breakupLabel = $breakupSvc->statusLabel($breakupStatus >= 0 ? $breakupStatus : null, $singleLine);
                                $breakupBadge = $breakupSvc->statusBadgeClass($breakupStatus >= 0 ? $breakupStatus : null, $singleLine);
                            @endphp
                            <td @if($breakupStatus === \App\Services\PurchaseBreakupService::STATUS_PROCESSING) data-breakup-processing="1" data-purchase-id="{{ $purchase->HeaderID }}" @endif>
                                <span class="badge rounded-pill {{ $breakupBadge }}"
                                      @if(!$singleLine) title="Breakup requires exactly one line item" @endif>
                                    {{ $breakupLabel }}
                                </span>
                            </td>
                            <td>
                                <div class="action-wrapper d-inline-flex justify-content-end">
                                    <div class="btn-group btn-listing">
                                        <button type="button" class="btn btn-outline-primary btn-list-left"
                                                onclick="openPurchaseModal('show', {{ $purchase->HeaderID }})">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button type="button"
                                                class="btn btn-primary btn-list-right dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown"
                                                data-bs-auto-close="true"
                                                data-bs-popper-config='{"strategy":"fixed"}'
                                                aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item"
                                                        onclick="openPurchaseModal('edit', {{ $purchase->HeaderID }})">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </li>
                                            @if($singleLine)
                                                @if($breakupStatus === \App\Services\PurchaseBreakupService::STATUS_NOT_GENERATED || $breakupStatus === \App\Services\PurchaseBreakupService::STATUS_FAILED)
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                                onclick="openBreakupGenerateModal({{ $purchase->HeaderID }}, '{{ addslashes($purchase->DocNo ?? '') }}', false)">
                                                            <i class="fas fa-random"></i> Generate Breakup
                                                        </button>
                                                    </li>
                                                @elseif($breakupStatus === \App\Services\PurchaseBreakupService::STATUS_PROCESSING)
                                                    <li>
                                                        <span class="dropdown-item text-muted">
                                                            <i class="fas fa-spinner"></i> Processing…
                                                        </span>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                                onclick="openBreakupGenerateModal({{ $purchase->HeaderID }}, '{{ addslashes($purchase->DocNo ?? '') }}', true)">
                                                            <i class="fas fa-redo"></i> Retry (stuck?)
                                                        </button>
                                                    </li>
                                                @elseif($breakupStatus === \App\Services\PurchaseBreakupService::STATUS_GENERATED)
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                                onclick="openBreakupPreview({{ $purchase->HeaderID }})">
                                                            <i class="fas fa-table"></i> Preview
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('purchases.breakup.download', $purchase->HeaderID) }}">
                                                            <i class="fas fa-file-excel"></i> Download Excel
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                                onclick="openBreakupGenerateModal({{ $purchase->HeaderID }}, '{{ addslashes($purchase->DocNo ?? '') }}', true)">
                                                            <i class="fas fa-redo"></i> Regenerate
                                                        </button>
                                                    </li>
                                                @elseif($breakupStatus === \App\Services\PurchaseBreakupService::STATUS_EXPIRED)
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                                onclick="openBreakupGenerateModal({{ $purchase->HeaderID }}, '{{ addslashes($purchase->DocNo ?? '') }}', true)">
                                                            <i class="fas fa-redo"></i> Regenerate
                                                        </button>
                                                    </li>
                                                @endif
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="confirmDelete({{ $purchase->HeaderID }}, '{{ addslashes($purchase->DocNo ?? '') }}')">
                                                    <i class="fas fa-trash"></i> Archive
                                                </button>
                                                <form id="delete-form-{{ $purchase->HeaderID }}"
                                                      action="{{ route('purchases.delete_transaction', $purchase->HeaderID) }}"
                                                      method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-2">No purchases found.</p>
                                @if(!request('search') && !request('start_date') && !request('end_date'))
                                    <button class="btn btn-primary" onclick="openPurchaseModal('create')">
                                        <i class="fas fa-plus"></i> Create Your First Purchase
                                    </button>
                                @else
                                    <p class="text-muted small">Try adjusting your search or filters.</p>
                                @endif
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
                    Showing {{ $purchases->firstItem() ?? 0 }}
                    to {{ $purchases->lastItem() ?? 0 }}
                    of {{ $purchases->total() }} entries
                </span>
                <nav class="pagination-style-2">
                    {{ $purchases->withQueryString()->links('pagination::bootstrap-5') }}
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal effect-fall fade" id="purchase_modal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="purchase_modal_title">Purchase</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="purchase_modal_body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" class="btn btn-sm btn-primary d-none" id="purchase_modal_save_btn">
                    <i class="fas fa-save"></i>
                    <span id="purchase_modal_save_label">Save Purchase</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal effect-fall fade" id="breakupGenerateModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="breakupGenerateModalTitle">Generate Breakup</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="breakup_generate_modal_body">
                <div id="breakupGenerateAlert" class="alert alert-danger d-none mb-3" role="alert"></div>
                <form id="breakupGenerateForm" novalidate>
                    <div class="ifp-modal-shell">
                        <div class="_pfp-tab-panel-shell border rounded overflow-hidden mb-0">
                            <div class="_pfp-tab-panel-body">
                                <p class="text-muted fs-12 mb-3 mb-0 pb-3 border-bottom">
                                    Purchase: <strong id="breakupGenerateDocNo"></strong>
                                </p>
                                <div class="row g-2 pt-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                                        <select name="customer_id" id="breakupCustomerId" class="form-select" required>
                                            <option value="">— Select Customer —</option>
                                            @foreach($customers ?? [] as $cust)
                                                <option value="{{ $cust->ID }}">
                                                    {{ trim($cust->BusinessName ?? '') ?: trim($cust->DisplayName ?? '') ?: trim(trim(($cust->FirstName ?? '') . ' ' . ($cust->LastName ?? ''))) ?: ('Customer #' . $cust->ID) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Min Invoice Amount <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm"
                                               name="min_invoice_amount" id="breakupMinAmount" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Max Invoice Amount <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm"
                                               name="max_invoice_amount" id="breakupMaxAmount" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">From Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm"
                                               name="from_date" id="breakupFromDate" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm"
                                               name="end_date" id="breakupEndDate" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Invoice Prefix <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" name="invoice_prefix"
                                               id="breakupInvoicePrefix" value="INV" maxlength="50" required>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="allow_decimal_qty"
                                                   id="breakupAllowDecimal" value="1">
                                            <label class="form-check-label" for="breakupAllowDecimal">Allow decimal qty</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="breakupGenerateSubmitBtn">
                    <i class="fas fa-random me-1"></i> <span id="breakupGenerateSubmitLabel">Generate</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="breakupPreviewModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">Breakup Preview</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="breakupPreviewBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deletePurchaseModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-danger">Archive Purchase</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Archive purchase <strong id="deletePurchaseDocNo"></strong>? Stock quantities will be reversed.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmPurchaseDeleteBtn">Archive</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/js/bootstrap-datepicker.min.js"></script>
<script>
let deletePurchaseId = null;
let searchTimer = null;
let breakupPurchaseId = null;
let breakupIsRegenerate = false;
let breakupPollTimer = null;
const BREAKUP_STATUS_PROCESSING = 1;
const breakupGenerateUrl = '{{ url("/purchases") }}';
const breakupCsrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

function applyBreakupFormSettings(settings) {
    if (!settings) return;
    if (settings.customer_id != null) {
        document.getElementById('breakupCustomerId').value = String(settings.customer_id);
    }
    if (settings.min_invoice_amount != null) {
        document.getElementById('breakupMinAmount').value = settings.min_invoice_amount;
    }
    if (settings.max_invoice_amount != null) {
        document.getElementById('breakupMaxAmount').value = settings.max_invoice_amount;
    }
    if (settings.from_date) {
        document.getElementById('breakupFromDate').value = settings.from_date;
    }
    if (settings.end_date) {
        document.getElementById('breakupEndDate').value = settings.end_date;
    }
    if (settings.invoice_prefix) {
        document.getElementById('breakupInvoicePrefix').value = settings.invoice_prefix;
    }
    document.getElementById('breakupAllowDecimal').checked = !!settings.allow_decimal_qty;
}

function openBreakupGenerateModal(purchaseId, docNo, isRegenerate) {
    breakupPurchaseId = purchaseId;
    breakupIsRegenerate = !!isRegenerate;
    document.getElementById('breakupGenerateDocNo').textContent = docNo || '';
    document.getElementById('breakupGenerateModalTitle').textContent = isRegenerate ? 'Regenerate Breakup' : 'Generate Breakup';
    document.getElementById('breakupGenerateSubmitLabel').textContent = isRegenerate ? 'Regenerate' : 'Generate';
    document.getElementById('breakupGenerateAlert').classList.add('d-none');
    document.getElementById('breakupGenerateForm').reset();
    document.getElementById('breakupInvoicePrefix').value = 'INV';
    const today = new Date().toISOString().slice(0, 10);
    document.getElementById('breakupFromDate').value = today;
    document.getElementById('breakupEndDate').value = today;

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('breakupGenerateModal'));
    const submitBtn = document.getElementById('breakupGenerateSubmitBtn');
    submitBtn.disabled = true;

    fetch(breakupGenerateUrl + '/' + purchaseId + '/breakup/settings', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data.settings) {
            applyBreakupFormSettings(data.settings);
        }
    })
    .catch(function () { /* keep defaults */ })
    .finally(function () {
        submitBtn.disabled = false;
        modal.show();
    });
}

function openBreakupPreview(purchaseId) {
    const body = document.getElementById('breakupPreviewBody');
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('breakupPreviewModal')).show();

    fetch(breakupGenerateUrl + '/' + purchaseId + '/breakup/preview', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (res) {
        if (!res.ok) throw new Error('Preview not available');
        return res.text();
    })
    .then(function (html) {
        body.innerHTML = html;
    })
    .catch(function (err) {
        body.innerHTML = '<div class="alert alert-danger m-0">' + err.message + '</div>';
    });
}

document.getElementById('breakupGenerateSubmitBtn')?.addEventListener('click', function () {
    if (!breakupPurchaseId) return;

    const alertEl = document.getElementById('breakupGenerateAlert');
    const btn = this;
    const form = document.getElementById('breakupGenerateForm');
    const fd = new FormData(form);
    if (!document.getElementById('breakupAllowDecimal').checked) {
        fd.delete('allow_decimal_qty');
    } else {
        fd.set('allow_decimal_qty', '1');
    }

    const path = breakupIsRegenerate ? '/breakup/regenerate' : '/breakup/generate';
    btn.disabled = true;
    alertEl.classList.add('d-none');

    fetch(breakupGenerateUrl + '/' + breakupPurchaseId + path, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': breakupCsrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: fd
    })
    .then(function (res) {
        return res.json().then(function (data) {
            if (!res.ok) throw new Error(data.message || 'Request failed');
            return data;
        });
    })
    .then(function () {
        bootstrap.Modal.getInstance(document.getElementById('breakupGenerateModal'))?.hide();
        window.location.reload();
    })
    .catch(function (err) {
        alertEl.textContent = err.message || 'Generation failed';
        alertEl.classList.remove('d-none');
    })
    .finally(function () {
        btn.disabled = false;
    });
});

function startBreakupPolling() {
    const processingRows = document.querySelectorAll('[data-breakup-processing="1"]');
    if (processingRows.length === 0) return;

    if (breakupPollTimer) clearInterval(breakupPollTimer);
    breakupPollTimer = setInterval(function () {
        let anyProcessing = false;
        const checks = [];

        processingRows.forEach(function (el) {
            const id = el.getAttribute('data-purchase-id');
            checks.push(
                fetch(breakupGenerateUrl + '/' + id + '/breakup/status', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.breakup_status === BREAKUP_STATUS_PROCESSING) {
                        anyProcessing = true;
                    }
                })
            );
        });

        Promise.all(checks).then(function () {
            if (!anyProcessing) {
                clearInterval(breakupPollTimer);
                window.location.reload();
            }
        });
    }, 5000);
}

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
        const search = document.getElementById('searchPurchases')?.value || '';
        const start  = document.getElementById('startDate')?.value || '';
        const end    = document.getElementById('endDate')?.value || '';
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (start)  params.set('start_date', start);
        if (end)    params.set('end_date', end);
        window.location.href = '{{ route("purchases.index") }}?' + params.toString();
    }, 500);
}

function confirmDelete(id, docNo) {
    deletePurchaseId = id;
    document.getElementById('deletePurchaseDocNo').textContent = docNo || 'this purchase';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('deletePurchaseModal')).show();
    return false;
}

document.getElementById('confirmPurchaseDeleteBtn')?.addEventListener('click', function () {
    if (deletePurchaseId) {
        document.getElementById('delete-form-' + deletePurchaseId)?.submit();
    }
});

function openPurchaseModal(mode, purchaseId) {
    purchaseId = purchaseId || null;

    const modalEl    = document.getElementById('purchase_modal');
    const modalBody  = document.getElementById('purchase_modal_body');
    const modalTitle = document.getElementById('purchase_modal_title');
    const saveBtn    = document.getElementById('purchase_modal_save_btn');
    const saveLabel  = document.getElementById('purchase_modal_save_label');
    const bsModal    = bootstrap.Modal.getOrCreateInstance(modalEl);

    const titles = { create: 'Add Purchase', edit: 'Edit Purchase', show: 'Purchase Details' };
    modalTitle.textContent = titles[mode] || 'Purchase';

    if (mode === 'show') {
        saveBtn.classList.add('d-none');
    } else {
        saveBtn.classList.remove('d-none');
        saveLabel.textContent = mode === 'edit' ? 'Update Purchase' : 'Save Purchase';
    }

    modalBody.innerHTML = '<div class="text-center py-5">' +
        '<div class="spinner-border text-primary" role="status"></div>' +
        '<p class="mt-2 text-muted">Loading...</p></div>';
    bsModal.show();

    const url = mode === 'create'
        ? '{{ route("purchases.modal") }}?mode=create'
        : '/purchases/' + purchaseId + '/modal?mode=' + mode;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        }
    })
    .then(function (res) {
        if (!res.ok) throw new Error('Server error: ' + res.status);
        return res.text();
    })
    .then(function (html) {
        modalBody.innerHTML = html;
        modalBody.querySelectorAll('script').forEach(function (old) {
            const s = document.createElement('script');
            if (old.src) s.src = old.src;
            else s.textContent = old.textContent;
            document.body.appendChild(s);
        });
        if (typeof window.initPurchaseForm === 'function') {
            window.initPurchaseForm(mode, saveBtn, bsModal);
        }
    })
    .catch(function (err) {
        modalBody.innerHTML = '<div class="alert alert-danger m-3">' + err.message + '</div>';
    });
}

$(function () {
    startBreakupPolling();

    $('.filter-date-picker').datepicker({
        format: 'yyyy-mm-dd',
        todayHighlight: true,
        autoclose: true,
        orientation: 'bottom auto',
        clearBtn: true
    }).on('changeDate clearDate', function () {
        debounceSearch();
    });
});

setTimeout(function () {
    document.querySelectorAll('.alert').forEach(function (a) {
        a.classList.remove('show');
        setTimeout(function () { a.remove(); }, 150);
    });
}, 5000);
</script>
@endsection
