{{--
    Product Management Page
    Redesigned to match Customer list theme (card layout, table, action buttons).
--}}

@extends('layouts.master')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">
<style>
    .btn-margin { margin-top: 20px; }
    .balance-positive { color: #28a745; }
    .balance-negative { color: #dc3545; }
    .balance-zero { color: var(--cm-text-muted); }
    .stock-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    .stock-in  { background: #d4edda; color: #155724; }
    .stock-low { background: #fff3cd; color: #856404; }
    .stock-out { background: #f8d7da; color: #721c24; }
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
    .status-active   { background: #d4edda; color: #155724; }
    .status-inactive { background: #f8d7da; color: #721c24; }
</style>
@endsection

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Product Management</h1>
        <div class="d-flex float-end">
            <button type="button" class="btn btn-sm btn-primary btn-wave waves-light" onclick="openProductModal('create')">
                <i class="ri-add-line fw-medium align-middle me-1"></i> Add Product
            </button>
            <div class="dropdown ms-2">
                <button class="btn btn-icon btn-secondary-light btn-sm btn-wave waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0);">Import</a></li>
                    <li><a class="dropdown-item" href="#">Bulk Print</a></li>
                    <li><a class="dropdown-item" href="{{ route('products.exportCSV', request()->query()) }}">Export CSV</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle"></i>
    @foreach ($errors->all() as $error)
        {{ $error }}
    @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <div class="col-xs-12">
        <div class="card custom-card mb-1">
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-md-9">
                        <form method="GET" action="{{ route('products.index') }}" id="filterForm">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="header-search d-md-block d-none">
                                        <label>Search Product</label>
                                        <input type="text"
                                            class="header-search-bar form-control form-control-sm bg-white"
                                            name="search"
                                            value="{{ request('search') }}"
                                            placeholder="Search by name, code...">
                                        <a href="javascript:void(0);" class="header-search-icon border-0" style="top: 25px;">
                                            <i class="bi bi-search fs-12"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label>Start Date</label>
                                            <input type="date" class="form-control form-control-sm"
                                                name="start_date" value="{{ request('start_date') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label>End Date</label>
                                            <input type="date" class="form-control form-control-sm"
                                                name="end_date" value="{{ request('end_date') }}">
                                        </div>
                                        <div class="col">
                                            <button class="btn btn-sm btn-primary btn-margin" type="submit">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            <a href="{{ route('products.index') }}" class="btn btn-sm btn-light btn-margin">
                                                <i class="fas fa-times"></i> Clear Filters
                                            </a>
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

<div class="col-xs-12">
    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap" id="productsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Sale Price</th>
                            <th>Cost</th>
                            <th>Current Qty</th>
                            <th>Status</th>
                            <th>Created On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $index => $product)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $product->Code ?? '-' }}</strong></td>
                            <td>
                                <div>{{ $product->Name ?? '-' }}</div>
                                @if(!empty($product->Type))
                                    <small class="text-muted">{{ $product->Type }}</small>
                                @endif
                            </td>
                            <td>{{ $product->Type ?? '-' }}</td>
                            <td>
                                <strong>PKR {{ number_format($product->SalePrice ?? 0, 2) }}</strong>
                            </td>
                            <td>PKR {{ number_format($product->Cost ?? 0, 2) }}</td>
                            <td><strong>{{ $product->CurrentQuantity ?? 0 }}</strong></td>
                            <td>
                                @if(($product->Status ?? 1) == 1)
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Inactive</span>
                                @endif
                            </td>
                            <td style="font-size:13px;">
                                {{ isset($product->CreatedOn) ? \Carbon\Carbon::parse($product->CreatedOn)->format('d M Y') : '-' }}
                            </td>
                            <td>
                                <button type="button" class="action-btn btn-info" title="View"
                                    onclick="openProductModal('show', {{ $product->ID }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="action-btn btn-warning" title="Edit"
                                    onclick="openProductModal('edit', {{ $product->ID }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="action-btn btn-delete"
                                    title="Archive"
                                    onclick="confirmDelete({{ $product->ID }}, '{{ addslashes($product->Name ?? '') }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form id="delete-form-{{ $product->ID }}"
                                      action="{{ route('products.delete_transaction', $product->ID) }}"
                                      method="POST" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-2">No products found.</p>
                                @if(request('search') || request('start_date') || request('end_date'))
                                    <p class="text-muted small">Try adjusting your search or filters.</p>
                                @else
                                    <button type="button" class="btn btn-primary" onclick="openProductModal('create')">
                                        <i class="fas fa-plus"></i> Add Your First Product
                                    </button>
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
                    Showing {{ count($products) }} product(s)
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Product Modal (Add / Edit / View) --}}
<div class="modal fade" id="product_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="product_modal_title">Product</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="product_modal_body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" class="btn btn-sm btn-primary d-none" id="product_modal_save_btn">
                    <i class="fas fa-save"></i>
                    <span id="product_modal_save_label">Save Product</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold text-danger">
                    <i class="fas fa-trash me-2"></i> Archive Product
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to archive <strong id="deleteProductName"></strong>?</p>
                <p class="text-muted fs-13 mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    This will set the product status to archived. It will not be permanently deleted.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i> Yes, Archive It
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    let deleteProductId = null;

    function confirmDelete(id, name) {
        deleteProductId = id;
        document.getElementById('deleteProductName').textContent = name || 'this product';
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteProductId) {
            document.getElementById('delete-form-' + deleteProductId).submit();
        }
    });

    function openProductModal(mode, productId = null) {
        var modalEl = document.getElementById('product_modal');
        var modalBody = document.getElementById('product_modal_body');
        var modalTitle = document.getElementById('product_modal_title');
        var saveBtn = document.getElementById('product_modal_save_btn');
        var saveLabel = document.getElementById('product_modal_save_label');
        var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

        var titles = { create: 'Add Product', edit: 'Edit Product', show: 'Product Details' };
        modalTitle.textContent = titles[mode] || 'Product';

        if (mode === 'show') {
            saveBtn.classList.add('d-none');
        } else {
            saveBtn.classList.remove('d-none');
            saveLabel.textContent = mode === 'edit' ? 'Update Product' : 'Save Product';
        }

        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading...</p></div>';
        bsModal.show();

        var url = mode === 'create'
            ? '{{ route("products.modal") }}?mode=create'
            : '/products/' + productId + '/modal?mode=' + mode;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
            }
        })
        .then(function(res) { if (!res.ok) throw new Error('Server error: ' + res.status); return res.text(); })
        .then(function(html) {
            modalBody.innerHTML = html;
            var scripts = modalBody.getElementsByTagName('script');
            for (var i = 0; i < scripts.length; i++) {
                var s = document.createElement('script');
                if (scripts[i].src) s.src = scripts[i].src; else s.textContent = scripts[i].textContent;
                document.body.appendChild(s);
            }
            saveBtn.onclick = function() {
                var form = modalBody.querySelector('form');
                if (!form) return;
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
                var formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
                    }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.status === 'success') {
                        bsModal.hide();
                        showProductToast('success', data.message);
                        setTimeout(function() { window.location.reload(); }, 1000);
                    } else {
                        showProductModalErrors(data.errors || {});
                        if (data.message) showProductToast('error', data.message);
                    }
                })
                .catch(function(err) {
                    showProductToast('error', 'Submission failed. Please try again.');
                })
                .finally(function() {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> <span id="product_modal_save_label">' + (mode === 'edit' ? 'Update Product' : 'Save Product') + '</span>';
                });
            };
        })
        .catch(function(err) {
            modalBody.innerHTML = '<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle me-2"></i>Failed to load. Please try again.<br><small class="text-muted">' + err.message + '</small></div>';
        });
    }

    function showProductModalErrors(errors) {
        var modalBody = document.getElementById('product_modal_body');
        modalBody.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
        modalBody.querySelectorAll('.ajax-error-feedback').forEach(function(el) { el.remove(); });
        var firstField = null;
        Object.keys(errors).forEach(function(field) {
            var input = modalBody.querySelector('[name="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
                var feedback = document.createElement('div');
                feedback.className = 'invalid-feedback ajax-error-feedback';
                feedback.style.display = 'block';
                feedback.textContent = errors[field][0];
                input.insertAdjacentElement('afterend', feedback);
                if (!firstField) firstField = input;
            }
        });
        if (firstField) {
            firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstField.focus();
        }
    }

    function showProductToast(type, message) {
        var existing = document.getElementById('pfp_toast');
        if (existing) existing.remove();
        var bg = type === 'success' ? 'bg-success' : 'bg-danger';
        var toast = document.createElement('div');
        toast.id = 'pfp_toast';
        toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:280px;';
        toast.innerHTML = '<div class="alert ' + bg + ' text-white shadow d-flex align-items-center gap-2 mb-0"><i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i><span>' + message + '</span><button type="button" class="btn-close btn-close-white ms-auto" onclick="this.closest(\'#pfp_toast\').remove()"></button></div>';
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 4000);
    }
</script>
@endsection
