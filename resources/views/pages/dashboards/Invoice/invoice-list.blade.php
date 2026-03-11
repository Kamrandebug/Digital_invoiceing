{{-- Invoice List --}}

@extends('layouts.master')

@section('styles')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
@endsection

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Invoice Management</h1>
        <div class="d-flex float-end">
            <button type="button"
                    class="btn btn-sm btn-primary btn-wave waves-light"
                    onclick="openInvoiceModal('create')">
                <i class="ri-add-line fw-medium align-middle me-1"></i> Add Invoice
            </button>
            <div class="dropdown ms-2">
                <button class="btn btn-icon btn-secondary-light btn-sm btn-wave waves-light"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0);">Import</a></li>
                    <li><a class="dropdown-item" href="#">Bulk Print</a></li>
                    <li><a class="dropdown-item" href="{{ route('invoices.export-csv') }}">Export CSV</a></li>
                </ul>
            </div>
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
                        <form action="{{ route('invoices.index') }}" method="GET">
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
                                            <label>Start Date</label>
                                            <input type="date" class="form-control form-control-sm"
                                                   name="start_date" id="startDate"
                                                   value="{{ request('start_date') }}"
                                                   onchange="debounceSearch()">
                                        </div>
                                        <div class="col-md-4">
                                            <label>End Date</label>
                                            <input type="date" class="form-control form-control-sm"
                                                   name="end_date" id="endDate"
                                                   value="{{ request('end_date') }}"
                                                   onchange="debounceSearch()">
                                        </div>
                                        <div class="col">
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
                            <th>Doc No. <i class="fas fa-sort"></i></th>
                            <th>Invoice Date <i class="fas fa-sort"></i></th>
                            <th>Due Date</th>
                            <th width="25%">Customer</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th style="min-width:100px !important; max-width:100px !important; width:100px !important;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        <tr>
                            {{-- HeaderID is the unique key from getlistingdata() --}}
                            <td>
                                <input type="checkbox" class="invoice-checkbox form-check-input" value="{{ $invoice->HeaderID }}" />
                            </td>

                            <td>{{ $invoice->DocNo ?? 'N/A' }}</td>

                            <td>
                                {{ $invoice->InvoiceDate
                                    ? \Carbon\Carbon::parse($invoice->InvoiceDate)->format('d/m/Y')
                                    : '-' }}
                            </td>

                            <td>
                                {{ $invoice->DueDate
                                    ? \Carbon\Carbon::parse($invoice->DueDate)->format('d/m/Y')
                                    : '-' }}
                            </td>

                            <td>{{ $invoice->CustomerDisplayName ?? 'N/A' }}</td>

                            <td>{{ number_format($invoice->TotalAmount ?? 0, 2) }} PKR</td>

                            <td>
                                {{-- Status is an integer: 1 = Active, 2 = Archived --}}
                                <span class="status-badge  {{ $invoice->Status == 1 ? 'status-active' : 'status-inactive' }}">
                                    {{ $invoice->Status == 1 ? 'Active' : 'Archived' }}
                                </span>
                            </td>

                            <td>
								
								<div class="action-wrapper">
									<div class="btn-group btn-listing">
									  <button class="btn btn-outline-primary btn-list-left" onclick="openInvoiceModal('show', {{ $invoice->HeaderID }})">
										<i class="fas fa-eye"></i> View
									  </button>
									  <button type="button" class="btn btn-primary btn-list-right dropdown-toggle dropdown-toggle-split me-2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<span class="sr-only">Toggle Dropdown</span>
									  </button>
									  <ul class="dropdown-menu">
										
										<button class="dropdown-item"
												onclick="openInvoiceModal('edit', {{ $invoice->HeaderID }})">
											<i class="fas fa-edit"></i> Edit
										</button>

										<form action="{{ route('invoices.delete_transaction', $invoice->HeaderID) }}"
											  method="POST" onsubmit="return confirmDelete('{{ $invoice->DocNo }}')">
											@csrf
											@method('DELETE')
											<button class="dropdown-item">
												<i class="fas fa-trash"></i> Delete
											</button>
										</form>
									  </ul>
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
                                    <button class="btn btn-primary" onclick="openInvoiceModal('create')">
                                        <i class="fas fa-plus"></i> Create Your First Invoice
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

@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('build/assets/select2.js') }}"></script>

<script>
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
    // ── Auto-hide flash alerts ────────────────────────────────
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function(a) {
            a.classList.remove('show');
            setTimeout(function() { a.remove(); }, 150);
        });
    }, 5000);

    // ── Confirm delete ────────────────────────────────────────
    function confirmDelete(docNo) {
        return confirm('Are you sure you want to archive invoice "' + docNo + '"?');
    }

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
        const rate = parseFloat(row.querySelector('.ifp-rate')?.value) || 0;
        if (qty > 0 && rate > 0) hasValidItem = true;
    });
    if (!hasValidItem) {
        showToast('error', 'At least one line item must have quantity and rate greater than 0.');
        return;
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