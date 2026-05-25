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
                            <td colspan="5" class="text-center py-5">
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
