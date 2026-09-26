{{-- Invoice List --}}

@extends('layouts.master')

@section('styles')
{{-- customer-management.css is loaded globally (layouts/components/styles.blade.php) for :root --cm-* and modal forms --}}
<style>
    .fbr-summary-card.fbr-summary--invalid {
        background: #fff8f8 !important;
        border-color: #f5c2c7 !important;
    }
    .fbr-summary-card.fbr-summary--valid {
        background: #f4fff8 !important;
        border-color: #badbcc !important;
    }

    /* Invoice list: action column — global .action-col is 100px max and breaks split + dropdown */
    #invoicesTable th.action-col,
    #invoicesTable td:last-child {
        min-width: 9.5rem;
        max-width: none !important;
        width: auto !important;
        text-align: end;
        vertical-align: middle;
    }
    #invoicesTable .btn-group.btn-listing {
        position: relative;
    }
    /* Popper uses strategy:fixed on toggle; keep menu above table scroll layers */
    #invoicesTable .dropdown-menu {
        z-index: 1060;
    }

    /* ── Compact Table Styling for 20 Records Per Page ── */
    #invoicesTable {
        font-size: 12.5px;
        margin-bottom: 0;
    }
    #invoicesTable thead th {
        padding: 0.35rem 0.55rem !important;
        font-size: 12px;
        font-weight: 600;
        vertical-align: middle;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    #invoicesTable tbody td {
        padding: 0.28rem 0.55rem !important;
        vertical-align: middle;
        line-height: 1.3;
        border-bottom: 1px solid #f1f5f9;
    }
    #invoicesTable .form-check-input {
        margin-top: 0;
        vertical-align: middle;
        width: 14px;
        height: 14px;
    }
    #invoicesTable .fbr-response-btn {
        padding: 2px 7px !important;
        font-size: 10.5px !important;
        line-height: 1.2;
    }
    #invoicesTable .badge {
        padding: 2px 7px;
        font-size: 10.5px;
        font-weight: 500;
    }
    #invoicesTable .btn-listing .btn {
        padding: 2px 8px !important;
        font-size: 11.5px !important;
        line-height: 1.35 !important;
    }
    #invoicesTable .btn-listing .btn i {
        font-size: 11px;
    }

    /* ── FBR Response Table Tabs Theming & Border Styling ── */
    .invoice-card-tab-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 8px 14px 0 14px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: wrap;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    .invoice-table-tabs {
        border-bottom: none;
        margin-bottom: 0;
        display: flex;
        gap: 6px;
        flex-wrap: nowrap;
        overflow-x: auto;
    }
    .invoice-table-tabs .nav-link {
        border: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        padding: 7px 15px;
        font-size: 12.5px;
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
    .invoice-table-tabs .nav-link:hover {
        color: #1e293b;
        background: #f8fafc;
        border-color: #cbd5e1;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }
    .invoice-table-tabs .nav-link.active {
        background: #ffffff;
        border-color: #cbd5e1 #cbd5e1 #ffffff;
        border-bottom: 1px solid #ffffff !important;
        font-weight: 600;
        position: relative;
        z-index: 2;
        box-shadow: 0 -2px 6px rgba(0,0,0,0.04);
    }
    .invoice-table-tabs .nav-link .badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.15s ease-in-out;
    }

    /* Tab 1: Not Posted (Blue / Primary Theme) */
    .invoice-table-tabs .tab-not-posted { border-color: #dbeafe; }
    .invoice-table-tabs .tab-not-posted:hover { border-color: #60a5fa; }
    .invoice-table-tabs .tab-not-posted .tab-icon { color: #0284c7; }
    .invoice-table-tabs .tab-not-posted .tab-badge { background-color: #e0f2fe; color: #0369a1; }
    .invoice-table-tabs .tab-not-posted.active {
        color: #0284c7;
        border-top: 3px solid #0284c7 !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .invoice-table-tabs .tab-not-posted.active .tab-badge {
        background-color: #0284c7;
        color: #ffffff;
    }

    /* Tab 2: Successfully Posted (Emerald Green Theme) */
    .invoice-table-tabs .tab-posted { border-color: #dcfce7; }
    .invoice-table-tabs .tab-posted:hover { border-color: #4ade80; }
    .invoice-table-tabs .tab-posted .tab-icon { color: #16a34a; }
    .invoice-table-tabs .tab-posted .tab-badge { background-color: #dcfce7; color: #15803d; }
    .invoice-table-tabs .tab-posted.active {
        color: #16a34a;
        border-top: 3px solid #16a34a !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .invoice-table-tabs .tab-posted.active .tab-badge {
        background-color: #16a34a;
        color: #ffffff;
    }

    /* Tab 3: FBR Error (Danger Red Theme) */
    .invoice-table-tabs .tab-error { border-color: #fee2e2; }
    .invoice-table-tabs .tab-error:hover { border-color: #f87171; }
    .invoice-table-tabs .tab-error .tab-icon { color: #dc2626; }
    .invoice-table-tabs .tab-error .tab-badge { background-color: #fee2e2; color: #b91c1c; }
    .invoice-table-tabs .tab-error.active {
        color: #dc2626;
        border-top: 3px solid #dc2626 !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .invoice-table-tabs .tab-error.active .tab-badge {
        background-color: #dc2626;
        color: #ffffff;
    }

    /* Tab 4: FBR Cancel Invoices (Orange / Amber Theme) */
    .invoice-table-tabs .tab-cancel { border-color: #ffedd5; }
    .invoice-table-tabs .tab-cancel:hover { border-color: #fb923c; }
    .invoice-table-tabs .tab-cancel .tab-icon { color: #ea580c; }
    .invoice-table-tabs .tab-cancel .tab-badge { background-color: #ffedd5; color: #c2410c; }
    .invoice-table-tabs .tab-cancel.active {
        color: #ea580c;
        border-top: 3px solid #ea580c !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .invoice-table-tabs .tab-cancel.active .tab-badge {
        background-color: #ea580c;
        color: #ffffff;
    }

    /* Tab 5: All Invoices (Slate Neutral Theme) */
    .invoice-table-tabs .tab-all { border-color: #e2e8f0; }
    .invoice-table-tabs .tab-all:hover { border-color: #94a3b8; }
    .invoice-table-tabs .tab-all .tab-icon { color: #64748b; }
    .invoice-table-tabs .tab-all .tab-badge { background-color: #f1f5f9; color: #475569; }
    .invoice-table-tabs .tab-all.active {
        color: #1e293b;
        border-top: 3px solid #475569 !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .invoice-table-tabs .tab-all.active .tab-badge {
        background-color: #475569;
        color: #ffffff;
    }
    /* ── Bulk Post FBR Modal Custom Styling ── */
    #bulkPostFbrModal .modal-dialog {
        max-width: 1020px;
        width: 95%;
        margin: 1.75rem auto;
    }
    #bulkPostFbrModal .modal-content {
        border-radius: 10px;
        overflow: hidden;
    }
    /* ── Bulk Post to FBR Modal Styling (Mirrors Batch Invoices Modal) ── */
    #bulkPostFbrModal .modal-content {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    #bulkPostFbrModal .modal-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.65rem 1.15rem;
    }
    #bulkPostFbrModal .modal-footer {
        background-color: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 0.55rem 1.15rem;
    }
    #bulkPostFbrModal .fbr-table-container {
        flex: 1 1 auto;
        min-height: 200px;
        max-height: 50vh;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: #ffffff;
    }
    #modalBulkPreviewTable {
        font-size: 12.5px;
        margin-bottom: 0;
        width: 100%;
    }
    #modalBulkPreviewTable thead th {
        padding: 0.35rem 0.55rem !important;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    #modalBulkPreviewTable tbody td {
        padding: 0.28rem 0.55rem !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 12px;
        color: #334155;
        white-space: nowrap;
        line-height: 1.3;
    }
    #modalBulkPreviewTable tfoot th {
        padding: 0.35rem 0.55rem !important;
        font-size: 12px;
        background-color: #f8fafc;
        border-top: 1px solid #e2e8f0;
        white-space: nowrap;
        position: sticky;
        bottom: 0;
        z-index: 2;
        vertical-align: middle;
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/css/bootstrap-datepicker3.min.css" rel="stylesheet">
@endsection

@section('content')
@php
    $canPostFbr = (auth()->check() && (int)(auth()->user()->UserType ?? 0) === 2)
        || session('selected_tenant.user_type') === 'admin'
        || (int)(session('selected_tenant.permissions.Invoices.Import') ?? 0) === 1;
@endphp
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Invoice Management</h1>
        <div class="d-flex float-end">
            @tenantCan('Invoices', 'Add')
            <button type="button"
                    class="btn btn-sm btn-primary btn-wave waves-light"
                    onclick="openInvoiceModal('create')">
                <i class="ri-add-line fw-medium align-middle me-1"></i> Add Invoice
            </button>
            @endtenantCan
            @if(session('selected_tenant.permissions.Invoices.Print') == 1 || session('selected_tenant.permissions.Invoices.Export') == 1)
            <div class="dropdown ms-2">
                <button class="btn btn-icon btn-secondary-light btn-sm btn-wave waves-light"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @tenantCan('Invoices', 'Print')
                    <li><a class="dropdown-item" href="#">Bulk Print</a></li>
                    @endtenantCan
                    @tenantCan('Invoices', 'Export')
                    <li><a class="dropdown-item" href="{{ route('invoices.export-csv', ['fbr_status' => $fbrStatus ?? 'not_posted', 'search' => request('search'), 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}">Export CSV</a></li>
                    @endtenantCan
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Session Messages --}}
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
@if($errors->any())
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-1"></i>
    @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button>
</div>
@endif

{{-- ── Filter Bar ── --}}
<div class="row">
    <div class="col-xs-12">
        <div class="card custom-card mb-2">
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-md-9">
                        <form action="{{ route('invoices.index') }}" method="GET" class="listing-filters-form">
                            <input type="hidden" name="fbr_status" id="fbrStatusInput" value="{{ $fbrStatus ?? 'not_posted' }}">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="header-search d-md-block d-none">
                                        <label>Search Invoice</label>
                                        <input type="text"
                                               class="header-search-bar form-control form-control-sm bg-white"
                                               name="search" id="searchInvoices"
                                               value="{{ request('search') }}"
                                               placeholder="Search by doc no, customer..."
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
                                                    onclick="window.location.href='{{ route('invoices.index', ['fbr_status' => $fbrStatus ?? 'not_posted']) }}'">
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

{{-- ── Invoice Table with Tab Looks ── --}}
<div class="col-xs-12">
    <div class="card custom-card">
        <div class="card-header invoice-card-tab-header d-flex justify-content-between align-items-center">
            <ul class="nav nav-tabs invoice-table-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link tab-not-posted {{ ($fbrStatus ?? 'not_posted') === 'not_posted' ? 'active' : '' }}"
                       href="javascript:void(0);"
                       onclick="filterFbrStatus('not_posted')">
                        <i class="fas fa-clock tab-icon"></i> Not Posted
                        <span class="badge tab-badge">
                            {{ number_format($tabCounts['not_posted'] ?? 0) }}
                        </span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link tab-posted {{ in_array($fbrStatus ?? '', ['posted', 'valid', 'successfully_posted']) ? 'active' : '' }}"
                       href="javascript:void(0);"
                       onclick="filterFbrStatus('posted')">
                        <i class="fas fa-check-circle tab-icon"></i> Successfully Posted
                        <span class="badge tab-badge">
                            {{ number_format($tabCounts['posted'] ?? 0) }}
                        </span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link tab-error {{ in_array($fbrStatus ?? '', ['error', 'failed']) ? 'active' : '' }}"
                       href="javascript:void(0);"
                       onclick="filterFbrStatus('error')">
                        <i class="fas fa-exclamation-triangle tab-icon"></i> FBR Error
                        <span class="badge tab-badge">
                            {{ number_format($tabCounts['error'] ?? 0) }}
                        </span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link tab-cancel {{ in_array($fbrStatus ?? '', ['cancelled', 'cancel', 'fbr_cancel']) ? 'active' : '' }}"
                       href="javascript:void(0);"
                       onclick="filterFbrStatus('cancelled')">
                        <i class="fas fa-ban tab-icon"></i> FBR Cancelled
                        <span class="badge tab-badge">
                            {{ number_format($tabCounts['cancelled'] ?? 0) }}
                        </span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link tab-all {{ ($fbrStatus ?? '') === 'all' ? 'active' : '' }}"
                       href="javascript:void(0);"
                       onclick="filterFbrStatus('all')">
                        <i class="fas fa-list tab-icon"></i> All Invoices
                        <span class="badge tab-badge">
                            {{ number_format($tabCounts['all'] ?? 0) }}
                        </span>
                    </a>
                </li>
            </ul>

            @if($canPostFbr && in_array($fbrStatus ?? 'not_posted', ['not_posted', 'error', 'failed']))
            <div class="ms-auto d-flex align-items-center py-1">
                <button type="button" 
                        id="bulkPostFbrBtn" 
                        class="btn btn-sm btn-primary btn-wave waves-light shadow-sm"
                        onclick="openBulkPostModal()">
                    <i class="fas fa-paper-plane me-1"></i> 
                    <span id="bulkPostBtnLabel">Post to FBR</span>
                    <span id="bulkPostCountBadge" class="badge bg-white text-primary ms-1 d-none">0</span>
                </button>
            </div>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="invoices-table table text-nowrap listing-table" id="invoicesTable">
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                            <th>InvoiceDate <i class="fas fa-sort"></i></th>
                            <th width="25%">Customer</th>
                            <th>DocNo <i class="fas fa-sort"></i></th>
                            <th>Net</th>
                            <th>FBR Transaction ID</th>
                            <th style="width:100px;">FBR Response</th>
                            <th class="action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        @php
                            $statusi = (int) ($invoice->Statusi ?? 0);
                            $fbrTxId = trim((string) ($invoice->FbrTransactionID ?? ''));
                            $fbrRespRaw = trim((string) ($invoice->FbrResponseJson ?? ''));
                            $fbrLocked = ($statusi === 7) || ($statusi === 8) || ($fbrTxId !== '');
                            $totalAmt = (float) ($invoice->TotalAmount ?? 0);
                            $totalGst = (float) ($invoice->TotalGST ?? 0);
                            $subTotal = (float) ($invoice->SubTotal ?? 0);
                            if ($subTotal == 0 && $totalAmt > 0) {
                                $subTotal = max(0, $totalAmt - $totalGst);
                            }
                        @endphp
                        <tr>
                            {{-- HeaderID is the unique key from getlistingdata() --}}
                            <td>
                                <input type="checkbox" 
                                       class="invoice-checkbox form-check-input" 
                                       value="{{ $invoice->HeaderID }}"
                                       data-id="{{ $invoice->HeaderID }}"
                                       data-docno="{{ $invoice->DocNo ?? 'N/A' }}"
                                       data-date="{{ $invoice->InvoiceDate ? \Carbon\Carbon::parse($invoice->InvoiceDate)->format('d/m/Y') : '-' }}"
                                       data-customer="{{ $invoice->CustomerDisplayName ?? 'N/A' }}"
                                       data-amount="{{ $subTotal }}"
                                       data-amount-formatted="{{ number_format($subTotal, 2) }} PKR"
                                       data-gst="{{ $totalGst }}"
                                       data-gst-formatted="{{ number_format($totalGst, 2) }} PKR"
                                       data-net="{{ $totalAmt }}"
                                       data-net-formatted="{{ number_format($totalAmt, 2) }} PKR"
                                       onchange="onInvoiceCheckboxChange()" />
                            </td>

                            <td>
                                {{ $invoice->InvoiceDate
                                    ? \Carbon\Carbon::parse($invoice->InvoiceDate)->format('d/m/Y')
                                    : '-' }}
                            </td>

                            <td>{{ $invoice->CustomerDisplayName ?? 'N/A' }}</td>

                            <td>{{ $invoice->DocNo ?? 'N/A' }}</td>

                            <td>{{ number_format($invoice->TotalAmount ?? 0, 2) }} PKR</td>

                            <td>{{ $invoice->FbrTransactionID ?? '-' }}</td>

                            <td>
                                @php
                                    $resp = $invoice->FbrResponseJson ?? '';
                                    $hasTxId = !empty($fbrTxId);
                                @endphp
                                @if($statusi === 8)
                                    <span class="badge rounded-pill fbr-response-btn bg-warning-subtle text-warning"
                                          style="cursor:pointer; font-size:11px; padding:5px 10px;"
                                          data-response="{{ e($resp) }}"
                                          title="Click to view FBR response">
                                        <i class="fas fa-ban me-1"></i>FBR Cancelled
                                    </span>
                                @elseif($statusi === 7 || $hasTxId)
                                    <span class="badge rounded-pill fbr-response-btn bg-success-subtle text-success"
                                          style="cursor:pointer; font-size:11px; padding:5px 10px;"
                                          data-response="{{ e($resp) }}"
                                          title="Click to view FBR response">
                                        <i class="fas fa-check-circle me-1"></i>Posted
                                    </span>
                                @elseif($statusi === 6 || (!empty($resp) && !$hasTxId))
                                    <span class="badge rounded-pill fbr-response-btn bg-danger-subtle text-danger"
                                          style="cursor:pointer; font-size:11px; padding:5px 10px;"
                                          data-response="{{ e($resp) }}"
                                          title="Click to view FBR error response">
                                        <i class="fas fa-exclamation-triangle me-1"></i>FBR Error
                                    </span>
                                @else
                                    <span class="badge rounded-pill bg-light text-muted">Not Posted</span>
                                @endif
                            </td>

                            <td>
								<div class="action-wrapper d-inline-flex justify-content-end">
									<div class="btn-group btn-listing">
									  @tenantCan('Invoices', 'ViewOnly')
									  <button type="button" class="btn btn-outline-primary btn-list-left" onclick="openInvoiceModal('show', {{ $invoice->HeaderID }})">
										<i class="fas fa-eye"></i> View
									  </button>
									  @endtenantCan
									  @if(session('selected_tenant.permissions.Invoices.Modify') == 1 || session('selected_tenant.permissions.Invoices.Delete') == 1 || session('selected_tenant.permissions.Invoices.Print') == 1)
									  <button type="button"
                                              class="btn btn-primary btn-list-right dropdown-toggle dropdown-toggle-split"
                                              data-bs-toggle="dropdown"
                                              data-bs-auto-close="true"
                                              data-bs-popper-config='{"strategy":"fixed"}'
                                              aria-expanded="false">
										<span class="visually-hidden">Toggle Dropdown</span>
									  </button>
									  <ul class="dropdown-menu dropdown-menu-end">
										@if(!$fbrLocked)
										@tenantCan('Invoices', 'Modify')
										<li>
											<button type="button" class="dropdown-item"
													onclick="openInvoiceModal('edit', {{ $invoice->HeaderID }})">
												<i class="fas fa-edit"></i> Edit
											</button>
										</li>
										@endtenantCan
										@endif
										
										@tenantCan('Invoices', 'Print')
										<li>
											<button type="button" class="dropdown-item"
													onclick="openPrintModal({{ $invoice->HeaderID }}, '{{ $invoice->DocNo ?? 'Invoice' }}')">
												<i class="fas fa-print"></i> Print
											</button>
										</li>
										@endtenantCan

										@if(empty(trim((string) ($invoice->FbrTransactionID ?? ''))))
										@tenantCan('Invoices', 'Modify')
										<li>
											<button type="button" class="dropdown-item"
													onclick="confirmPostFbr({{ $invoice->HeaderID }}, '{{ addslashes($invoice->DocNo ?? '') }}')">
												<i class="fas fa-paper-plane"></i> Post to FBR
											</button>
											<form id="post-fbr-form-{{ $invoice->HeaderID }}"
												  action="{{ route('invoices.post-fbr', $invoice->HeaderID) }}"
												  method="POST" class="d-none">
												@csrf
											</form>
										</li>
										@endtenantCan
										@endif

										@if(in_array($fbrStatus ?? '', ['posted', 'valid', 'successfully_posted']) && $statusi === 7)
										@tenantCan('Invoices', 'Modify')
										<li>
											<button type="button" class="dropdown-item text-warning"
													onclick="confirmCancelFbr({{ $invoice->HeaderID }}, '{{ addslashes($invoice->DocNo ?? '') }}')">
												<i class="fas fa-ban me-1"></i> Mark as FBR Cancelled
											</button>
											<form id="cancel-fbr-form-{{ $invoice->HeaderID }}"
												  action="{{ route('invoices.cancel-fbr', $invoice->HeaderID) }}"
												  method="POST" class="d-none">
												@csrf
											</form>
										</li>
										@endtenantCan
										@endif

										@if(!$fbrLocked)
										@tenantCan('Invoices', 'Delete')
										<li>
                                            <button type="button"
                                                    class="dropdown-item text-danger"
                                                    onclick="confirmDelete({{ $invoice->HeaderID }}, '{{ addslashes($invoice->DocNo ?? '') }}')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                            <form id="delete-form-{{ $invoice->HeaderID }}"
                                                  action="{{ route('invoices.delete_transaction', $invoice->HeaderID) }}"
                                                  method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
										</li>
										@endtenantCan
										@endif
									  </ul>
									  @endif
									</div>
								</div>

								
                               <?php /*?> {{-- VIEW --}}
                                <button class="btn btn-sm action-btn btn-info" onclick="openInvoiceModal('show', {{ $invoice->HeaderID }})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>

                                {{-- EDIT --}}
                                <button class="btn btn-sm action-btn btn-warning" onclick="openInvoiceModal('edit', {{ $invoice->HeaderID }})" title="Edit Invoice">
                                    <i class="fas fa-edit"></i>
                                </button>

                                {{-- DELETE --}}
                                <form action="{{ route('invoices.delete_transaction', $invoice->HeaderID) }}"
                                      method="POST"
                                      style="display:inline;"
                                      onsubmit="return confirmDelete('{{ $invoice->DocNo }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm action-btn btn-delete btn-danger" title="Archive Invoice">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form><?php */?>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-2">No invoices found.</p>
                                @if(!request('search') && !request('start_date') && !request('end_date'))
                                    @tenantCan('Invoices', 'Add')
                                    <button class="btn btn-primary" onclick="openInvoiceModal('create')">
                                        <i class="fas fa-plus"></i> Create Your First Invoice
                                    </button>
                                    @endtenantCan
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
                    Showing {{ $invoices->firstItem() ?? 0 }}
                    to {{ $invoices->lastItem() ?? 0 }}
                    of {{ $invoices->total() }} entries
                </span>
                <nav class="pagination-style-2">
                    {{ $invoices->withQueryString()->links('pagination::bootstrap-5') }}
                </nav>
            </div>
        </div>
    </div>
</div>

{{-- ── AJAX Invoice Modal ── --}}
<div class="modal effect-fall fade" id="invoice_modal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="invoice_modal_title">Invoice</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="invoice_modal_body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading...</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" class="btn btn-sm btn-primary d-none" id="invoice_modal_save_btn">
                    <i class="fas fa-save"></i>
                    <span id="invoice_modal_save_label">Save Invoice</span>
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Print Invoice Modal — PDF/print preview with orientation --}}
<div class="modal fade" id="invoice_print_modal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">
                    <i class="fas fa-print me-2"></i> Print Invoice — <span id="invoice_print_docno"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
                    <label class="mb-0 fw-medium">Orientation:</label>
                    <select id="invoice_print_orientation" class="form-select form-select-sm" style="width: auto;">
                        <option value="portrait">Portrait</option>
                        <option value="landscape">Landscape</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" id="invoice_print_apply_orientation">
                        <i class="fas fa-sync-alt me-1"></i> Apply
                    </button>
                    <button type="button" class="btn btn-sm btn-success ms-2" id="invoice_print_do_print">
                        <i class="fas fa-print me-1"></i> Print / Save as PDF
                    </button>
                    <small class="text-muted d-block w-100 mt-1" style="font-size: 11px;">
                        In the print dialog, turn off <strong>Headers and footers</strong> to hide the date, title, and page URL.
                    </small>
                </div>
                <div class="border rounded bg-light" style="min-height: 400px;">
                    <iframe id="invoice_print_frame" name="invoice_print_frame"
                            style="width:100%; height:75vh; border:0; display:block;"
                            title="Invoice print preview"></iframe>
                </div>
            </div>
            <div class="modal-footer no-print">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Invoice Delete Modal --}}
<div class="modal fade" id="deleteInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold text-danger">
                    <i class="fas fa-trash me-2"></i> Archive Invoice
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to archive invoice <strong id="deleteInvoiceDocNo"></strong>?</p>
                <p class="text-muted fs-13 mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    This will archive the invoice and hide it from active lists.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmInvoiceDeleteBtn">
                    <i class="fas fa-trash me-1"></i> Yes, Archive It
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Post to FBR confirmation (same pattern as Archive Invoice modal) --}}
<div class="modal fade" id="postFbrInvoiceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold text-primary">
                    <i class="fas fa-paper-plane me-2"></i> Post to FBR
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Post invoice <strong id="postFbrInvoiceDocNo"></strong> to the Federal Board of Revenue?</p>
                <p class="text-muted fs-13 mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    This submits the invoice to FBR. Ensure details are correct before continuing.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="confirmPostFbrBtn">
                    <i class="fas fa-paper-plane me-1"></i> Yes, Post to FBR
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Cancel FBR Invoice confirmation modal --}}
<div class="modal fade" id="cancelFbrInvoiceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold text-warning">
                    <i class="fas fa-ban me-2"></i> Mark as FBR Cancelled
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark invoice <strong id="cancelFbrInvoiceDocNo"></strong> as FBR Cancelled?</p>
                <p class="text-muted fs-13 mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    This will update the invoice status to <strong>FBR Cancelled</strong> and move it to the FBR Cancelled tab.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-warning text-white" id="confirmCancelFbrBtn">
                    <i class="fas fa-ban me-1"></i> Yes, Mark as Cancelled
                </button>
            </div>
        </div>
    </div>
</div>

{{-- FBR Response Modal --}}
<div class="modal fade" id="fbr_response_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <span class="badge bg-primary-subtle text-primary fw-semibold">FBR API Response</span>
                    <h6 class="modal-title fw-semibold mt-2 mb-0">Live Response Payload</h6>
                    <small class="text-muted">Read-only response returned from FBR for this invoice.</small>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div id="fbr_response_summary" class="mb-3 d-none">
                    <div id="fbr_summary_card" class="card border shadow-sm mb-0 fbr-summary-card" style="background:#f8fbff;">
                        <div class="card-body p-3">
                            <div id="fbr_summary_strip" class="d-none align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <span id="fbr_summary_badge_status" class="badge rounded-pill"></span>
                                <span id="fbr_summary_badge_code" class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis"></span>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label id="fbr_label_code" class="form-label mb-1 fw-semibold text-muted small">Code</label>
                                    <input type="text" id="fbr_summary_code" class="form-control form-control-sm" readonly>
                                </div>
                                <div class="col-md-9">
                                    <label id="fbr_label_message" class="form-label mb-1 fw-semibold text-muted small">Message</label>
                                    <input type="text" id="fbr_summary_message" class="form-control form-control-sm" readonly>
                                </div>
                                <div class="col-12">
                                    <label id="fbr_label_description" class="form-label mb-1 fw-semibold text-muted small">Description</label>
                                    <textarea id="fbr_summary_description" class="form-control form-control-sm" rows="3" readonly></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small">
                        <i class="fas fa-code me-1"></i>
                        Formatted JSON view (if applicable)
                    </div>
                    <button type="button"
                            class="btn btn-xs btn-outline-secondary"
                            style="font-size: 11px; padding: 3px 8px;"
                            onclick="copyFbrResponseToClipboard()">
                        <i class="fas fa-copy me-1"></i> Copy
                    </button>
                </div>
                <pre id="fbr_response_modal_body"
                     class="mb-0 small"
                     style="
                        white-space: pre-wrap;
                        word-break: break-word;
                        font-family: SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
                        background-color: #0b1120;
                        color: #e5e7eb;
                        border-radius: 6px;
                        padding: 14px 16px;
                        max-height: 70vh;
                        overflow: auto;
                        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.25);
                     "></pre>
            </div>
            <div class="modal-footer border-0 pt-0">
                <span class="text-muted small me-auto">
                    <i class="fas fa-info-circle me-1"></i>
                    This data is stored as received from FBR.
                </span>
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
{{-- Bulk Post to FBR Review Modal --}}
<div class="modal fade" id="bulkPostFbrModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="modal-title fw-semibold text-dark mb-0 fs-14 d-flex align-items-center">
                        <i class="fas fa-paper-plane text-primary me-2 fs-15"></i> Review & Post to FBR
                    </h6>
                    <small class="text-muted fs-11 mt-0.5 d-block">Review selected invoices and tax breakup before transmitting to FBR live portal.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-transparent text-primary fw-semibold fs-11 px-2 py-1" id="modalBulkHeaderInvoicesPill">0 Invoices</span>
                    <span class="badge bg-success-transparent text-success fw-semibold fs-11 px-2 py-1" id="modalBulkHeaderAmountPill">PKR 0.00</span>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                {{-- Summary Metrics Cards --}}
                <div class="row g-2 mb-3">
                    <div class="col-sm-3 col-6">
                        <div class="card custom-card border shadow-none mb-0">
                            <div class="card-body p-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="d-block text-muted fs-11 mb-1">Selected Invoices</span>
                                        <h6 class="fw-bold mb-0 text-dark fs-15" id="modalBulkCount">0</h6>
                                    </div>
                                    <div class="avatar avatar-sm bg-primary-transparent rounded-circle">
                                        <i class="ri-file-list-3-line fs-14 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="card custom-card border shadow-none mb-0">
                            <div class="card-body p-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="d-block text-muted fs-11 mb-1">Total Ex-Tax</span>
                                        <h6 class="fw-bold mb-0 text-dark fs-15" id="modalBulkAmount">PKR 0.00</h6>
                                    </div>
                                    <div class="avatar avatar-sm bg-info-transparent rounded-circle">
                                        <i class="ri-money-dollar-circle-line fs-14 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="card custom-card border shadow-none mb-0">
                            <div class="card-body p-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="d-block text-muted fs-11 mb-1">Total GST</span>
                                        <h6 class="fw-bold mb-0 text-warning fs-15" id="modalBulkGst">PKR 0.00</h6>
                                    </div>
                                    <div class="avatar avatar-sm bg-warning-transparent rounded-circle">
                                        <i class="ri-percent-line fs-14 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="card custom-card border shadow-none mb-0">
                            <div class="card-body p-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="d-block text-muted fs-11 mb-1">Total Net Value</span>
                                        <h6 class="fw-bold mb-0 text-success fs-15" id="modalBulkTotalAmount">PKR 0.00</h6>
                                    </div>
                                    <div class="avatar avatar-sm bg-success-transparent rounded-circle">
                                        <i class="ri-wallet-3-line fs-14 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview Table Header & Info --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold text-dark fs-12">Selected Invoices Preview</span>
                    <span class="text-muted fs-11 d-flex align-items-center gap-1">
                        <i class="ri-information-line text-primary fs-13"></i> Click <i class="ri-close-circle-line text-danger fw-bold"></i> to remove an invoice from this batch
                    </span>
                </div>
                <div class="fbr-table-container table-responsive rounded border">
                    <table class="table text-nowrap listing-table table-hover align-middle mb-0" id="modalBulkPreviewTable">
                        <thead>
                            <tr>
                                <th style="width: 36px;" class="text-center">#</th>
                                <th>Doc No</th>
                                <th>Invoice Date</th>
                                <th>Customer</th>
                                <th class="text-end">Amount (PKR)</th>
                                <th class="text-end">GST (PKR)</th>
                                <th class="text-end">Net (PKR)</th>
                                <th class="text-center" style="width: 50px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="modalBulkPreviewBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end fw-semibold text-dark">Totals:</th>
                                <th class="text-end font-monospace text-muted" id="modalBulkFooterAmount">0.00</th>
                                <th class="text-end font-monospace text-warning" id="modalBulkFooterGst">0.00</th>
                                <th class="text-end font-monospace fw-bold text-dark" id="modalBulkFooterNet">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-info py-2 px-3 mt-3 mb-0 d-flex align-items-center border-0 bg-info-transparent text-info fs-12">
                    <i class="fas fa-info-circle me-2 fs-14 text-info"></i>
                    <span>Submitting will transmit these invoices to the FBR live portal. Ensure invoice details and tax calculations are correct before posting.</span>
                </div>

                <form id="bulkPostFbrForm" action="{{ route('invoices.bulk-post-fbr') }}" method="POST" class="d-none">
                    @csrf
                    <input type="hidden" name="fbr_status" value="{{ $fbrStatus ?? 'not_posted' }}">
                    <div id="bulkPostHiddenInputs"></div>
                </form>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary btn-wave" id="modalConfirmSubmitFbrBtn" onclick="submitBulkFbrForm()">
                    <i class="fas fa-paper-plane me-1"></i> <span id="modalSubmitBtnLabel">Confirm & Submit to FBR</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('build/assets/select2.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/js/bootstrap-datepicker.min.js"></script>

<script>
    let deleteInvoiceId = null;
    let postFbrInvoiceId = null;
    let cancelFbrInvoiceId = null;
//	document.addEventListener('DOMContentLoaded', function () {
//    // Toggle dropdown on button click
//    document.querySelectorAll('.action-toggle').forEach(btn => {
//        btn.addEventListener('click', function (e) {
//            e.stopPropagation();
//            
//            const wrapper = this.parentElement;
//            const dropdown = wrapper.querySelector('.slide-dropdown');
//            
//            // Close all other dropdowns first
//            document.querySelectorAll('.slide-dropdown.active').forEach(d => {
//                if (d !== dropdown) {
//                    d.classList.remove('active');
//                    d.parentElement.querySelector('.action-toggle').classList.remove('active');
//                }
//            });
//            
//            // Toggle current dropdown
//            dropdown.classList.toggle('active');
//            this.classList.toggle('active');
//        });
//    });
//
//    // Close dropdown when clicking outside
//    document.addEventListener('click', function (e) {
//        document.querySelectorAll('.slide-dropdown.active').forEach(dropdown => {
//            const wrapper = dropdown.parentElement;
//            
//            if (!wrapper.contains(e.target)) {
//                dropdown.classList.remove('active');
//                wrapper.querySelector('.action-toggle').classList.remove('active');
//            }
//        });
//    });
//});

    // ── Enhance date pickers (Start / End) ─────────────────────
    $(function () {
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
    // ── Auto-hide flash success alerts (errors remain until dismissed) ──────
    setTimeout(function () {
        document.querySelectorAll('.alert-success').forEach(function(a) {
            a.classList.remove('show');
            setTimeout(function() { a.remove(); }, 150);
        });
    }, 5000);

    // ── Confirm delete ────────────────────────────────────────
    function confirmDelete(id, docNo) {
        deleteInvoiceId = id;
        document.getElementById('deleteInvoiceDocNo').textContent = docNo || 'this invoice';
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteInvoiceModal'));
        modal.show();
        return false;
    }
    document.getElementById('confirmInvoiceDeleteBtn').addEventListener('click', function() {
        if (deleteInvoiceId) {
            document.getElementById('delete-form-' + deleteInvoiceId).submit();
        }
    });

    function confirmPostFbr(id, docNo) {
        postFbrInvoiceId = id;
        document.getElementById('postFbrInvoiceDocNo').textContent = docNo || 'this invoice';
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('postFbrInvoiceModal'));
        modal.show();
        return false;
    }
    document.getElementById('confirmPostFbrBtn').addEventListener('click', function() {
        if (postFbrInvoiceId) {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Posting to FBR, please wait...';
            
            const modalEl = document.getElementById('postFbrInvoiceModal');
            if (modalEl) {
                const cancelBtn = modalEl.querySelector('button[data-bs-dismiss="modal"]');
                if (cancelBtn) cancelBtn.disabled = true;
                const closeBtn = modalEl.querySelector('.btn-close');
                if (closeBtn) closeBtn.disabled = true;
            }

            document.getElementById('post-fbr-form-' + postFbrInvoiceId).submit();
        }
    });

    function confirmCancelFbr(id, docNo) {
        cancelFbrInvoiceId = id;
        document.getElementById('cancelFbrInvoiceDocNo').textContent = docNo || 'this invoice';
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('cancelFbrInvoiceModal'));
        modal.show();
        return false;
    }
    document.getElementById('confirmCancelFbrBtn').addEventListener('click', function() {
        if (cancelFbrInvoiceId) {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Marking as Cancelled...';
            
            const modalEl = document.getElementById('cancelFbrInvoiceModal');
            if (modalEl) {
                const closeBtn1 = modalEl.querySelector('button[data-bs-dismiss="modal"]');
                if (closeBtn1) closeBtn1.disabled = true;
                const closeBtn2 = modalEl.querySelector('.btn-close');
                if (closeBtn2) closeBtn2.disabled = true;
            }

            document.getElementById('cancel-fbr-form-' + cancelFbrInvoiceId).submit();
        }
    });

    // ── FBR Status Tab Filter ────────────────────────────────
    function filterFbrStatus(status) {
        const search = document.getElementById('searchInvoices') ? document.getElementById('searchInvoices').value : '';
        const start  = document.getElementById('startDate') ? document.getElementById('startDate').value : '';
        const end    = document.getElementById('endDate') ? document.getElementById('endDate').value : '';
        const params = new URLSearchParams();
        if (status) params.set('fbr_status', status);
        if (search) params.set('search', search);
        if (start)  params.set('start_date', start);
        if (end)    params.set('end_date', end);
        window.location.href = '{{ route("invoices.index") }}?' + params.toString();
    }

    // ── Debounced search ──────────────────────────────────────
    let searchTimer = null;
    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            const search    = document.getElementById('searchInvoices') ? document.getElementById('searchInvoices').value : '';
            const start     = document.getElementById('startDate') ? document.getElementById('startDate').value : '';
            const end       = document.getElementById('endDate') ? document.getElementById('endDate').value : '';
            const fbrStatus = document.getElementById('fbrStatusInput') ? document.getElementById('fbrStatusInput').value : 'not_posted';
            const params = new URLSearchParams();
            if (fbrStatus) params.set('fbr_status', fbrStatus);
            if (search) params.set('search', search);
            if (start)  params.set('start_date', start);
            if (end)    params.set('end_date', end);
            window.location.href = '{{ route("invoices.index") }}?' + params.toString();
        }, 500);
    }

    // ── Select all & Bulk Post Selection ─────────────────────
    function onInvoiceCheckboxChange() {
        const checkedBoxes = document.querySelectorAll('.invoice-checkbox:checked');
        const count = checkedBoxes.length;
        const badge = document.getElementById('bulkPostCountBadge');
        const label = document.getElementById('bulkPostBtnLabel');

        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('d-none');
                if (label) label.textContent = 'Post Selected to FBR';
            } else {
                badge.classList.add('d-none');
                if (label) label.textContent = 'Post to FBR';
            }
        }

        // Update selectAll state
        const allBoxes = document.querySelectorAll('.invoice-checkbox');
        const selectAllBox = document.getElementById('selectAll');
        if (selectAllBox && allBoxes.length > 0) {
            selectAllBox.checked = (allBoxes.length === count);
        }
    }

    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.invoice-checkbox').forEach(function(cb) {
            cb.checked = checked;
        });
        onInvoiceCheckboxChange();
    }

    function openBulkPostModal() {
        const checkedBoxes = Array.from(document.querySelectorAll('.invoice-checkbox:checked'));
        if (checkedBoxes.length === 0) {
            alert('Please select at least one invoice to post to FBR.');
            return;
        }

        renderBulkModalPreview();
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkPostFbrModal'));
        modal.show();
    }

    function renderBulkModalPreview() {
        const checkedBoxes = Array.from(document.querySelectorAll('.invoice-checkbox:checked'));
        const tbody = document.getElementById('modalBulkPreviewBody');
        const countEl = document.getElementById('modalBulkCount');
        const amountEl = document.getElementById('modalBulkAmount');
        const gstEl = document.getElementById('modalBulkGst');
        const totalEl = document.getElementById('modalBulkTotalAmount');
        const footerAmountEl = document.getElementById('modalBulkFooterAmount');
        const footerGstEl = document.getElementById('modalBulkFooterGst');
        const footerNetEl = document.getElementById('modalBulkFooterNet');
        const submitBtnLabel = document.getElementById('modalSubmitBtnLabel');

        let totalAmountVal = 0;
        let totalGstVal = 0;
        let totalNetVal = 0;
        let html = '';

        const headerInvoicesPill = document.getElementById('modalBulkHeaderInvoicesPill');
        const headerAmountPill = document.getElementById('modalBulkHeaderAmountPill');

        checkedBoxes.forEach(function(cb, index) {
            const id = cb.value;
            const docNo = cb.getAttribute('data-docno') || 'N/A';
            const date = cb.getAttribute('data-date') || '-';
            const customer = cb.getAttribute('data-customer') || 'N/A';
            const amountVal = parseFloat(cb.getAttribute('data-amount')) || 0;
            const gstVal = parseFloat(cb.getAttribute('data-gst')) || 0;
            const netVal = parseFloat(cb.getAttribute('data-net')) || 0;

            totalAmountVal += amountVal;
            totalGstVal += gstVal;
            totalNetVal += netVal;

            const amountFormatted = amountVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const gstFormatted = gstVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const netFormatted = netVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            html += `
                <tr id="modal_row_${id}">
                    <td class="text-center text-muted font-monospace fs-11">${index + 1}</td>
                    <td><span class="badge bg-primary-transparent text-primary font-monospace fw-bold fs-11">${docNo}</span></td>
                    <td><span class="fs-12 text-dark font-monospace">${date}</span></td>
                    <td><div class="fw-semibold text-dark fs-12">${customer}</div></td>
                    <td class="text-end font-monospace text-muted">${amountFormatted}</td>
                    <td class="text-end font-monospace text-muted">${gstFormatted}</td>
                    <td class="text-end font-monospace fw-bold text-dark">${netFormatted}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-icon btn-outline-danger" 
                                onclick="removeModalBulkRow('${id}')" 
                                title="Remove from batch">
                            <i class="ri-close-line fs-14"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        if (tbody) tbody.innerHTML = html;
        if (countEl) countEl.textContent = checkedBoxes.length;
        
        const formattedAmountTotal = 'PKR ' + totalAmountVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const formattedGstTotal = 'PKR ' + totalGstVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const formattedNetTotal = 'PKR ' + totalNetVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (amountEl) amountEl.textContent = formattedAmountTotal;
        if (gstEl) gstEl.textContent = formattedGstTotal;
        if (totalEl) totalEl.textContent = formattedNetTotal;

        if (headerInvoicesPill) headerInvoicesPill.innerHTML = '<i class="ri-file-text-line me-1"></i> ' + checkedBoxes.length + ' Invoices';
        if (headerAmountPill) headerAmountPill.innerHTML = '<i class="ri-money-dollar-circle-line me-1"></i> ' + formattedNetTotal;

        if (footerAmountEl) footerAmountEl.textContent = totalAmountVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (footerGstEl) footerGstEl.textContent = totalGstVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (footerNetEl) footerNetEl.textContent = totalNetVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (submitBtnLabel) submitBtnLabel.textContent = `Confirm & Submit (${checkedBoxes.length}) to FBR`;
    }

    function removeModalBulkRow(id) {
        const cb = document.querySelector(`.invoice-checkbox[value="${id}"]`);
        if (cb) {
            cb.checked = false;
        }
        onInvoiceCheckboxChange();

        const remaining = document.querySelectorAll('.invoice-checkbox:checked');
        if (remaining.length === 0) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('bulkPostFbrModal'));
            if (modal) modal.hide();
        } else {
            renderBulkModalPreview();
        }
    }

    function submitBulkFbrForm() {
        const checkedBoxes = Array.from(document.querySelectorAll('.invoice-checkbox:checked'));
        if (checkedBoxes.length === 0) {
            alert('No invoices selected.');
            return;
        }

        const container = document.getElementById('bulkPostHiddenInputs');
        if (container) {
            container.innerHTML = '';
            checkedBoxes.forEach(function(cb) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'invoice_ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });
        }

        const btn = document.getElementById('modalConfirmSubmitFbrBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Submitting to FBR...';
        }

        document.getElementById('bulkPostFbrForm').submit();
    }

    // ══════════════════════════════════════════════════════════
    //  Universal Modal Opener
    // ══════════════════════════════════════════════════════════
    function openInvoiceModal(mode, invoiceId) {
        invoiceId = invoiceId || null;

        const modalEl    = document.getElementById('invoice_modal');
        const modalBody  = document.getElementById('invoice_modal_body');
        const modalTitle = document.getElementById('invoice_modal_title');
        const saveBtn    = document.getElementById('invoice_modal_save_btn');
        const saveLabel  = document.getElementById('invoice_modal_save_label');
        const bsModal    = bootstrap.Modal.getOrCreateInstance(modalEl);

        const titles = { create: 'Add Invoice', edit: 'Edit Invoice', show: 'Invoice Details' };
        modalTitle.textContent = titles[mode] || 'Invoice';

        if (mode === 'show') {
            saveBtn.classList.add('d-none');
        } else {
            saveBtn.classList.remove('d-none');
            saveLabel.textContent = mode === 'edit' ? 'Update Invoice' : 'Save Invoice';
        }

        modalBody.innerHTML = '<div class="text-center py-5">' +
            '<div class="spinner-border text-primary" role="status"></div>' +
            '<p class="mt-2 text-muted">Loading...</p></div>';

        bsModal.show();

        const url = mode === 'create'
            ? '{{ route("invoices.modal") }}?mode=create'
            : '/invoices/' + invoiceId + '/modal?mode=' + mode;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
            }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Server error: ' + res.status);
            return res.text();
        })
        .then(function(html) {
            modalBody.innerHTML = html;

            // Execute <script> tags injected via innerHTML
            Array.from(modalBody.getElementsByTagName('script')).forEach(function(old) {
                const s = document.createElement('script');
                if (old.src) { s.src = old.src; s.async = false; }
                else         { s.textContent = old.textContent; }
                document.body.appendChild(s);
            });

           
           // Wire Save Button → AJAX submit
saveBtn.onclick = function () {
    const form = modalBody.querySelector('form');
    if (!form) return;

    // ✅ Trigger native HTML5 validation first
    if (!form.reportValidity()) return;

    // ✅ Check at least one line item row exists
    const rows = form.querySelectorAll('.ifp-row');
    if (rows.length === 0) {
        showToast('error', 'At least one line item is required.');
        return;
    }

    // ✅ Check at least one row has qty > 0 and rate > 0
    let hasValidItem = false;
    rows.forEach(function(row) {
        const qty  = parseFloat(row.querySelector('.ifp-qty')?.value)  || 0;
        const rateEl = row.querySelector('.ifp-col-sale-rate .ifp-rate') || row.querySelector('.ifp-rate');
        const rateRaw = (rateEl && rateEl.value) ? String(rateEl.value).replace(/,/g, '').trim() : '';
        const rate = parseFloat(rateRaw) || 0;
        if (qty > 0 && rate > 0) hasValidItem = true;
    });
    if (!hasValidItem) {
        showToast('error', 'At least one line item must have quantity and rate greater than 0.');
        return;
    }

    // Remove visual thousand separators before collecting FormData.
    if (typeof window.ifpNormalizeFormattedInputs === 'function') {
        window.ifpNormalizeFormattedInputs(form);
    }

    // ✅ All good — proceed with AJAX
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            bsModal.hide();
            showToast('success', data.message);
            setTimeout(function() { window.location.reload(); }, 1000);
        } else {
            showModalErrors(data.errors || {});
            if (data.message) showToast('error', data.message);
        }
    })
    .catch(function() {
        showToast('error', 'Submission failed. Please try again.');
    })
    .finally(function() {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> <span>' +
            (mode === 'edit' ? 'Update Invoice' : 'Save Invoice') + '</span>';
    });
}; 
        })
        .catch(function(err) {
            modalBody.innerHTML = '<div class="alert alert-danger m-3">' +
                '<i class="fas fa-exclamation-circle me-2"></i>' +
                'Failed to load. Please try again.<br>' +
                '<small class="text-muted">' + err.message + '</small></div>';
        });
    }

    // ── Show validation errors inside modal ───────────────────
    function showModalErrors(errors) {
        const modalBody = document.getElementById('invoice_modal_body');
        modalBody.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
        modalBody.querySelectorAll('.ajax-error-feedback').forEach(function(el) { el.remove(); });

        let firstField = null;
        Object.entries(errors).forEach(function([field, messages]) {
            const name  = field.replace(/\.(\d+)\./g, '[$1][').replace(/\.(\w+)$/, '][$1]');
            const input = modalBody.querySelector('[name="' + field + '"], [name="' + name + '"]');
            if (input) {
                input.classList.add('is-invalid');
                const fb = document.createElement('div');
                fb.className = 'invalid-feedback ajax-error-feedback';
                fb.style.display = 'block';
                fb.textContent = messages[0];
                input.insertAdjacentElement('afterend', fb);
                if (!firstField) firstField = input;
            }
        });
        if (firstField) {
            firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstField.focus();
        }
    }

    // ── Print modal ───────────────────────────────────────────
    var _printModalInvoiceId = null;

    function openPrintModal(invoiceId, docNo) {
        _printModalInvoiceId = invoiceId;
        document.getElementById('invoice_print_docno').textContent = docNo || ('#' + invoiceId);
        document.getElementById('invoice_print_orientation').value = 'portrait';
        setPrintFrameSrc('portrait');
        var modalEl = document.getElementById('invoice_print_modal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function setPrintFrameSrc(orientation) {
        if (!_printModalInvoiceId) return;
        var baseUrl = '{{ url("invoices") }}/' + _printModalInvoiceId + '/print-view';
        var url = baseUrl + '?orientation=' + encodeURIComponent(orientation) + '&_=' + Date.now();
        var frame = document.getElementById('invoice_print_frame');
        frame.src = url;
    }

    document.getElementById('invoice_print_apply_orientation').addEventListener('click', function () {
        var orient = document.getElementById('invoice_print_orientation').value;
        setPrintFrameSrc(orient);
    });

    document.getElementById('invoice_print_orientation').addEventListener('change', function () {
        var orient = this.value;
        setPrintFrameSrc(orient);
    });

    /** Save/print PDF filename: GD-DI-YYYYMMDD-{invoiceId} (e.g. GD-DI-20260514-19) */
    function buildGDPrintSaveTitle(invoiceId) {
        var d = new Date();
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return 'GD-DI-' + y + m + day + '-' + String(invoiceId);
    }

    document.getElementById('invoice_print_do_print').addEventListener('click', function () {
        var frame = document.getElementById('invoice_print_frame');
        if (!frame || !frame.contentWindow) return;
        var iw = frame.contentWindow;
        var parentTitle = document.title;
        var iframeSavedTitle = '';
        try {
            if (!iw.document) {
                showToast('error', 'Invoice preview is still loading. Please wait and try again.');
                return;
            }
            iframeSavedTitle = iw.document.title || '';
            var saveTitle = (_printModalInvoiceId && iw.document)
                ? buildGDPrintSaveTitle(_printModalInvoiceId)
                : iframeSavedTitle;
            if (iw.document && saveTitle) {
                iw.document.title = saveTitle;
            }
            document.title = saveTitle;
            var restored = false;
            function restoreTabTitle() {
                if (restored) return;
                restored = true;
                document.title = parentTitle;
                try {
                    if (iw.document) {
                        iw.document.title = iframeSavedTitle;
                    }
                    iw.removeEventListener('afterprint', restoreTabTitle);
                } catch (e2) {}
            }
            iw.addEventListener('afterprint', restoreTabTitle);
            setTimeout(restoreTabTitle, 180000);
            iw.focus();
            iw.print();
        } catch (e) {
            document.title = parentTitle;
            try {
                if (iw.document) {
                    iw.document.title = iframeSavedTitle;
                }
            } catch (e2) {}
            showToast('error', 'Could not open print dialog. Try printing from the browser (Ctrl+P).');
        }
    });

    // ── FBR Response popup ────────────────────────────────────
    function openFbrResponseModal(resp) {
        var summaryWrap = document.getElementById('fbr_response_summary');
        var summaryCard = document.getElementById('fbr_summary_card');
        var summaryStrip = document.getElementById('fbr_summary_strip');
        var badgeStatus = document.getElementById('fbr_summary_badge_status');
        var badgeCode = document.getElementById('fbr_summary_badge_code');
        var labelCode = document.getElementById('fbr_label_code');
        var labelMessage = document.getElementById('fbr_label_message');
        var labelDesc = document.getElementById('fbr_label_description');
        var summaryCode = document.getElementById('fbr_summary_code');
        var summaryMessage = document.getElementById('fbr_summary_message');
        var summaryDescription = document.getElementById('fbr_summary_description');

        if (summaryWrap) summaryWrap.classList.add('d-none');
        if (summaryCard) {
            summaryCard.classList.remove('fbr-summary--invalid', 'fbr-summary--valid');
        }
        if (summaryStrip) {
            summaryStrip.classList.add('d-none');
            summaryStrip.classList.remove('d-flex');
        }
        if (badgeStatus) {
            badgeStatus.textContent = '';
            badgeStatus.className = 'badge rounded-pill';
        }
        if (badgeCode) {
            badgeCode.textContent = '';
            badgeCode.className = 'badge rounded-pill bg-secondary-subtle text-secondary-emphasis';
        }
        if (labelCode) labelCode.textContent = 'Code';
        if (labelMessage) labelMessage.textContent = 'Message';
        if (labelDesc) labelDesc.textContent = 'Description';
        if (summaryCode) summaryCode.value = '';
        if (summaryMessage) summaryMessage.value = '';
        if (summaryDescription) summaryDescription.value = '';

        var bodyEl = document.getElementById('fbr_response_modal_body');
        if (bodyEl) {
            var text = resp || '-';
            text = text.trim();

            // Decode HTML entities like &quot; to real characters first
            if (text.indexOf('&') !== -1) {
                try {
                    var textarea = document.createElement('textarea');
                    textarea.innerHTML = text;
                    text = textarea.value;
                } catch (e) {
                    // ignore if decoding fails
                }
            }

            // If the whole payload is a quoted JSON string like "{\"key\":\"val\"}"
            // decode it once so we remove the extra \" noise.
            if (text.startsWith('"') && text.endsWith('"')) {
                try {
                    text = JSON.parse(text); // becomes inner string
                } catch (e) {
                    // ignore and keep original text
                }
            }

            // Now, if it looks like JSON, pretty-print it
            if (text.startsWith('{') || text.startsWith('[')) {
                try {
                    var parsed = JSON.parse(text);

                    if (parsed && typeof parsed === 'object' && summaryWrap) {
                        var vr = parsed.validationResponse;
                        var fault = parsed.fault;

                        if (fault && typeof fault === 'object') {
                            var code = fault.code ?? fault.statusCode ?? '';
                            var message = fault.message ?? parsed.message ?? '';
                            var description = fault.description ?? parsed.description ?? parsed.detail ?? '';
                            if (code || message || description) {
                                summaryWrap.classList.remove('d-none');
                                if (summaryCode) summaryCode.value = code !== '' && code !== undefined ? String(code) : '-';
                                if (summaryMessage) summaryMessage.value = message || '-';
                                if (summaryDescription) summaryDescription.value = description || '-';
                                if (summaryCard) summaryCard.classList.add('fbr-summary--invalid');
                            }
                        } else if (vr && typeof vr === 'object') {
                            var errCode = vr.errorCode != null && vr.errorCode !== '' ? String(vr.errorCode) : '';
                            var errText = vr.error != null ? String(vr.error).trim() : '';
                            var st = vr.status != null ? String(vr.status) : '';
                            var stc = vr.statusCode != null ? String(vr.statusCode) : '';
                            var isValidResponse = stc === '00';

                            var errorLines = [];
                            if (errText) {
                                errorLines.push(errCode ? ('[' + errCode + '] ' + errText) : errText);
                            }
                            if (Array.isArray(vr.invoiceStatuses)) {
                                vr.invoiceStatuses.forEach(function (item) {
                                    if (!item || typeof item !== 'object') return;
                                    var lineErr = item.error != null ? String(item.error).trim() : '';
                                    if (!lineErr) return;
                                    var lineCode = item.errorCode != null && item.errorCode !== '' ? String(item.errorCode) : '';
                                    var lineNo = item.itemSNo != null && item.itemSNo !== '' ? String(item.itemSNo) : '';
                                    var head = [];
                                    if (lineNo) head.push('Item ' + lineNo);
                                    if (lineCode) head.push('[' + lineCode + ']');
                                    errorLines.push(head.length ? (head.join(' ') + ' ' + lineErr) : lineErr);
                                });
                            }
                            var combinedErrors = errorLines.join('\n').trim();

                            var hasSummary = isValidResponse || errCode || errText || st || stc || combinedErrors;
                            if (hasSummary) {
                                summaryWrap.classList.remove('d-none');
                                if (isValidResponse) {
                                    if (labelCode) labelCode.textContent = 'Code';
                                    if (labelMessage) labelMessage.textContent = 'Status';
                                    if (labelDesc) labelDesc.textContent = 'Message';
                                    if (summaryCode) summaryCode.value = stc || '-';
                                    if (summaryMessage) {
                                        var okParts = [];
                                        if (st) okParts.push(st);
                                        if (stc) okParts.push('(' + stc + ')');
                                        summaryMessage.value = okParts.length ? okParts.join(' ') : '-';
                                    }
                                    if (summaryDescription) {
                                        summaryDescription.value = 'Invoice Successfully Posted to FBR';
                                        summaryDescription.rows = 3;
                                    }
                                    if (summaryCard) summaryCard.classList.add('fbr-summary--valid');
                                } else {
                                    if (labelCode) labelCode.textContent = 'Error code';
                                    if (labelMessage) labelMessage.textContent = 'Status';
                                    if (labelDesc) labelDesc.textContent = 'Error details';
                                    if (summaryCode) summaryCode.value = errCode || '-';
                                    if (summaryMessage) {
                                        var msgParts = [];
                                        if (st) msgParts.push(st);
                                        if (stc) msgParts.push('(' + stc + ')');
                                        summaryMessage.value = msgParts.length ? msgParts.join(' ') : '-';
                                    }
                                    if (summaryDescription) {
                                        summaryDescription.value = combinedErrors || '-';
                                        if (combinedErrors) {
                                            var ln = combinedErrors.split('\n').length;
                                            summaryDescription.rows = Math.min(14, Math.max(3, ln + 1));
                                        } else {
                                            summaryDescription.rows = 3;
                                        }
                                    }
                                    if (summaryCard) summaryCard.classList.add('fbr-summary--invalid');
                                }

                                if (summaryStrip && badgeStatus && badgeCode) {
                                    summaryStrip.classList.remove('d-none');
                                    summaryStrip.classList.add('d-flex');
                                    if (isValidResponse) {
                                        badgeStatus.textContent = 'Valid';
                                        badgeStatus.className = 'badge rounded-pill bg-success-subtle text-success-emphasis';
                                        badgeCode.textContent = stc ? stc : '';
                                        badgeCode.className = 'badge rounded-pill bg-secondary-subtle text-secondary-emphasis';
                                    } else {
                                        var stLower = st.toLowerCase();
                                        if (stLower === 'invalid') {
                                            badgeStatus.textContent = 'Invalid';
                                            badgeStatus.className = 'badge rounded-pill bg-danger-subtle text-danger-emphasis';
                                        } else if (stLower === 'valid') {
                                            badgeStatus.textContent = 'Valid';
                                            badgeStatus.className = 'badge rounded-pill bg-success-subtle text-success-emphasis';
                                        } else if (st) {
                                            badgeStatus.textContent = st;
                                            badgeStatus.className = 'badge rounded-pill bg-secondary-subtle text-secondary-emphasis';
                                        }
                                        if (errCode) {
                                            badgeCode.textContent = 'Error ' + errCode;
                                            badgeCode.className = 'badge rounded-pill bg-dark-subtle text-dark-emphasis';
                                        } else if (stc) {
                                            badgeCode.textContent = 'Code ' + stc;
                                        } else {
                                            badgeCode.textContent = '';
                                        }
                                    }
                                }
                            }
                        } else {
                            var codeRoot = parsed.code != null ? parsed.code : '';
                            var msgRoot = parsed.message != null ? parsed.message : '';
                            var descRoot = parsed.description != null ? parsed.description : (parsed.detail != null ? parsed.detail : '');
                            if (codeRoot !== '' || msgRoot !== '' || descRoot !== '') {
                                summaryWrap.classList.remove('d-none');
                                if (summaryCode) summaryCode.value = codeRoot !== '' ? String(codeRoot) : '-';
                                if (summaryMessage) summaryMessage.value = msgRoot || '-';
                                if (summaryDescription) summaryDescription.value = descRoot || '-';
                                if (summaryCard) summaryCard.classList.add('fbr-summary--invalid');
                            }
                        }
                    }

                    text = JSON.stringify(parsed, null, 2);
                } catch (e) {
                    // leave as raw text if not valid JSON
                }
            }

            bodyEl.textContent = text;
        }

        var modalEl = document.getElementById('fbr_response_modal');
        if (!modalEl) return;
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function copyFbrResponseToClipboard() {
        var bodyEl = document.getElementById('fbr_response_modal_body');
        if (!bodyEl) return;

        var text = bodyEl.textContent || '';
        if (!text) {
            showToast('error', 'Nothing to copy.');
            return;
        }

        navigator.clipboard.writeText(text).then(function () {
            showToast('success', 'FBR response copied to clipboard.');
        }).catch(function () {
            showToast('error', 'Unable to copy. Please copy manually.');
        });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.fbr-response-btn');
        if (!btn) return;
        var resp = btn.getAttribute('data-response') || '';
        openFbrResponseModal(resp);
    });

    // ── Toast notification ────────────────────────────────────
    function showToast(type, message) {
        const existing = document.getElementById('ifp_toast');
        if (existing) existing.remove();

        const bg   = type === 'success' ? 'bg-success' : 'bg-danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        const toast = document.createElement('div');
        toast.id = 'ifp_toast';
        toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:280px;';
        toast.innerHTML = '<div class="alert ' + bg + ' text-white shadow d-flex align-items-center gap-2 mb-0">' +
            '<i class="fas fa-' + icon + '"></i>' +
            '<span>' + message + '</span>' +
            '<button type="button" class="btn-close btn-close-white ms-auto" onclick="document.getElementById(\'ifp_toast\').remove()"></button>' +
            '</div>';
        document.body.appendChild(toast);
        setTimeout(function() { if (document.getElementById('ifp_toast')) document.getElementById('ifp_toast').remove(); }, 4000);
    }
</script>
@endsection