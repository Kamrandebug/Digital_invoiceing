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
        min-width: 10rem;
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
</style>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/css/bootstrap-datepicker3.min.css" rel="stylesheet">
@endsection

@section('content')
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
            @if(session('selected_tenant.permissions.Invoices.Import') == 1 || session('selected_tenant.permissions.Invoices.Print') == 1 || session('selected_tenant.permissions.Invoices.Export') == 1)
            <div class="dropdown ms-2">
                <button class="btn btn-icon btn-secondary-light btn-sm btn-wave waves-light"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu">
                    @tenantCan('Invoices', 'Import')
                    <li><a class="dropdown-item" href="javascript:void(0);">Import</a></li>
                    @endtenantCan
                    @tenantCan('Invoices', 'Print')
                    <li><a class="dropdown-item" href="#">Bulk Print</a></li>
                    @endtenantCan
                    @tenantCan('Invoices', 'Export')
                    <li><a class="dropdown-item" href="{{ route('invoices.export-csv') }}">Export CSV</a></li>
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

{{-- ── Filter Bar ── --}}
<div class="row">
    <div class="col-xs-12">
        <div class="card custom-card mb-1">
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-md-9">
                        <form action="{{ route('invoices.index') }}" method="GET" class="listing-filters-form">
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
                                                    onclick="window.location.href='{{ route('invoices.index') }}'">
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

{{-- ── Invoice Table ── --}}
<div class="col-xs-12">
    <div class="card custom-card">
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
                            $fbrTxId = trim((string) ($invoice->FbrTransactionID ?? ''));
                            $fbrRespRaw = trim((string) ($invoice->FbrResponseJson ?? ''));
                            $fbrRespForCheck = strtolower($fbrRespRaw);
                            $fbrIsValid = $fbrRespForCheck !== '' && str_contains($fbrRespForCheck, 'valid');
                            $fbrLocked = $fbrTxId !== '' && $fbrIsValid;
                        @endphp
                        <tr>
                            {{-- HeaderID is the unique key from getlistingdata() --}}
                            <td>
                                <input type="checkbox" class="invoice-checkbox form-check-input" value="{{ $invoice->HeaderID }}" />
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
                                @endphp
                                @if(!empty($resp))
                                    @php
                                        $isValid = \Illuminate\Support\Str::contains($resp, '\"Valid\"')
                                            || \Illuminate\Support\Str::contains($resp, '"Valid"')
                                            || \Illuminate\Support\Str::contains($resp, 'Valid');
                                        $badgeClass = $isValid
                                            ? 'bg-success-subtle text-success'
                                            : 'bg-secondary-subtle text-secondary';
                                        $badgeLabel = $isValid ? 'FBR Valid' : 'View FBR';
                                    @endphp
                                    <span class="badge rounded-pill fbr-response-btn {{ $badgeClass }}"
                                          style="cursor:pointer; font-size:11px; padding:5px 10px;"
                                          data-response="{{ e($resp) }}"
                                          title="Click to view FBR response">
                                        <i class="fas fa-receipt me-1"></i>{{ $badgeLabel }}
                                    </span>
                                @else
                                    <span class="badge rounded-pill bg-light text-muted">No Response</span>
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
<div class="modal fade" id="postFbrInvoiceModal" tabindex="-1" aria-hidden="true">
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
    // ── Auto-hide flash alerts ────────────────────────────────
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function(a) {
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
            document.getElementById('post-fbr-form-' + postFbrInvoiceId).submit();
        }
    });

    // ── Debounced search ──────────────────────────────────────
    let searchTimer = null;
    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            const search = document.getElementById('searchInvoices').value;
            const start  = document.getElementById('startDate').value;
            const end    = document.getElementById('endDate').value;
            const params = new URLSearchParams();
            if (search) params.set('search', search);
            if (start)  params.set('start_date', start);
            if (end)    params.set('end_date', end);
            window.location.href = '{{ route("invoices.index") }}?' + params.toString();
        }, 500);
    }

    // ── Select all ────────────────────────────────────────────
    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.invoice-checkbox').forEach(function(cb) {
            cb.checked = checked;
        });
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