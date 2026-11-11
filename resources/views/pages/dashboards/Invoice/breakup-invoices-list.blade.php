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

    /* ── Header tab/card styling — mirrors invoice-list and bulk-invoices ── */
    .invoice-card-tab-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 14px;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
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
                        <th class="text-center" style="width: 140px;">FBR Status</th>
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
                                $liveError = (int) ($batch->LiveErrorCount ?? 0);
                            @endphp
                            @if($liveTotal === 0)
                                <span class="badge bg-light text-muted">Empty Batch</span>
                            @elseif($livePosted >= $liveTotal)
                                <span class="badge rounded-pill bg-success-subtle text-success" style="font-size: 11px; padding: 4px 8px;">
                                    <i class="fas fa-check-circle me-1"></i> All Posted ({{ $livePosted }}/{{ $liveTotal }})
                                </span>
                            @elseif($livePosted > 0)
                                <span class="badge rounded-pill bg-warning-subtle text-warning" style="font-size: 11px; padding: 4px 8px;">
                                    <i class="fas fa-paper-plane me-1"></i> {{ $livePosted }}/{{ $liveTotal }} Posted
                                </span>
                                @if($liveError > 0)
                                    <small class="d-block text-danger font-monospace fs-10 mt-0.5">{{ $liveError }} error(s)</small>
                                @endif
                            @elseif($liveError > 0)
                                <span class="badge rounded-pill bg-danger-subtle text-danger" style="font-size: 11px; padding: 4px 8px;">
                                    <i class="fas fa-exclamation-triangle me-1"></i> {{ $liveError }} Error(s)
                                </span>
                            @else
                                <span class="badge rounded-pill bg-light text-muted border" style="font-size: 11px; padding: 4px 8px;">
                                    <i class="fas fa-clock me-1 text-muted"></i> Not Posted (0/{{ $liveTotal }})
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
                                            <a class="dropdown-item" href="{{ route('invoices.index', ['search' => $batch->RequestID, 'fbr_status' => 'all']) }}" target="_blank">
                                                <i class="fas fa-external-link-alt me-2 text-secondary"></i> Open in Invoices Page
                                            </a>
                                        </li>
                                        @php
                                            $isPostedBatch = ($batch->FbrPostedCount ?? 0) > 0;
                                        @endphp
                                        @if($isPostedBatch)
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <span class="dropdown-item text-muted disabled" style="cursor: not-allowed;" title="Invoices in this batch have already been submitted to FBR and cannot be deleted">
                                                    <i class="fas fa-lock me-2 text-muted"></i> Rollback Blocked (FBR Posted)
                                                </span>
                                            </li>
                                        @else
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" onclick="confirmRollbackBatch('{{ $batch->RequestID }}', '{{ addslashes($batch->Title) }}', {{ $batch->TotalInvoices }})">
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
                <form id="breakupGeneratorForm" onsubmit="generateBreakupPreview(event)">
                    @csrf
                    <input type="hidden" name="request_id" id="form_request_id" value="">
                    
                    <div class="card border rounded mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3">
                                {{-- Customer Selection --}}
                                <div class="col-md-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">Customer <span class="text-danger">*</span></label>
                                    <select name="customer_id" id="modal_customer_id" class="form-select form-select-sm" required>
                                        <option value="">-- Select Customer --</option>
                                        @foreach($customers as $c)
                                            <option value="{{ $c->ID }}">
                                                {{ $c->DisplayName ?? $c->BusinessName ?? ('Customer #' . $c->ID) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Product Selection --}}
                                <div class="col-md-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">Product <span class="text-danger">*</span></label>
                                    <select name="product_id" id="modal_product_id" class="form-select form-select-sm" onchange="onProductSelect(this.value)" required>
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

                                {{-- Product Attributes Info Bar (Auto-loaded) --}}
                                <div class="col-12" id="productInfoBanner" style="display: none;">
                                    <div class="p-2 border rounded bg-light d-flex flex-wrap gap-3 align-items-center fs-12">
                                        <div><span class="text-muted">UOM:</span> <strong id="infoUom" class="badge bg-secondary-transparent">-</strong></div>
                                        <div><span class="text-muted">FBR Sale Type:</span> <strong id="infoSaleType" class="text-dark">-</strong></div>
                                        <div><span class="text-muted">Tax Rate:</span> <strong id="infoTaxRate" class="badge bg-info-transparent">-</strong></div>
                                        <div><span class="text-muted">Fixed Value (TPRate):</span> <strong id="infoFixedRate" class="badge bg-warning-transparent">-</strong></div>
                                    </div>
                                </div>

                                {{-- Sale Rate & Fixed Rate Override --}}
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">Sale Rate (PKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="any" min="0.01" name="sale_rate" id="modal_sale_rate" class="form-control form-control-sm text-end" placeholder="0.00" required>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">Fixed Rate (TPRate)</label>
                                    <input type="number" step="any" min="0" name="tp_rate" id="modal_tp_rate" class="form-control form-control-sm text-end" placeholder="Optional" oninput="syncFixedRateFlag(this.value)">
                                    <input type="hidden" name="is_fixed_value" id="modal_is_fixed_value" value="0">
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">Sale of Term</label>
                                    <select name="sale_of_term" class="form-select form-select-sm">
                                        <option value="CASH">CASH</option>
                                        <option value="CREDIT">CREDIT</option>
                                    </select>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">Invoice Numbering</label>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="numbering_mode" id="numModeAuto" value="auto" checked onchange="toggleNumberingMode()">
                                            <label class="form-check-label fs-12 fw-medium" for="numModeAuto">Auto</label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="numbering_mode" id="numModeCustom" value="custom" onchange="toggleNumberingMode()">
                                            <label class="form-check-label fs-12 fw-medium" for="numModeCustom">Prefix</label>
                                        </div>
                                    </div>
                                    <div id="customPrefixWrapper" class="mt-1" style="display: none;">
                                        <input type="text" name="invoice_prefix" id="modal_invoice_prefix" class="form-control form-control-sm" value="INV-{{ date('Y') }}" placeholder="e.g. INV-2026">
                                    </div>
                                </div>

                                {{-- Target Total & Min/Max Constraints --}}
                                <div class="col-md-4 col-sm-12">
                                    <label class="form-label fs-12 fw-semibold mb-1 text-primary">
                                        Total Target Quantity <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" min="0.0001" name="total_target_qty" id="modal_target_qty" class="form-control form-control-sm text-end fw-bold" placeholder="e.g. 1000" required>
                                        <span class="input-group-text bg-light text-muted" id="targetUomAddon">Units</span>
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">Min Qty per Invoice <span class="text-danger">*</span></label>
                                    <input type="number" step="any" min="0.0001" name="min_qty" id="modal_min_qty" class="form-control form-control-sm text-end" placeholder="e.g. 20" required>
                                </div>

                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">Max Qty per Invoice <span class="text-danger">*</span></label>
                                    <input type="number" step="any" min="0.0001" name="max_qty" id="modal_max_qty" class="form-control form-control-sm text-end" placeholder="e.g. 50" required>
                                </div>

                                {{-- Date Range & Decimal Options --}}
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">From Date <span class="text-danger">*</span></label>
                                    <input type="date" name="from_date" id="modal_from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}" required>
                                </div>

                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fs-12 fw-semibold mb-1">To Date <span class="text-danger">*</span></label>
                                    <input type="date" name="to_date" id="modal_to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                                </div>

                                <div class="col-md-4 col-sm-12 d-flex align-items-center pt-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="allow_decimal_qty" id="modal_allow_decimal" value="1">
                                        <label class="form-check-label fs-12 fw-medium text-dark" for="modal_allow_decimal">
                                            Allow Decimal Quantities
                                        </label>
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
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 2: VIEW BATCH INVOICES (Shared with Bulk Invoices)       --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@include('pages.dashboards.Invoice._batch-invoices-view-modal')

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 3: ROLLBACK BATCH CONFIRMATION --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="rollbackBreakupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent py-2">
                <h5 class="modal-title fs-14 fw-semibold text-danger">
                    <i class="ri-alert-line me-1"></i> Rollback Breakup Batch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rollbackBatchForm" method="POST" onsubmit="executeRollbackBatch(event)">
                @csrf
                <input type="hidden" name="request_id" id="rollback_request_id" value="">
                <div class="modal-body p-3">
                    <div class="alert alert-warning fs-12 mb-3">
                        <i class="ri-error-warning-line me-1"></i> This action will hard delete all unposted invoices in this batch and archive them into history tables.
                    </div>
                    <div class="fs-13">
                        Are you sure you want to rollback batch: <strong id="rollback_batch_title" class="text-dark"></strong> (<span id="rollback_invoices_count"></span> invoices)?
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger px-3" id="btnConfirmRollback">
                        <i class="ri-delete-bin-line me-1"></i> Yes, Rollback Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const CSRF_TOKEN = '{{ csrf_token() }}';

    // ── Open Generator Modal ──────────────────────────────────────────
    function openBreakupModal() {
        document.getElementById('form_request_id').value = '';
        document.getElementById('breakupPreviewContainer').innerHTML = '';
        const modal = new bootstrap.Modal(document.getElementById('breakupGeneratorModal'));
        modal.show();
    }

    // ── Product Selection Auto-Loader ─────────────────────────────────
    function onProductSelect(productId) {
        if (!productId) {
            document.getElementById('productInfoBanner').style.display = 'none';
            return;
        }

        const sel = document.getElementById('modal_product_id');
        const opt = sel.options[sel.selectedIndex];

        // Read data attributes directly for instantaneous responsiveness
        const salePrice = opt.getAttribute('data-saleprice') || 0;
        const tpRate = opt.getAttribute('data-tprate') || 0;
        const isFixed = opt.getAttribute('data-isfixed') || 0;
        const uom = opt.getAttribute('data-uom') || '-';
        const saleType = opt.getAttribute('data-saletype') || '-';
        const taxRate = opt.getAttribute('data-salerate') || '-';

        document.getElementById('modal_sale_rate').value = salePrice;
        document.getElementById('modal_tp_rate').value = tpRate > 0 ? tpRate : '';
        document.getElementById('modal_is_fixed_value').value = isFixed;

        document.getElementById('infoUom').textContent = uom;
        document.getElementById('targetUomAddon').textContent = uom !== '-' ? uom : 'Units';
        document.getElementById('infoSaleType').textContent = saleType;
        document.getElementById('infoTaxRate').textContent = taxRate;
        document.getElementById('infoFixedRate').textContent = isFixed == 1 ? (tpRate + ' (Active)') : 'None';

        document.getElementById('productInfoBanner').style.display = 'block';
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
                alert('Generation Error: ' + (data.message || 'Failed to generate preview.'));
                return;
            }

            document.getElementById('form_request_id').value = data.request_id;
            document.getElementById('breakupPreviewContainer').innerHTML = data.preview_html;
        })
        .catch(err => {
            document.getElementById('previewLoadingSpinner').style.display = 'none';
            document.getElementById('btnGeneratePreview').disabled = false;
            alert('Failed to connect to server: ' + err);
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
            alert('Quantity must be greater than zero.');
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
                alert(data.message || 'Row update failed.');
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
        .catch(err => alert('Failed to update row: ' + err));
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
                alert(data.message || 'Delete row failed.');
                return;
            }

            const tr = document.getElementById('preview-row-' + tempId);
            if (tr) tr.remove();

            syncPreviewTotals(data.batch_totals);
        })
        .catch(err => alert('Failed to delete row: ' + err));
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

    // ── Confirm & Move to Live Invoices ────────────────────────────────
    function commitBreakupInvoices() {
        const requestId = document.getElementById('form_request_id').value;
        const batchTitle = document.getElementById('commitBatchTitle').value;
        const btn = document.getElementById('btnCommitInvoices');

        if (!requestId) {
            alert('No preview batch active. Please generate a preview first.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Moving to Invoices...';

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
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-check-double-line me-1"></i> Confirm & Move to Invoices';
                alert('Commit failed: ' + (data.message || 'Error saving invoices.'));
                return;
            }

            alert(data.message || 'Invoices moved successfully!');
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-check-double-line me-1"></i> Confirm & Move to Invoices';
            alert('Server error: ' + err);
        });
    }

    // ── Rollback Confirmation Modal ────────────────────────────────────
    function confirmRollbackBatch(requestId, title, count) {
        document.getElementById('rollback_request_id').value = requestId;
        document.getElementById('rollback_batch_title').textContent = title;
        document.getElementById('rollback_invoices_count').textContent = count;
        const modal = new bootstrap.Modal(document.getElementById('rollbackBreakupModal'));
        modal.show();
    }

    function executeRollbackBatch(e) {
        e.preventDefault();
        const requestId = document.getElementById('rollback_request_id').value;
        const btn = document.getElementById('btnConfirmRollback');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Rolling back...';

        fetch('{{ url("breakup-invoices/rollback") }}/' + requestId, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.status) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-delete-bin-line me-1"></i> Yes, Rollback Batch';
                alert('Rollback failed: ' + (data.message || 'Error.'));
                return;
            }
            alert(data.message || 'Batch rolled back successfully.');
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-delete-bin-line me-1"></i> Yes, Rollback Batch';
            alert('Server error: ' + err);
        });
    }

    function formatMoney(num) {
        return (parseFloat(num) || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
</script>
@endsection

