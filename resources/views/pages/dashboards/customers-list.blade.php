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
			<button type="button" class="btn btn-sm btn-primary btn-wave waves-light" data-bs-toggle="modal" data-bs-target="#add_customer_modal"><i class="ri-add-line fw-medium align-middle me-1"></i> Add Customer</button>
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
										<input type="text" class="header-search-bar form-control form-control-sm bg-white" name="search" id="searchCustomers" value="{{ request('search') }}" placeholder="Search customers..." onkeyup="filterTable()">
										<a href="javascript:void(0);" class="header-search-icon border-0" style="top: 25px;">
											<i class="bi bi-search fs-12"></i>
										</a>
									</div>
								</div>
								<div class="col-md-8">
									<div class="row g-2">
										<div class="col-md-4">
											<label>Start Date</label>
											<div><input type="date" class="form-control form-control-sm" name="start_date" id="startDate" value="{{ request('start_date') }}"></div>
										</div>
										<div class="col-md-4">
											<label>End Date</label>
											<input type="date" class="form-control form-control-sm" name="end_date" id="endDate" value="{{ request('end_date') }}">
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
							<th>Closing Balance <i class="fas fa-info-circle" title="Current balance"></i></th>
							<th>Opening Balance Date <i class="fas fa-info-circle" title="Date of opening balance"></i></th>
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
							<td class="{{ $customer->PartyBalance > 0 ? 'balance-positive' : ($customer->PartyBalance < 0 ? 'balance-negative' : 'balance-zero') }}">
								{{ number_format(abs($customer->PartyBalance), 2) }} PKR
							</td>
							<td>{{ $customer->CreatedOn ? \Carbon\Carbon::parse($customer->CreatedOn)->format('d/m/Y') : 'N/A' }}</td>
							<td>

								<button class="action-btn btn-info"
										onclick="window.location.href='{{ route('customers.show', $customer->ID ?? 0) }}'"
										title="View Details">
									<i class="fas fa-clipboard-list"></i>
								</button>

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
							<td colspan="8" class="text-center py-5">
								<i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
								<p class="text-muted mb-2">No customers found.</p>
								{{-- Add button --}}
								@if(!request('search') && !request('start_date') && !request('end_date'))
								<button class="btn btn-primary" onclick="window.location.href='{{ route('customers.create') }}'">
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
			<div class="ms-auto"> 
				<nav aria-label="Page navigation" class="pagination-style-2">
					<ul class="pagination mb-0 flex-wrap justify-content-end">
						<li class="page-item disabled">
							<a class="page-link" href="javascript:void(0);">
								Prev
							</a>
						</li>
						<li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
						<li class="page-item active"><a class="page-link" href="javascript:void(0);">2</a></li>
						<li class="page-item">
							<a class="page-link" href="javascript:void(0);">
								<i class="bi bi-three-dots"></i>
							</a>
						</li>
						<li class="page-item"><a class="page-link" href="javascript:void(0);">17</a></li>
						<li class="page-item">
							<a class="page-link text-primary" href="javascript:void(0);">
								next
							</a>
						</li>
					</ul>
				</nav>
			</div> 
		</div>
	</div>
    
    </div>

    {{-- Pagination --}}
    <div class="pagination">
        <span>Showing {{ count($customers) > 0 ? '1 to ' . count($customers) : '0' }} of {{ count($customers) }} entries</span>
        <div>
            <button disabled>Previous</button>
            <button class="active">1</button>
            <button disabled>Next</button>
        </div>
    </div>

</div>

<div class="modal fade" id="add_customer_modal" tabindex="-1" aria-labelledby="add_customer_modalLabel" aria-hidden="true" style="display: none;">
	<div class="modal-dialog modal-fullscreen">
		<div class="modal-content">
			<div class="modal-header">
				<h6 class="modal-title" id="add_customer_modalLabel">Add Customer</h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal"
					aria-label="Close"></button>
			</div>
			<div class="modal-body">
				add code here
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" data-bs-dismiss="modal">Save</button>
				<button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Close</button>
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
    function filterTable() {
        const searchValue = document.getElementById('searchCustomers').value.toLowerCase();
        const table = document.getElementById('customersTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            
            if (cells.length === 0) continue;
            
            // Get text content
            const name = cells[2]?.textContent.toLowerCase() || '';
            const address = cells[3]?.textContent.toLowerCase() || '';
            const contact = cells[4]?.textContent.toLowerCase() || '';
            
            // Check search match
            const searchMatch = name.includes(searchValue) || 
                              address.includes(searchValue) || 
                              contact.includes(searchValue);
            
            // Show/hide
            row.style.display = searchMatch ? '' : 'none';
        }
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
</script>
@endsection
