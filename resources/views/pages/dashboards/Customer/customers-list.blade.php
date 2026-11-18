{{--
    Customer List
--}}

@extends('layouts.master')

@section('styles')
<!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">-->
<!--<link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">-->

<style>
    #customersTable th.action-col,
    #customersTable td:last-child {
        min-width: 10rem;
        max-width: none !important;
        width: auto !important;
        text-align: end;
        vertical-align: middle;
    }
    #customersTable .btn-group.btn-listing { position: relative; }
    #customersTable .dropdown-menu { z-index: 1060; }
</style>
@endsection

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Customer Management</h1>
        <div class="d-flex float-end">
			@tenantCan('Customers', 'Modify')
			<button type="button"
				class="btn btn-sm btn-outline-primary btn-wave waves-light me-2"
				onclick="openFbrBulkSyncModal()">
				<i class="fas fa-sync-alt me-1"></i> Sync with FBR
			</button>
			@endtenantCan

			@tenantCan('Customers', 'Add')
			<button type="button"
        class="btn btn-sm btn-primary btn-wave waves-light"
        onclick="openCustomerModal('create')">
    <i class="ri-add-line fw-medium align-middle me-1"></i> Add Customer
			</button>
			@endtenantCan

			@if(session('selected_tenant.permissions.Customers.Import') == 1 || session('selected_tenant.permissions.Customers.Print') == 1 || session('selected_tenant.permissions.Customers.Export') == 1 || session('selected_tenant.permissions.Customers.Modify') == 1)
			<div class="dropdown ms-2">
				<button class="btn btn-icon btn-secondary-light btn-sm btn-wave waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
					<i class="ti ti-dots-vertical"></i>
				</button>
				<ul class="dropdown-menu">
					@tenantCan('Customers', 'Modify')
					<li><a class="dropdown-item" href="javascript:void(0);" onclick="openFbrBulkSyncModal()"><i class="fas fa-sync-alt me-1 text-primary"></i> Sync with FBR</a></li>
					@endtenantCan
					@tenantCan('Customers', 'Import')
					<li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#customerImportModal"><i class="fas fa-file-import me-1 text-primary"></i> Import</a></li>
					@endtenantCan
					@tenantCan('Customers', 'Print')
					<li><a class="dropdown-item" href="#">Bulk Print</a></li>
					@endtenantCan
					@tenantCan('Customers', 'Export')
					<li><a class="dropdown-item" href="{{ route('customers.export-csv') }}">Export CSV</a></li>
					@endtenantCan
				</ul>
			</div>
			@endif
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

{{-- Errors --}}
@if($errors->any())
<div class="alert alert-warning alert-dismissible fade show" role="alert">
	<i class="fas fa-exclamation-triangle me-1"></i> 
	@foreach ($errors->all() as $error)
		{{ $error }}<br>
	@endforeach
	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button>
</div>
@endif
<div class="row">
	<div class="col-xs-12">
		<div class="card custom-card mb-1">
			<div class="card-body p-2">
				<div class="row">
					<div class="col-md-9">
						{{-- Filters --}}
						<form action="{{ route('customers.index') }}" method="GET" class="listing-filters-form">
							<div class="row g-2">
								<div class="col-md-4">
									<div class="header-search d-md-block d-none">
										<label>Search Customer</label>
									<input type="text"
									class="header-search-bar form-control form-control-sm bg-white"
									name="search"
									id="searchCustomers"
									value="{{ request('search') }}"
									placeholder="Search customers..."
									onkeyup="debounceSearch()">
										<a href="javascript:void(0);" class="header-search-icon border-0" style="top: 25px;">
											<i class="bi bi-search fs-12"></i>
										</a>
									</div>
								</div>
								<div class="col-md-8">
									<div class="row g-2">
										<div class="col-md-4">
											<label class="listing-filter-date-label" for="startDate">Start Date</label>
											<input type="date" class="form-control form-control-sm" id="startDate"
												name="start_date"
												value="{{ request('start_date') }}"
												onchange="debounceSearch()">
										</div>
										<div class="col-md-4">
											<label class="listing-filter-date-label" for="endDate">End Date</label>
											<input type="date" class="form-control form-control-sm" id="endDate"
												name="end_date"
												value="{{ request('end_date') }}"
												onchange="debounceSearch()">
										</div>
										<div class="col listing-filter-actions-col">
											<button class="btn btn-sm btn-primary btn-margin" type="button" onclick="window.location.href='{{ route('customers.index') }}'"><i class="fas fa-times"></i> Clear Filters</button>
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
    
    {{-- Exports --}}
	<div class="card custom-card">
<!--
		<div class="card-header justify-content-between">
			<div class="card-title">
				Customer List
			</div>
			<div class="d-flex">
				<button type="button" class="btn btn-sm btn-primary btn-wave waves-light" data-bs-toggle="modal" data-bs-target="#add_customer_modal"><i class="ri-add-line fw-medium align-middle me-1"></i> Add Customer</button>
				<div class="dropdown ms-2">
					<button class="btn btn-icon btn-secondary-light btn-sm btn-wave waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
						<i class="ti ti-dots-vertical"></i>
					</button>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="javascript:void(0);">Import</a></li>
						<li><a class="dropdown-item" href="#">Bulk Print</a></li>
						<li><a class="dropdown-item" href="{{ route('customers.export-csv') }}">Export CSV</a></li>
					</ul>
				</div>
			</div>
		</div>
-->
		<div class="card-body p-0">
			{{-- Table --}}
			<div class="table-responsive">
				<table class="customers-table table text-nowrap listing-table" id="customersTable">
					<thead>
						<tr>
							<th><input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
							<th>Code</th>
							<th>Name <i class="fas fa-sort"></i></th>
							<th class="text-center">NTN</th>
							<th>Province</th>
							<th class="text-center">Contact Number</th>
							<th class="text-center">FBR Sales Tax Status</th>
							<th class="text-center">Further Tax</th>
							<th class="text-center">ST Withheld at Source</th>
							<th class="text-center">Adv. Tax</th>
                            <th class="text-center">Status</th>
							<th class="action-col">Action</th>
						</tr>
					</thead>
					<tbody>
						@forelse($customers as $customer)
						<tr>
							<td><input type="checkbox" class="customer-checkbox form-check-input" value="{{ $customer->ID }}"></td>
							<td><span class="badge bg-light text-dark fw-semibold border">{{ $customer->AccountNo ?? '-' }}</span></td>
							<td>
								<strong>{{ $customer->BusinessName ?? ($customer->DisplayName ?? ($customer->FirstName . ' ' . $customer->LastName)) }}</strong>
								@if($customer->DisplayName)
								<br><small class="text-muted">{{ $customer->DisplayName }}</small>
								@endif
							</td>
							<td class="text-center">{{ $customer->NTN ?? 'N/A' }}</td>
							<td>{{ $customer->County ?? 'N/A' }}</td>
							<td class="text-center">{{ $customer->Mobile ?? $customer->Phone ?? 'N/A' }}</td>
							<td class="text-center">
								@php
									$isUnreg = (int) ($customer->IsUnregistered ?? 0) === 1;
									$regType = strtolower(str_replace([' ', '-', '_'], '', (string) ($customer->RegisterType ?? '')));
									$filerStatus = strtolower(str_replace([' ', '-', '_'], '', (string) ($customer->FilerStatus ?? '')));
									$isFiler = (int) ($customer->IsFiler ?? 0) === 1;

									if ($isUnreg || $regType === 'unregistered') {
										$fbrBadgeClass = 'status-inactive';
										$fbrBadgeText = 'Unregistered';
									} elseif ($filerStatus === 'active' || ($isFiler && empty($filerStatus))) {
										$fbrBadgeClass = 'status-active';
										$fbrBadgeText = 'Registered (Active)';
									} elseif ($filerStatus === 'inactive' || (!$isFiler && !empty($customer->NTN))) {
										$fbrBadgeClass = 'status-inactive';
										$fbrBadgeText = 'Registered (In-Active)';
									} else {
										$fbrBadgeClass = 'status-inactive';
										$fbrBadgeText = 'Unregistered';
									}
								@endphp
								<span class="status-badge {{ $fbrBadgeClass }}">{{ $fbrBadgeText }}</span>
							</td>
							<td class="text-center">
								<span class="fw-semibold">{{ number_format((float) ($customer->FurtherTax ?? 0), 2) }}%</span>
							</td>
							<td class="text-center">
								@if(!empty($customer->SalesTaxWithheldAtSource) && (string)$customer->SalesTaxWithheldAtSource !== '0')
									@if(is_numeric($customer->SalesTaxWithheldAtSource))
										<span class="fw-semibold">{{ number_format((float) $customer->SalesTaxWithheldAtSource, 2) }}%</span>
									@else
										<span class="fw-semibold">{{ $customer->SalesTaxWithheldAtSource }}</span>
									@endif
								@else
									<span class="text-muted">—</span>
								@endif
							</td>
							<td class="text-center">
								@if((int)($customer->IsAdvanceTax ?? 0) === 1 && (float)($customer->AdvanceTax ?? 0) > 0)
									<span class="fw-semibold">{{ number_format((float)$customer->AdvanceTax, 2) }}%</span>
								@else
									<span class="text-muted">—</span>
								@endif
							</td>
                            <td class="text-center">
                                @if(($customer->Status ?? 1) == 1)
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Inactive</span>
                                @endif
                            </td>
							<td>
								
								<div class="btn-group btn-listing">
									  @tenantCan('Customers', 'ViewOnly')
									  <button class="btn btn-outline-primary btn-list-left" onclick="openCustomerModal('show', {{ $customer->ID }})">
										<i class="fas fa-eye"></i> View
									  </button>
									  @endtenantCan

									  @if(session('selected_tenant.permissions.Customers.Modify') == 1 || session('selected_tenant.permissions.Customers.Delete') == 1)
									  <button type="button"
                                              class="btn btn-primary btn-list-right dropdown-toggle dropdown-toggle-split me-2"
                                              data-bs-toggle="dropdown"
                                              data-bs-auto-close="true"
                                              data-bs-popper-config='{"strategy":"fixed"}'
                                              aria-expanded="false">
										<span class="sr-only">Toggle Dropdown</span>
									  </button>
									  <ul class="dropdown-menu dropdown-menu-end">
										@tenantCan('Customers', 'Modify')
										<li>
										    <button type="button" class="dropdown-item" onclick="openCustomerModal('edit', {{ $customer->ID }})">
											    <i class="fas fa-edit"></i> Edit
										    </button>
										</li>
										@endtenantCan

										@tenantCan('Customers', 'Delete')
										<li>
                                            <button type="button"
                                                    class="dropdown-item text-danger"
                                                    onclick="confirmDelete({{ $customer->ID }}, '{{ addslashes($customer->BusinessName ?? ($customer->DisplayName ?? ($customer->FirstName . ' ' . $customer->LastName))) }}')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                            <form id="delete-form-{{ $customer->ID }}"
                                                  action="{{ route('customers.delete_transaction', $customer->ID ?? 0) }}"
                                                  method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </li>
										@endtenantCan

                                        <li>
                                            <button type="button" class="dropdown-item" onclick="showAuditHistory('CUSTOMER', {{ $customer->ID }})">
                                                <i class="ri-history-line me-2"></i> Audit Log
                                            </button>
                                        </li>
									  </ul>
									  @endif
									</div>
								
								
</td>
						</tr>
						@empty
						<tr>
							<td colspan="12" class="text-center py-5">
								<i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
								<p class="text-muted mb-2">No customers found.</p>
								{{-- Add button --}}
								@if(!request('search') && !request('start_date') && !request('end_date'))
								@tenantCan('Customers', 'Add')
								<button class="btn btn-primary" onclick="openCustomerModal('create')">
								<i class="fas fa-plus"></i> Add Your First Customer
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
            Showing {{ $customers->firstItem() ?? 0 }}
            to {{ $customers->lastItem() ?? 0 }}
            of {{ $customers->total() }} entries
        </span>
        <nav class="pagination-style-2">
            {{ $customers->withQueryString()->links('pagination::bootstrap-5') }}
        </nav>
    </div>
</div>
	</div>
    
    </div>
</div>

<div class="modal fade" id="customer_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="customer_modal_title">Customer</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- AJAX content loads here --}}
            <div class="modal-body" id="customer_modal_body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading...</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                {{-- Hidden by default, shown for create/edit --}}
                <button type="button" class="btn btn-sm btn-primary d-none" id="customer_modal_save_btn">
                    <i class="fas fa-save"></i>
                    <span id="customer_modal_save_label">Save Customer</span>
                </button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold text-danger">
                    <i class="fas fa-trash me-2"></i> Archive Customer
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to archive <strong id="deleteCustomerName"></strong>?</p>
                <p class="text-muted fs-13 mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    This will archive the customer and hide it from active lists.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmCustomerDeleteBtn">
                    <i class="fas fa-trash me-1"></i> Yes, Archive It
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    let deleteCustomerId = null;

    // Auto-hide only success alerts after 5 seconds; keep error alerts visible until dismissed manually
    setTimeout(function() {
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(alert => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        });
    }, 5000);
    
    function confirmDelete(id, customerName) {
        deleteCustomerId = id;
        document.getElementById('deleteCustomerName').textContent = customerName || 'this customer';
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteCustomerModal'));
        modal.show();
        return false;
    }
    document.getElementById('confirmCustomerDeleteBtn').addEventListener('click', function() {
        if (deleteCustomerId) {
            document.getElementById('delete-form-' + deleteCustomerId).submit();
        }
    });
    
    // Frontend filter
   // ── Debounced backend search (works with pagination) ──
let searchTimer = null;
function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
        const search  = document.getElementById('searchCustomers').value;
        const start   = document.getElementById('startDate').value;
        const end     = document.getElementById('endDate').value;
        const params  = new URLSearchParams();
        if (search) params.set('search', search);
        if (start)  params.set('start_date', start);
        if (end)    params.set('end_date', end);
        window.location.href = '{{ route("customers.index") }}?' + params.toString();
    }, 500); // 500ms delay after user stops typing
}
    
    // Clear
    function clearFilters() {
        document.getElementById('searchCustomers').value = '';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        // Reload page to clear backend filters
        window.location.href = '{{ route('customers.index') }}';
    }
    
    // Select all
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.customer-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    }
		// ── Universal Modal Opener ──────────────────────────────
function openCustomerModal(mode, customerId = null) {
    const modalEl    = document.getElementById('customer_modal');
    const modalBody  = document.getElementById('customer_modal_body');
    const modalTitle = document.getElementById('customer_modal_title');
    const saveBtn    = document.getElementById('customer_modal_save_btn');
    const saveLabel  = document.getElementById('customer_modal_save_label');
    const bsModal    = bootstrap.Modal.getOrCreateInstance(modalEl);

    const titles = { create: 'Add Customer', edit: 'Edit Customer', show: 'Customer Details' };
    modalTitle.textContent = titles[mode] || 'Customer';

    if (mode === 'show') {
        saveBtn.classList.add('d-none');
    } else {
        saveBtn.classList.remove('d-none');
        saveLabel.textContent = mode === 'edit' ? 'Update Customer' : 'Save Customer';
    }

    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading...</p>
        </div>`;

    bsModal.show();

    const timestamp = Date.now();
    let url = mode === 'create'
        ? '{{ route("customers.modal") }}?mode=create&_t=' + timestamp
        : `/customers/${customerId}/modal?mode=${mode}&_t=` + timestamp;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Cache-Control': 'no-cache'
        }
    })
    .then(res => { if (!res.ok) throw new Error('Server error: ' + res.status); return res.text(); })
    .then(html => {
        modalBody.innerHTML = html;

        // ── EXECUTE SCRIPTS EMBEDDED IN AJAX CONTENT ──
        // Browsers do not execute <script> tags inserted via innerHTML.
        // We must manually extract and run them.
        const scripts = modalBody.getElementsByTagName("script");
        const scriptArray = Array.from(scripts); // Convert HTMLCollection to Array to avoid live collection issues during removal/re-insertion

        scriptArray.forEach(oldScript => {
            const newScript = document.createElement("script");
            if (oldScript.src) {
                newScript.src = oldScript.src;
                newScript.async = false; // Ensure sequential execution if needed
            } else {
                newScript.textContent = oldScript.textContent;
            }
            // Append to body (or head) to force execution
            document.body.appendChild(newScript);
            // Optional: Remove the script tag from the body after execution to keep DOM clean
            // document.body.removeChild(newScript); 
        });

        // ── Wire Save Button → AJAX submit ──
        saveBtn.onclick = function () {
            const form = modalBody.querySelector('form');
            if (!form) return;

            // Show loading state on button
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Purge draft keys from localStorage
                    if (typeof window.clearCustomerModalDrafts === 'function') {
                        window.clearCustomerModalDrafts();
                    }
                    Object.keys(localStorage).forEach(k => {
                        if (k.startsWith('cfp_') || k.startsWith('customer_')) localStorage.removeItem(k);
                    });
                    bsModal.hide();
                    // Show success toast on list page
                    showToast('success', data.message);
                    // Reload table after short delay
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    // Show validation errors inside modal
                    showModalErrors(data.errors || {});
                    if (data.message) showToast('error', data.message);
                }
            })
            .catch(err => {
                showToast('error', 'Submission failed. Please try again.');
            })
            .finally(() => {
                // Restore button
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> <span id="customer_modal_save_label">' + (mode === 'edit' ? 'Update Customer' : 'Save Customer') + '</span>';
            });
        };
    })
    .catch(err => {
        modalBody.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                Failed to load. Please try again.<br>
                <small class="text-muted">${err.message}</small>
            </div>`;
    });
}

// ── Show errors inside modal ──────────────────────────
function showModalErrors(errors) {
    const modalBody = document.getElementById('customer_modal_body');

    // Clear previous errors
    modalBody.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    modalBody.querySelectorAll('.ajax-error-feedback').forEach(el => el.remove());

    let firstField = null;

    Object.entries(errors).forEach(([field, messages]) => {
        const input = modalBody.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback ajax-error-feedback';
            feedback.style.display = 'block';
            feedback.textContent = messages[0];
            input.insertAdjacentElement('afterend', feedback);
            if (!firstField) firstField = input;
        }
    });

    if (firstField) {
        firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstField.focus();
    }
}

// ── Toast notification ────────────────────────────────
function showToast(type, message) {
    const existing = document.getElementById('cfp_toast');
    if (existing) existing.remove();

    const bg = type === 'success' ? 'bg-success' : 'bg-danger';
    const toast = document.createElement('div');
    toast.id = 'cfp_toast';
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:280px;';
    toast.innerHTML = `
        <div class="alert ${bg} text-white shadow d-flex align-items-center gap-2 mb-0">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button type="button" class="btn-close btn-close-white ms-auto" onclick="this.closest('#cfp_toast').remove()"></button>
        </div>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

function handleCustomerImportSubmit(form) {
    const btn = document.getElementById('btnSubmitCustomerImport');
    const txt = document.getElementById('btnSubmitImportText');
    if (btn) {
        btn.disabled = true;
        if (txt) txt.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importing...';
    }
}
</script>

{{-- Customer Import Modal --}}
<div class="modal fade" id="customerImportModal" tabindex="-1" aria-labelledby="customerImportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data" id="customerImportForm" onsubmit="handleCustomerImportSubmit(this)">
                @csrf
                <div class="modal-header border-bottom py-2 px-3 bg-light">
                    <h6 class="modal-title fw-semibold text-primary mb-0" id="customerImportModalLabel" style="font-size: 15px;">
                        <i class="fas fa-file-import me-2"></i> Import Customers
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    {{-- Download Sample Template Strip --}}
                    <div class="d-flex align-items-center justify-content-between p-2 mb-3 bg-light border rounded">
                        <div>
                            <div class="fw-semibold text-dark fs-13"><i class="fas fa-file-excel text-success me-1"></i> Sample Template</div>
                            <small class="text-muted">Download pre-formatted Excel/CSV template</small>
                        </div>
                        <a href="{{ route('customers.import-template') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i> Download Template
                        </a>
                    </div>

                    {{-- File Input --}}
                    <div class="mb-0">
                        <label for="import_file" class="form-label fw-semibold fs-13 text-dark">
                            Select File <span class="text-danger">*</span>
                        </label>
                        <input type="file" 
                               name="import_file" 
                               id="import_file" 
                               class="form-control form-control-sm" 
                               accept=".xlsx, .xls, .csv, text/csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" 
                               required>
                        <small class="text-muted d-block mt-1">Supported formats: <strong>.xlsx, .xls, .csv</strong> (Max: 10MB)</small>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 border-top bg-light">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitCustomerImport">
                        <i class="fas fa-upload me-1"></i> <span id="btnSubmitImportText">Upload & Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- FBR Bulk Sync Modal --}}
<div class="modal fade" id="fbrBulkSyncModal" tabindex="-1" aria-labelledby="fbrBulkSyncModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-2 px-3 bg-light">
                <h6 class="modal-title fw-semibold text-primary mb-0" id="fbrBulkSyncModalLabel" style="font-size: 15px;">
                    <i class="fas fa-sync-alt me-2"></i> Sync Customer Status with FBR (ATL)
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="fbrSyncModalCloseX"></button>
            </div>
            <div class="modal-body p-3">
                {{-- Config Section (Before Start) --}}
                <div id="fbrSyncConfigSection">
                    <div class="alert alert-info py-2 px-3 fs-12 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Verifies active customers having an <strong>NTN</strong> against the live FBR Active Taxpayer List (ATL).
                        Customers marked as <strong>Unregistered Buyer</strong> or having an empty NTN are automatically excluded.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">Verification "As of" Date</label>
                            <input type="date" class="form-control form-control-sm" id="fbrSyncAsOfDate" value="{{ now()->toDateString() }}">
                            <div class="form-text fs-11 text-muted">Date against which FBR ATL tax status is verified.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">Unregistered Buyer Further Tax Rate (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" id="fbrSyncFurtherTax" value="4.00" min="0" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text fs-11 text-muted">Applied to Unregistered buyers. Set to 0.00% automatically for Registered filers.</div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-2 bg-light">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="fbrSyncApplyAdvTax" onchange="toggleFbrSyncAdvTax(this.checked)">
                                    <label class="form-check-label fs-12 fw-semibold text-dark" for="fbrSyncApplyAdvTax">
                                        Configure Advance Tax (Section 236H / Unregistered Buyer)
                                    </label>
                                </div>
                                <div class="row g-2 mt-1 d-none" id="fbrSyncAdvTaxRateWrap">
                                    <div class="col-md-6">
                                        <label class="form-label fs-11 text-muted">Unregistered Advance Tax Rate %</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="fbrSyncAdvTaxRate" value="0.50" min="0" max="100" step="0.01">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-12 fs-11 text-muted">
                                        When enabled, Unregistered buyers receive this advance tax rate, while Registered filers are reset to 0.00%.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Progress & Live Log Section (During / After Sync) --}}
                <div id="fbrSyncProgressSection" class="d-none mt-2">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fs-12 fw-semibold text-dark" id="fbrSyncStatusText">Connecting to FBR...</span>
                        <span class="fs-12 fw-bold text-primary" id="fbrSyncPercent">0%</span>
                    </div>
                    <div class="progress progress-sm mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="fbrSyncProgressBar" role="progressbar" style="width: 0%"></div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge bg-light text-dark border p-2">Total Eligible: <strong id="statTotalCount">0</strong></span>
                        <span class="status-badge status-active">Registered: <strong id="statActiveCount">0</strong></span>
                        <span class="status-badge status-inactive">Unregistered: <strong id="statInactiveCount">0</strong></span>
                        <span class="badge bg-light text-danger border p-2">Errors / Skipped: <strong id="statErrorCount">0</strong></span>
                    </div>

                    <div class="table-responsive border rounded" style="max-height: 230px; overflow-y: auto;" id="fbrSyncTableContainer">
                        <table class="customers-table table text-nowrap listing-table mb-0" id="fbrSyncResultTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Customer Name</th>
                                    <th>NTN</th>
                                    <th>FBR Sales Tax Status</th>
                                    <th>Further Tax</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody id="fbrSyncResultBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Alert box for status messages --}}
                <div class="alert d-none mt-3 mb-0 fs-12" id="fbrSyncAlertBox" role="alert"></div>
            </div>
            <div class="modal-footer py-2 px-3 border-top bg-light">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" id="fbrSyncBtnClose">Close</button>
                <button type="button" class="btn btn-sm btn-primary" id="fbrSyncBtnStart" onclick="startFbrBulkSync()">
                    <i class="fas fa-play me-1"></i> <span id="fbrSyncBtnStartText">Start Bulk Sync</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.fbrSyncHadUpdates = false;
window.fbrSyncRunning = false;
window.fbrSyncStopRequested = false;

function openFbrBulkSyncModal() {
    const modalEl = document.getElementById('fbrBulkSyncModal');
    if (!modalEl) return;

    // Reset view states
    document.getElementById('fbrSyncConfigSection')?.classList.remove('d-none');
    document.getElementById('fbrSyncProgressSection')?.classList.add('d-none');
    const alertBox = document.getElementById('fbrSyncAlertBox');
    if (alertBox) {
        alertBox.className = 'alert d-none mt-3 mb-0 fs-12';
        alertBox.textContent = '';
    }

    const btnStart = document.getElementById('fbrSyncBtnStart');
    if (btnStart) {
        btnStart.disabled = false;
        btnStart.classList.remove('d-none');
    }
    const btnStartText = document.getElementById('fbrSyncBtnStartText');
    if (btnStartText) btnStartText.textContent = 'Start Bulk Sync';

    const btnClose = document.getElementById('fbrSyncBtnClose');
    if (btnClose) btnClose.textContent = 'Close';

    window.fbrSyncRunning = false;
    window.fbrSyncStopRequested = false;
    window.fbrSyncHadUpdates = false;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function toggleFbrSyncAdvTax(isChecked) {
    const wrap = document.getElementById('fbrSyncAdvTaxRateWrap');
    if (wrap) {
        wrap.classList.toggle('d-none', !isChecked);
    }
}

document.getElementById('fbrBulkSyncModal')?.addEventListener('hidden.bs.modal', function () {
    if (window.fbrSyncRunning) {
        window.fbrSyncStopRequested = true;
    }
    if (window.fbrSyncHadUpdates) {
        window.location.reload();
    }
});

async function startFbrBulkSync() {
    const btnStart = document.getElementById('fbrSyncBtnStart');
    const btnStartText = document.getElementById('fbrSyncBtnStartText');
    const btnClose = document.getElementById('fbrSyncBtnClose');
    const alertBox = document.getElementById('fbrSyncAlertBox');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const asOfDate = document.getElementById('fbrSyncAsOfDate')?.value || '';
    const furtherTaxRate = parseFloat(document.getElementById('fbrSyncFurtherTax')?.value || '4.00');
    const applyAdvTax = !!document.getElementById('fbrSyncApplyAdvTax')?.checked;
    const advTaxRate = parseFloat(document.getElementById('fbrSyncAdvTaxRate')?.value || '0.50');

    if (isNaN(furtherTaxRate) || furtherTaxRate < 0) {
        alert('Please specify a valid non-negative Further Tax percentage.');
        return;
    }

    // Enter starting state
    if (btnStart) btnStart.disabled = true;
    if (btnStartText) btnStartText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Preparing...';
    if (alertBox) alertBox.classList.add('d-none');

    try {
        // Step 1: Initialize list of qualifying customer IDs
        const initRes = await fetch(@json(route('customers.fbr_bulk_sync.init')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        });

        const initData = await initRes.json().catch(() => ({}));

        if (!initRes.ok || initData.status !== 'success') {
            throw new Error(initData.message || 'Failed to initialize FBR customer sync.');
        }

        const ids = initData.ids || [];
        const total = ids.length;

        if (total === 0) {
            if (alertBox) {
                alertBox.className = 'alert alert-warning mt-3 mb-0 fs-12';
                alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> No eligible customers found with an NTN to verify. Unregistered buyers and customers without an NTN are skipped.';
                alertBox.classList.remove('d-none');
            }
            if (btnStart) btnStart.disabled = false;
            if (btnStartText) btnStartText.textContent = 'Start Bulk Sync';
            return;
        }

        // Step 2: Switch to Progress View
        document.getElementById('fbrSyncConfigSection')?.classList.add('d-none');
        document.getElementById('fbrSyncProgressSection')?.classList.remove('d-none');
        if (btnStart) btnStart.classList.add('d-none');
        if (btnClose) {
            btnClose.textContent = 'Stop Sync';
            btnClose.onclick = function() {
                if (window.fbrSyncRunning) {
                    window.fbrSyncStopRequested = true;
                    btnClose.textContent = 'Stopping...';
                    btnClose.disabled = true;
                }
            };
        }

        window.fbrSyncRunning = true;
        window.fbrSyncStopRequested = false;

        // Reset progress counters
        document.getElementById('statTotalCount').textContent = total;
        document.getElementById('statActiveCount').textContent = '0';
        document.getElementById('statInactiveCount').textContent = '0';
        document.getElementById('statErrorCount').textContent = '0';
        document.getElementById('fbrSyncProgressBar').style.width = '0%';
        document.getElementById('fbrSyncPercent').textContent = '0%';
        document.getElementById('fbrSyncResultBody').innerHTML = '';

        let processedCount = 0;
        let activeCount = 0;
        let inactiveCount = 0;
        let errorCount = 0;

        // Process in chunk sizes of 5 to avoid timeouts
        const chunkSize = 5;
        const chunks = [];
        for (let i = 0; i < ids.length; i += chunkSize) {
            chunks.push(ids.slice(i, i + chunkSize));
        }

        for (let c = 0; c < chunks.length; c++) {
            if (window.fbrSyncStopRequested) {
                break;
            }

            const currentBatch = chunks[c];
            document.getElementById('fbrSyncStatusText').textContent = `Syncing batch ${c + 1} of ${chunks.length}...`;

            try {
                const batchRes = await fetch(@json(route('customers.fbr_bulk_sync.batch')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        ids: currentBatch,
                        further_tax_rate: furtherTaxRate,
                        apply_advance_tax: applyAdvTax,
                        advance_tax_rate: advTaxRate,
                        as_of_date: asOfDate
                    })
                });

                const batchData = await batchRes.json().catch(() => ({}));

                if (batchRes.ok && batchData.status === 'success' && Array.isArray(batchData.processed)) {
                    window.fbrSyncHadUpdates = true;
                    const tbody = document.getElementById('fbrSyncResultBody');
                    const scrollContainer = document.getElementById('fbrSyncTableContainer');

                    batchData.processed.forEach(item => {
                        processedCount++;
                        if (item.status === 'Registered' || item.status === 'Active' || item.is_filer === 1) {
                            activeCount++;
                        } else if (item.status === 'Error') {
                            errorCount++;
                        } else {
                            inactiveCount++;
                        }

                        let badgeHtml = '<span class="status-badge status-inactive">Unregistered</span>';
                        if (item.status === 'Registered (Active)' || item.status === 'Registered' || item.status === 'Active' || item.is_filer === 1) {
                            badgeHtml = '<span class="status-badge status-active">Registered (Active)</span>';
                        } else if (item.status === 'Registered (In-Active)') {
                            badgeHtml = '<span class="status-badge status-inactive">Registered (In-Active)</span>';
                        } else if (item.status === 'Error') {
                            badgeHtml = '<span class="status-badge status-inactive">Error</span>';
                        }

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${escapeHtml(item.account_no || '-')}</td>
                            <td><strong>${escapeHtml(item.name || '-')}</strong></td>
                            <td>${escapeHtml(item.ntn || '-')}</td>
                            <td>${badgeHtml}</td>
                            <td>${Number(item.further_tax || 0).toFixed(2)}%</td>
                            <td class="text-muted">${escapeHtml(item.message || '-')}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Auto-scroll table to bottom
                    if (scrollContainer) {
                        scrollContainer.scrollTop = scrollContainer.scrollHeight;
                    }
                } else {
                    errorCount += currentBatch.length;
                    processedCount += currentBatch.length;
                }
            } catch (err) {
                errorCount += currentBatch.length;
                processedCount += currentBatch.length;
            }

            // Update UI Counters
            document.getElementById('statActiveCount').textContent = activeCount;
            document.getElementById('statInactiveCount').textContent = inactiveCount;
            document.getElementById('statErrorCount').textContent = errorCount;

            const pct = Math.min(100, Math.round((processedCount / total) * 100));
            document.getElementById('fbrSyncProgressBar').style.width = pct + '%';
            document.getElementById('fbrSyncPercent').textContent = pct + '%';
        }

        // Completion
        window.fbrSyncRunning = false;
        if (btnClose) {
            btnClose.disabled = false;
            btnClose.onclick = null;
            btnClose.textContent = window.fbrSyncHadUpdates ? 'Done & Refresh' : 'Close';
        }

        const progressBar = document.getElementById('fbrSyncProgressBar');
        if (window.fbrSyncStopRequested) {
            document.getElementById('fbrSyncStatusText').textContent = 'Sync stopped by user.';
            if (alertBox) {
                alertBox.className = 'alert alert-warning mt-3 mb-0 fs-12';
                alertBox.innerHTML = `<i class="fas fa-info-circle me-1"></i> Sync was stopped. Processed ${processedCount} of ${total} customers.`;
                alertBox.classList.remove('d-none');
            }
        } else {
            if (progressBar) {
                progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                progressBar.classList.add('bg-success');
            }
            document.getElementById('fbrSyncStatusText').innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Bulk Sync Completed!';
            if (alertBox) {
                alertBox.className = 'alert alert-success mt-3 mb-0 fs-12';
                alertBox.innerHTML = `<strong><i class="fas fa-check-circle me-1"></i> Complete!</strong> Verified ${total} customers with FBR. Found ${activeCount} Registered and updated ${inactiveCount} Unregistered buyers with Further Tax (${furtherTaxRate}%).`;
                alertBox.classList.remove('d-none');
            }
        }

    } catch (e) {
        window.fbrSyncRunning = false;
        if (alertBox) {
            alertBox.className = 'alert alert-danger mt-3 mb-0 fs-12';
            alertBox.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i> Error: ${escapeHtml(e.message || 'An error occurred during sync.')}`;
            alertBox.classList.remove('d-none');
        }
        if (btnStart) {
            btnStart.disabled = false;
            btnStart.classList.remove('d-none');
        }
        if (btnStartText) btnStartText.textContent = 'Start Bulk Sync';
        if (btnClose) {
            btnClose.disabled = false;
            btnClose.onclick = null;
            btnClose.textContent = 'Close';
        }
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
@endsection
