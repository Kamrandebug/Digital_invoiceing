{{--
    Product Management Page
    
    Path: resources/views/pages/dashboards/products.blade.php
    URL: /products
    
    This page REUSES the same components as customers-list.blade.php
    
    IMPORTANT: Converted to @include/@component to fix location issues.
--}}

@extends('layouts.master')

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">
@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Product Management</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
            <li class="breadcrumb-item"><a href="javascript:void(0);">Ecommerce</a></li>
            <li class="breadcrumb-item active" aria-current="page">Products</li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

<!-- Low Stock Alert (specific to products page) -->
<div class="alert alert-warning" style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <i class="fas fa-exclamation-triangle" style="color: #ff9800; margin-right: 10px;"></i>
        <strong>1 product is running low on stock (≤ 10 units).</strong>
    </div>
    <div>
        <button style="background-color: #fff; border: 1px solid #ffc107; color: #ff9800; padding: 5px 15px; margin-right: 10px; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-eye"></i> View Low Stock Products
        </button>
        <button style="background-color: transparent; border: none; color: #dc3545; padding: 5px 15px; cursor: pointer;">
            <i class="fas fa-times"></i> Dismiss
        </button>
    </div>
</div>

<!-- Product Management Container -->
<div class="customer-management-container">
    
    {{-- ✅ FIXED: Using correct component path --}}
    @include('layouts.components.data-management.page-header', ['addButtonText' => 'Add Product'])

    {{-- Custom product filters --}}
    <div class="search-filters-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search products...">
        </div>

        <div style="display: flex; gap: 15px; flex: 1; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 12px; color: #666; margin-bottom: 5px; display: block;">Category</label>
                <select style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    <option>All Categories</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 12px; color: #666; margin-bottom: 5px; display: block;">Brand</label>
                <select style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    <option>All Brands</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 12px; color: #666; margin-bottom: 5px; display: block;">Stock Status</label>
                <select style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    <option>All Status</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 12px; color: #666; margin-bottom: 5px; display: block;">Date Range</label>
                <input type="text" placeholder="Select date range" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
        </div>
    </div>

    {{-- Price/Cost Range Filters --}}
    <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
        <input type="text" placeholder="Min price" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 120px;">
        <input type="text" placeholder="Max price" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 120px;">
        <input type="text" placeholder="Min cost" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 120px;">
        <input type="text" placeholder="Max cost" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 120px;">
        <button class="btn-clear-filters">
            <i class="fas fa-times"></i> Clear Filters
        </button>
        <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
            <label>Search:</label>
            <input type="text" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 200px;">
        </div>
    </div>

    {{-- ✅ FIXED: Using correct component path --}}
    @include('layouts.components.data-management.export-buttons')

    {{-- ✅ FIXED: Using correct component path and @component for slots --}}
    @component('layouts.components.data-management.data-table', ['columns' => [
        ['label' => 'Sr. #'],
        ['label' => 'Code'],
        ['label' => 'SKU'],
        ['label' => 'Unique ID'],
        ['label' => 'Name'],
        ['label' => 'Description'],
        ['label' => 'Category'],
        ['label' => 'Brand'],
        ['label' => 'Price'],
        ['label' => 'Cost'],
        ['label' => 'Total Quantity'],
        ['label' => 'Total Cost'],
        ['label' => 'Type']
    ]])
        @php
            // Dummy product data
            $products = [
                ['sr' => 1, 'code' => '1258', 'sku' => '-', 'uid' => '-', 'name' => 'one product', 'desc' => '', 'category' => 'Sample Category 1307', 'brand' => 'Sample Brand 325', 'price' => 100.00, 'cost' => 50.00, 'qty' => 10, 'total_cost' => 950.00, 'type' => 'stock'],
                ['sr' => 2, 'code' => 'PRD-276', 'sku' => 'SKU-276', 'uid' => 'UID-276', 'name' => 'Example Product 276', 'desc' => 'Product Description 276', 'category' => 'Sample Category 1313', 'brand' => 'Sample Brand 390', 'price' => 99.99, 'cost' => 50.00, 'qty' => 100, 'total_cost' => 5000.00, 'type' => 'stock'],
                ['sr' => 3, 'code' => 'PRD-275', 'sku' => 'SKU-275', 'uid' => 'UID-275', 'name' => 'Example Product 275', 'desc' => 'Product Description 275', 'category' => 'Sample Category 1205', 'brand' => 'Sample Brand 282', 'price' => 99.99, 'cost' => 50.00, 'qty' => 100, 'total_cost' => 5000.00, 'type' => 'stock'],
                ['sr' => 4, 'code' => 'PRD-274', 'sku' => 'SKU-274', 'uid' => 'UID-274', 'name' => 'Example Product 274', 'desc' => 'Product Description 274', 'category' => 'Sample Category 1077', 'brand' => 'Another Brand 389', 'price' => 99.99, 'cost' => 50.00, 'qty' => 100, 'total_cost' => 5000.00, 'type' => 'stock'],
                ['sr' => 5, 'code' => 'PRD-273', 'sku' => 'SKU-273', 'uid' => 'UID-273', 'name' => 'Example Product 273', 'desc' => 'Product Description 273', 'category' => 'Sample Category 949', 'brand' => 'Another Brand 281', 'price' => 99.99, 'cost' => 50.00, 'qty' => 100, 'total_cost' => 5000.00, 'type' => 'stock'],
            ];
        @endphp

        @foreach($products as $product)
        <tr>
            <td><input type="checkbox"></td>
            <td>{{ $product['sr'] }}</td>
            <td>{{ $product['code'] }}</td>
            <td>{{ $product['sku'] }}</td>
            <td>{{ $product['uid'] }}</td>
            <td>{{ $product['name'] }}</td>
            <td>{{ $product['desc'] }}</td>
            <td>{{ $product['category'] }}</td>
            <td>{{ $product['brand'] }}</td>
            <td>{{ number_format($product['price'], 2) }}</td>
            <td>{{ number_format($product['cost'], 2) }}</td>
            <td>{{ $product['qty'] }}</td>
            <td>{{ number_format($product['total_cost'], 2) }}</td>
            <td>{{ $product['type'] }}</td>
            <td>
                <button class="btn-action btn-edit" style="background-color: #3b82f6; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-action btn-delete" style="background-color: #ef4444; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; margin-left: 5px;">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
        @endforeach
    @endcomponent

    {{-- ✅ FIXED: Using correct component path --}}
    @include('layouts.components.data-management.pagination', ['from' => 1, 'to' => 5, 'total' => 5])

</div>

@endsection

@section('scripts')
@endsection
