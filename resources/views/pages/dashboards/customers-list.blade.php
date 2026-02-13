{{--
    Customer Management Page
    
    Path: resources/views/pages/dashboards/customers-list.blade.php
    URL: /customers-list
    
    Updated to use REAL DATABASE DATA with transaction-based CRUD functions:
    - save_transaction (Add Customer)
    - edit_transaction (Edit Customer)
    - delete_transaction (Delete Customer)
--}}

@extends('layouts.master')

@section('styles')
{{-- Font Awesome for icons --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
{{-- Custom CSS for customer management --}}
<link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">
@endsection

@section('content')

<!-- Start::page-header -->
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
<!-- End::page-header -->

<!-- Success/Error Messages -->
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

<!-- Customer Management Container -->
<div class="customer-management-container">
    
    {{-- Header with Action Buttons --}}
    <div class="header">
        <h1>Customer Management</h1>
        <div class="action-buttons">
            <button onclick="window.location.href='{{ route('customers.create') }}'">
                <i class="fas fa-plus"></i> Add Customer
            </button>
            <button><i class="fas fa-file-upload"></i> Import</button>
        </div>
    </div>

    {{-- Search & Filters Section --}}
    <div class="search-filters-section">
        <div class="search-box">
            <input type="text" id="searchCustomers" placeholder="Search customers..." onkeyup="filterTable()">
        </div>

        <div class="date-filters">
            <div>
                <label>Start Date</label>
                <input type="date" id="startDate">
            </div>
            <div>
                <label>End Date</label>
                <input type="date" id="endDate">
            </div>
        </div>

        <button onclick="clearFilters()"><i class="fas fa-times"></i> Clear Filters</button>

        <div class="search-right">
            <label>Search:</label>
            <input type="text" id="searchRight" onkeyup="filterTable()">
        </div>
    </div>

    {{-- Export Buttons --}}
    <div class="export-buttons">
        <button onclick="window.location.href='{{ route('customers.export-csv') }}'">
            <i class="fas fa-download"></i> Export CSV
        </button>
        <button onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    {{-- Customers Table with REAL DATABASE DATA --}}
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
                {{-- Loop through REAL customers from database --}}
                @forelse($customers as $customer)
                <tr>
                    <td><input type="checkbox" class="customer-checkbox" value="{{ $customer->ID }}"></td>
                    <td>{{ $customer->ID }}</td>
                    <td>
                        <strong>{{ $customer->DisplayName ?? $customer->name }}</strong>
                        @if($customer->BusinessName)
                        <br><small class="text-muted">{{ $customer->BusinessName }}</small>
                        @endif
                    </td>
                    <td>{{ $customer->Address ?? 'N/A' }}</td>
                    <td>{{ $customer->Mobile ?? $customer->Phone ?? 'N/A' }}</td>
                    <td class="{{ $customer->PartyBalance > 0 ? 'balance-positive' : ($customer->PartyBalance < 0 ? 'balance-negative' : 'balance-zero') }}">
                        {{ number_format(abs($customer->PartyBalance), 2) }} PKR
                    </td>
                    <td>{{ $customer->CreatedOn ? $customer->CreatedOn->format('d/m/Y') : 'N/A' }}</td>
                    <td>
                        {{-- Edit Button (uses edit_transaction) --}}
                        <button class="action-btn btn-edit" 
                                onclick="window.location.href='{{ route('customers.edit', $customer->ID) }}'"
                                title="Edit Customer">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        
                        {{-- View/Info Button --}}
                        <button class="action-btn btn-info"
                                onclick="window.location.href='{{ route('customers.show', $customer->ID) }}'"
                                title="View Details">
                            <i class="fas fa-clipboard-list"></i>
                        </button>
                        
                        {{-- Delete Button (uses delete_transaction) --}}
                        <form action="{{ route('customers.delete_transaction', $customer->ID) }}" 
                              method="POST" 
                              style="display: inline;"
                              onsubmit="return confirmDelete('{{ $customer->DisplayName ?? $customer->name }}')">
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
                        <button class="btn btn-primary" onclick="window.location.href='{{ route('customers.create') }}'">
                            <i class="fas fa-plus"></i> Add Your First Customer
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pagination">
        <span>Showing {{ $customers->count() > 0 ? '1 to ' . $customers->count() : '0' }} of {{ $customers->count() }} entries</span>
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
    // Auto-hide success/error messages after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        });
    }, 5000);
    
    // Delete confirmation with customer name
    function confirmDelete(customerName) {
        return confirm(`Are you sure you want to archive the customer "${customerName}"?\n\nThis will set IsArchive = 1 and hide the customer from the active list.`);
    }
    
    // Filter table function
    function filterTable() {
        const searchValue = document.getElementById('searchCustomers').value.toLowerCase();
        const searchRight = document.getElementById('searchRight').value.toLowerCase();
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
                              name.includes(searchRight) ||
                              address.includes(searchValue) || 
                              address.includes(searchRight) ||
                              contact.includes(searchValue) ||
                              contact.includes(searchRight);
            
            // Show/hide row
            row.style.display = searchMatch ? '' : 'none';
        }
    }
    
    // Clear filters
    function clearFilters() {
        document.getElementById('searchCustomers').value = '';
        document.getElementById('searchRight').value = '';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        filterTable();
    }
    
    // Select all checkboxes
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.customer-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    }
</script>
@endsection
