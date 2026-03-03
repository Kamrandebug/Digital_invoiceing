{{--
    Customer List
--}}

@extends('layouts.master')

@section('styles')
<!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">-->
<!--<link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">-->
<!--<style>
    .search-filters-section {
        display: flex;
        align-items: flex-end; /* Bottom align everything so inputs match */
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px; /* Add some space below */
    }

    .search-box {
        position: relative;
        flex: 1;
        min-width: 300px;
        display: flex;
        align-items: center; /* Vertical alignment for input and button */
        gap: 10px; /* RESTORE GAP */
    }
    
    .search-box input {
        width: 100%;
        padding: 0 15px 0 35px; 
        border: 1px solid #ddd;
        /* border-right: none; REMOVE THIS */
        border-radius: 5px; /* RESTORE RADIUS */
        font-size: 14px;
        height: 40px; 
    }

    .search-filters-section .btn-search {
        border-radius: 5px; /* RESTORE RADIUS */
        margin-left: 0 !important; 
    }
    
    .date-filters {
        display: flex;
        gap: 10px;
        align-items: flex-end; /* Align inputs and labels properly */
    }
    
    .date-filters > div {
        display: flex;
        flex-direction: column; /* Stack label above input */
        gap: 5px;
    }

    .date-filters label {
        margin: 0;
        white-space: nowrap;
        font-weight: 500;
        color: #555;
        font-size: 12px; /* Smaller font for labels */
    }
    
    .date-filters input {
        height: 40px; 
        padding: 5px 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    /* Ensure all buttons have the same height and alignment */
    .export-buttons button, 
    .search-filters-section button, 
    .search-filters-section .btn-search {
        padding: 0 20px; /* Horizontal padding */
        height: 40px;    /* Fixed height matching inputs */
        border: none;
        border-radius: 5px;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 14px;
        line-height: 1; /* Reset line height for centering */
    }
    
    /* Clear filters button specific style */
    .search-filters-section button[type="button"]:not(.btn-search) {
        background-color: #dc3545; /* Red */
    }

    .btn-search {
        background-color: #0d6efd; /* Blue like Print */
    }
    
    .btn-export-csv {
        background-color: #6f42c1; /* Purple */
    }
    
    .btn-print {
        background-color: #0d6efd; /* Blue */
    }

    .export-buttons {
        display: flex;
        gap: 10px;
        align-items: center;
        height: 40px; /* Match height of filter bar */
        margin-bottom: 20px; /* Space below export buttons */
    }
</style>-->
<style>
	.btn-margin{
		margin-top: 20px;
	}
</style>
@endsection

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Customer Management</h1>
        <div class="d-flex float-end">
			<button type="button"
        class="btn btn-sm btn-primary btn-wave waves-light"
        onclick="openCustomerModal('create')">
    <i class="ri-add-line fw-medium align-middle me-1"></i> Add Customer
</button>
<!--				<button type="button" class="btn btn-sm btn-primary btn-wave waves-light" onclick="window.location.href='{{ route('customers.create') }}'"><i class="ri-add-line fw-medium align-middle me-1"></i> Add Customer</button>-->
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
</div>
{{-- Messages --}}
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

{{-- Errors --}}
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
						{{-- Filters --}}
						<form action="{{ route('customers.index') }}" method="GET" class="">
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
											<label>Start Date</label>
											<div><input type="date" class="form-control form-control-sm"
												name="start_date" id="startDate"
												value="{{ request('start_date') }}"
												onchange="debounceSearch()"></div>
										</div>
										<div class="col-md-4">
											<label>End Date</label>
											<input type="date" class="form-control form-control-sm"
												name="end_date" id="endDate"
												value="{{ request('end_date') }}"
												onchange="debounceSearch()">
										</div>
										<div class="col">
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
				<table class="customers-table table text-nowrap" id="customersTable">
					<thead>
						<tr>
							<th><input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
							<th>ID <i class="fas fa-sort"></i></th>
							<th>Name <i class="fas fa-sort"></i></th>
							<th>Address</th>
							<th>Contact Number</th>
                            <th>Status</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						@forelse($customers as $customer)
						<tr>
							<td><input type="checkbox" class="customer-checkbox form-check-input" value="{{ $customer->ID }}"></td>
							<td>{{ $customer->ID }}</td>
							<td>
								<strong>{{ $customer->DisplayName ?? ($customer->FirstName . ' ' . $customer->LastName) }}</strong>
								@if($customer->BusinessName)
								<br><small class="text-muted">{{ $customer->BusinessName }}</small>
								@endif
							</td>
							<td>{{ $customer->Address ?? 'N/A' }}</td>
							<td>{{ $customer->Mobile ?? $customer->Phone ?? 'N/A' }}</td>
                            <td>
                                @if(($customer->Status ?? 1) == 1)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
							<td>
    {{-- VIEW --}}
    <button class="action-btn btn-info"
            onclick="openCustomerModal('show', {{ $customer->ID }})"
            title="View Details">
        <i class="fas fa-clipboard-list"></i>
    </button>

    {{-- EDIT --}}
    <button class="action-btn btn-warning"
            onclick="openCustomerModal('edit', {{ $customer->ID }})"
            title="Edit Customer">
        <i class="fas fa-edit"></i>
    </button>

    {{-- DELETE --}}
    <form action="{{ route('customers.delete_transaction', $customer->ID ?? 0) }}"
          method="POST"
          style="display: inline;"
          onsubmit="return confirmDelete('{{ $customer->DisplayName ?? ($customer->FirstName . ' ' . $customer->LastName) }}')">
        @csrf
        @method('DELETE')
        <button type="submit" class="action-btn btn-delete" title="Archive Customer">
            <i class="fas fa-trash"></i>
        </button>
    </form>
</td>
						</tr>
						@empty
						<tr>
							<td colspan="7" class="text-center py-5">
								<i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
								<p class="text-muted mb-2">No customers found.</p>
								{{-- Add button --}}
								@if(!request('search') && !request('start_date') && !request('end_date'))
								<button class="btn btn-primary" onclick="openCustomerModal('create')">
								<i class="fas fa-plus"></i> Add Your First Customer
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

@endsection

@section('scripts')
<script>
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        });
    }, 5000);
    
    // Confirm delete
    function confirmDelete(customerName) {
        return confirm(`Are you sure you want to archive the customer "${customerName}"?\n\nThis will set IsArchive = 1 and hide the customer from the active list.`);
    }
    
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

    let url = mode === 'create'
        ? '{{ route("customers.modal") }}?mode=create'
        : `/customers/${customerId}/modal?mode=${mode}`;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
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
</script>
@endsection
