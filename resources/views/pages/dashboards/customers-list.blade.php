{{--
    Customer Management Page
    
    Path: resources/views/pages/dashboards/customers-list.blade.php
    URL: /customers-list
    
    This page uses reusable components from:
    resources/views/layouts/components/data-management/
    
    IMPORTANT: Converted to @include/@component to fix location issues.
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

<!-- Customer Management Container -->
<div class="customer-management-container">
    
    {{-- 
        ✅ COMPONENT 1: Page Header with Action Buttons 
    --}}
    @include('layouts.components.data-management.page-header', ['addButtonText' => 'Add Customer'])

    {{-- 
        ✅ COMPONENT 2: Search & Filters Section 
    --}}
    @include('layouts.components.data-management.search-filters', [
        'searchPlaceholder' => 'Search customers...', 
        'showDateFilters' => true
    ])

    {{-- 
        ✅ COMPONENT 3: Export Buttons 
    --}}
    @include('layouts.components.data-management.export-buttons')

    {{-- 
        ✅ COMPONENT 4: Data Table with Customer Data 
        Using @component because it requires a slot for the rows
    --}}
    @component('layouts.components.data-management.data-table', ['columns' => [
        ['label' => 'ID', 'sortable' => true],
        ['label' => 'Name', 'sortable' => true],
        ['label' => 'Address'],
        ['label' => 'Contact Number'],
        ['label' => 'Closing Balance', 'info' => true],
        ['label' => 'Opening Balance Date', 'info' => true]
    ]])
        
        @php
            // Dummy customer data (hardcoded for demonstration)
            $customers = [
                ['id' => 161, 'name' => 'Sukkur General Store', 'address' => 'Civil Lines, Sukkur', 'phone' => '3355578001', 'balance' => 2200.00],
                ['id' => 147, 'name' => 'Ahmad Electronics', 'address' => 'Shop #15, Main Market, Lahore', 'phone' => '3001234567', 'balance' => 5000.00],
                ['id' => 148, 'name' => 'Karachi Traders', 'address' => 'Plot 45, Industrial Area, Karachi', 'phone' => '3211234567', 'balance' => 0.00],
                ['id' => 149, 'name' => 'Islamabad Office Solutions', 'address' => 'F-8 Markaz, Islamabad', 'phone' => '3331234567', 'balance' => 6300.00],
                ['id' => 150, 'name' => 'Faisalabad Furniture Co.', 'address' => 'Jhang Road, Faisalabad', 'phone' => '3041234567', 'balance' => 0.00],
                ['id' => 151, 'name' => 'Multan Medical Store', 'address' => 'Nawan Sheir, Multan', 'phone' => '3061234567', 'balance' => 1500.00],
                ['id' => 152, 'name' => 'Peshawar Auto Parts', 'address' => 'University Road, Peshawar', 'phone' => '3451234567', 'balance' => 0.00],
                ['id' => 153, 'name' => 'Quetta Hardware Store', 'address' => 'Jinnah Road, Quetta', 'phone' => '3361234567', 'balance' => 3000.00],
                ['id' => 154, 'name' => 'Sialkot Sports', 'address' => 'Paris Road, Sialkot', 'phone' => '3241234567', 'balance' => 0.00],
                ['id' => 155, 'name' => 'Gujranwala Textiles', 'address' => 'GT Road, Gujranwala', 'phone' => '3004567890', 'balance' => 4000.00],
                ['id' => 156, 'name' => 'Rawalpindi Electronics', 'address' => 'Murree Road, Rawalpindi', 'phone' => '3123456789', 'balance' => 0.00],
                ['id' => 157, 'name' => 'Hyderabad Traders', 'address' => 'Latifabad, Hyderabad', 'phone' => '3332345678', 'balance' => 2500.00],
                ['id' => 158, 'name' => 'Bahawalpur Furniture', 'address' => 'Model Town, Bahawalpur', 'phone' => '3012345678', 'balance' => 0.00],
                ['id' => 159, 'name' => 'Sargodha Hardware', 'address' => 'University Road, Sargodha', 'phone' => '3456789012', 'balance' => 1800.00],
                ['id' => 160, 'name' => 'Abbottabad Textiles', 'address' => 'Supply Bazar, Abbottabad', 'phone' => '3223456789', 'balance' => 3500.00],
            ];
        @endphp

        {{-- Loop through customers and create table rows --}}
        @foreach($customers as $customer)
        <tr>
            <td><input type="checkbox"></td>
            <td>{{ $customer['id'] }}</td>
            <td>{{ $customer['name'] }}</td>
            <td>{{ $customer['address'] }}</td>
            <td>{{ $customer['phone'] }}</td>
            <td class="{{ $customer['balance'] > 0 ? 'balance-red' : 'balance-green' }}">
                {{ number_format($customer['balance'], 2) }} PKR
            </td>
            <td>N/A</td>
            <td>
                <button class="btn-action btn-edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-action btn-info">
                    <i class="fas fa-file-alt"></i>
                </button>
                <button class="btn-action btn-delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
        @endforeach
        
    @endcomponent

    {{-- 
        ✅ COMPONENT 5: Pagination 
    --}}
    @include('layouts.components.data-management.pagination', ['from' => 1, 'to' => 10, 'total' => 15])

</div>

@endsection

@section('scripts')
{{-- No JavaScript needed for static table --}}
@endsection
