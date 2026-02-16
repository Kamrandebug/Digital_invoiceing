{{--
    Customer List
--}}

@extends('layouts.master')

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">
<style>
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
</style>
@endsection

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Customer Management</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
            <li class="breadcrumb-item"><a href="javascript:void(0);">Ecommerce</a></li>
            <li class="breadcrumb-item active" aria-current="page">Customers List</li>
        </ol>
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

<div class="customer-management-container">
    
    {{-- Header --}}
    <div class="header">
        <h1>Customer Management</h1>
        <div class="action-buttons">
            <button onclick="window.location.href='{{ route('customers.create') }}'">
                <i class="fas fa-plus"></i> Add Customer
            </button>
            <button><i class="fas fa-file-upload"></i> Import</button>
        </div>
    </div>

    {{-- Filters --}}
    <form action="{{ route('customers.index') }}" method="GET" class="search-filters-section">
        <div class="search-box">
            <div style="position: relative; width: 100%;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa;"></i>
                <input type="text" name="search" id="searchCustomers" value="{{ request('search') }}" placeholder="Search customers..." onkeyup="filterTable()">
            </div>
            
            <button type="submit" class="btn-search">
                <i class="fas fa-search"></i> Search
            </button>
        </div>

        <div class="date-filters">
            <div>
                <label>Start Date</label>
                <input type="date" name="start_date" id="startDate" value="{{ request('start_date') }}">
            </div>
            <div>
                <label>End Date</label>
                <input type="date" name="end_date" id="endDate" value="{{ request('end_date') }}">
            </div>
        </div>

        <button type="button" onclick="window.location.href='{{ route('customers.index') }}'"><i class="fas fa-times"></i> Clear Filters</button>
    </form>

    {{-- Exports --}}
    <div class="export-buttons">
        <button onclick="window.location.href='{{ route('customers.export-csv') }}'" class="btn-export-csv">
            <i class="fas fa-download"></i> Export CSV
        </button>
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    {{-- Table --}}
    <div class="customers-table-wrapper">
        <table class="customers-table" id="customersTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
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
                    <td><input type="checkbox" class="customer-checkbox" value="{{ $customer->ID }}"></td>
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
