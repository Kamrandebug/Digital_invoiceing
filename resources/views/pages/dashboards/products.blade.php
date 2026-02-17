{{--
    Product Management Page
    Path: resources/views/pages/dashboards/products.blade.php
--}}

@extends('layouts.master')

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* ===== CONTAINER ===== */
    .product-management-container {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    /* ===== TOP HEADER ===== */
    .management-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .management-header h2 {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .btn-add-product {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
    }

    .btn-add-product:hover {
        background: #218838;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(40,167,69,0.3);
    }

    /* ===== STATS CARDS ===== */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }

    .stat-card {
        padding: 18px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .stat-card.total    { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
    .stat-card.active   { background: linear-gradient(135deg, #17a2b8, #36b9cc); color: white; }
    .stat-card.low      { background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; }
    .stat-card.inactive { background: linear-gradient(135deg, #6c757d, #868e96); color: white; }

    .stat-icon { font-size: 28px; opacity: 0.85; }

    .stat-info p  { margin: 0; font-size: 13px; opacity: 0.9; }
    .stat-info h3 { margin: 0; font-size: 24px; font-weight: 700; }

    /* ===== FILTERS ===== */
    .filter-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .filter-row {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
        flex: 1;
        min-width: 140px;
    }

    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #555;
    }

    .filter-group input,
    .filter-group select {
        padding: 9px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        width: 100%;
    }

    .filter-group input:focus,
    .filter-group select:focus {
        border-color: #28a745;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.15);
    }

    .btn-filter-search {
        background: #28a745;
        color: white;
        padding: 9px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        white-space: nowrap;
        transition: background 0.2s;
    }

    .btn-filter-search:hover { background: #218838; }

    .btn-filter-clear {
        background: #6c757d;
        color: white;
        padding: 9px 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        white-space: nowrap;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
    }

    .btn-filter-clear:hover { background: #5a6268; color: white; }

    /* ===== EXPORT BUTTONS ===== */
    .export-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .export-buttons { display: flex; gap: 8px; flex-wrap: wrap; }

    .btn-export {
        padding: 7px 14px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: white;
        cursor: pointer;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        color: #333;
        transition: all 0.2s;
    }

    .btn-export:hover         { background: #f0f0f0; color: #333; }
    .btn-export.csv           { border-color: #28a745; color: #28a745; }
    .btn-export.csv:hover     { background: #28a745; color: white; }
    .btn-export.print         { border-color: #6c757d; color: #6c757d; }
    .btn-export.print:hover   { background: #6c757d; color: white; }

    .results-count { font-size: 14px; color: #666; }

    /* ===== TABLE ===== */
    .table-wrapper { overflow-x: auto; }

    .products-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .products-table thead th {
        background: #f8f9fa;
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        color: #444;
        border-bottom: 2px solid #e9ecef;
        white-space: nowrap;
    }

    .products-table tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.15s;
    }

    .products-table tbody tr:hover { background: #f9fff9; }

    .products-table tbody td {
        padding: 12px 14px;
        color: #555;
        vertical-align: middle;
    }

    /* ===== BADGES ===== */
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }

    .status-active   { background: #d4edda; color: #155724; }
    .status-inactive { background: #f8d7da; color: #721c24; }

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

    /* ===== ACTION BUTTONS ===== */
    .action-buttons { display: flex; gap: 5px; }

    .btn-view, .btn-edit-row, .btn-delete-row {
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: opacity 0.2s;
    }

    .btn-view     { background: #17a2b8; color: white; }
    .btn-edit-row { background: #28a745; color: white; }
    .btn-delete-row { background: #dc3545; color: white; }

    .btn-view:hover, .btn-edit-row:hover, .btn-delete-row:hover {
        opacity: 0.85;
        color: white;
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #888;
    }

    .empty-state i    { font-size: 48px; margin-bottom: 15px; color: #ccc; }
    .empty-state h4   { font-size: 18px; color: #555; margin-bottom: 8px; }
    .empty-state p    { font-size: 14px; }

    /* ===== PAGINATION ===== */
    .pagination-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 14px;
        color: #666;
    }

    /* ===== LOW STOCK ALERT ===== */
    .low-stock-alert {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 6px;
        padding: 12px 18px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    .low-stock-alert .alert-text { color: #856404; font-weight: 500; }
    .low-stock-alert .alert-text i { margin-right: 8px; }

    .btn-dismiss-alert {
        background: transparent;
        border: none;
        color: #dc3545;
        cursor: pointer;
        font-size: 13px;
        padding: 4px 10px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .stats-row { grid-template-columns: repeat(2, 1fr); }
        .filter-row { flex-direction: column; }
        .management-header { flex-direction: column; align-items: flex-start; }
    }
</style>
@endsection

@section('content')

{{-- Page Header --}}
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Product Management</h1>
        <div class="d-flex align-items-center gap-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
                <li class="breadcrumb-item active" aria-current="page">Products</li>
            </ol>
        </div>
    </div>
</div>

{{-- Session Messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Low Stock Alert --}}
@php
    $lowStockCount = collect($products)->where('StockStatus', 0)->count();
@endphp
@if($lowStockCount > 0)
<div class="low-stock-alert" id="lowStockAlert">
    <span class="alert-text">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>{{ $lowStockCount }} product(s)</strong> are running low on stock.
    </span>
    <button class="btn-dismiss-alert" onclick="document.getElementById('lowStockAlert').style.display='none'">
        <i class="fas fa-times"></i> Dismiss
    </button>
</div>
@endif

{{-- Main Container --}}
<div class="product-management-container">

    {{-- Header --}}
    <div class="management-header">
        <h2><i class="fas fa-boxes me-2" style="color:#28a745;"></i> Products List</h2>
        <a href="{{ route('products.create') }}" class="btn-add-product">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>

    {{-- Stats Cards --}}
    @php
        $totalProducts    = count($products);
        $activeProducts   = collect($products)->where('Status', 1)->count();
        $lowStock         = collect($products)->where('StockStatus', 0)->count();
        $inactiveProducts = collect($products)->where('Status', 0)->count();
    @endphp
    <div class="stats-row">
        <div class="stat-card total">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-info">
                <p>Total Products</p>
                <h3>{{ $totalProducts }}</h3>
            </div>
        </div>
        <div class="stat-card active">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <p>Active</p>
                <h3>{{ $activeProducts }}</h3>
            </div>
        </div>
        <div class="stat-card low">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <p>Low Stock</p>
                <h3>{{ $lowStock }}</h3>
            </div>
        </div>
        <div class="stat-card inactive">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-info">
                <p>Inactive</p>
                <h3>{{ $inactiveProducts }}</h3>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-section">
        <form method="GET" action="{{ route('products.index') }}" id="filterForm">
            <div class="filter-row">

                <div class="filter-group" style="flex: 2; min-width: 200px;">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by name, code...">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}">
                </div>

                <div class="filter-group" style="flex: none;">
                    <label>&nbsp;</label>
                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn-filter-search">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="{{ route('products.index') }}" class="btn-filter-clear">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>

            </div>

            {{-- Validation Errors --}}
            @if($errors->any())
            <div class="alert alert-danger mt-3 mb-0">
                @foreach($errors->all() as $error)
                    <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                @endforeach
            </div>
            @endif
        </form>
    </div>

    {{-- Export Bar --}}
    <div class="export-bar">
        <div class="results-count">
            <i class="fas fa-list"></i>
            Showing <strong>{{ count($products) }}</strong> product(s)
            @if(request('search'))
                for "<strong>{{ request('search') }}</strong>"
            @endif
        </div>
        <div class="export-buttons">
            <a href="{{ route('products.exportCSV', request()->query()) }}" class="btn-export csv">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <button class="btn-export print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrapper">
        <table class="products-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Sale Price</th>
                    <th>Cost</th>
                    <th>Current Qty</th>
                    <th>Low Stock Level</th>
                    <th>Tax Rate</th>
                    <th>Stock Status</th>
                    <th>Status</th>
                    <th>Created On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $index => $product)
                <tr>
                    <td>{{ $index + 1 }}</td>

                    <td>
                        <span style="font-weight:600; color:#333;">{{ $product->Code ?? '-' }}</span>
                    </td>

                    <td>
                        <div style="font-weight:500; color:#222;">{{ $product->Name ?? '-' }}</div>
                        @if(!empty($product->Type))
                            <small style="color:#888;">{{ $product->Type }}</small>
                        @endif
                    </td>

                    <td>{{ $product->Type ?? '-' }}</td>

                    <td>
                        <strong>PKR {{ number_format($product->SalePrice ?? 0, 2) }}</strong>
                        @if(($product->Discount ?? 0) > 0)
                            <br><small class="text-muted">{{ $product->Discount }}% off</small>
                        @endif
                    </td>

                    <td>PKR {{ number_format($product->Cost ?? 0, 2) }}</td>

                    <td>
                        <span style="font-weight:600;">{{ $product->CurrentQuantity ?? 0 }}</span>
                    </td>

                    <td>{{ $product->LowStockLevel ?? 0 }}</td>

                    <td>{{ $product->TaxRate ?? 0 }}%</td>

                    <td>
                        @php $ss = $product->StockStatus ?? 1; @endphp
                        @if($ss == 1)
                            <span class="stock-badge stock-in">In Stock</span>
                        @elseif($ss == 0)
                            <span class="stock-badge stock-low">Low Stock</span>
                        @else
                            <span class="stock-badge stock-out">Out of Stock</span>
                        @endif
                    </td>

                    <td>
                        @if(($product->Status ?? 1) == 1)
                            <span class="status-badge status-active">Active</span>
                        @else
                            <span class="status-badge status-inactive">Inactive</span>
                        @endif
                    </td>

                    <td style="color:#888; font-size:13px;">
                        {{ isset($product->CreatedOn) ? \Carbon\Carbon::parse($product->CreatedOn)->format('d M Y') : '-' }}
                    </td>

                    <td>
                        <div class="action-buttons">
                            {{-- View --}}
                            <a href="{{ route('products.show', $product->ID) }}"
                               class="btn-view" title="View">
                                <i class="fas fa-eye"></i>
                            </a>

                            {{-- Edit --}}
                            <a href="{{ route('products.edit', $product->ID) }}"
                               class="btn-edit-row" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>

                            {{-- Delete --}}
                            <button class="btn-delete-row"
                                    title="Archive"
                                    onclick="confirmDelete({{ $product->ID }}, '{{ addslashes($product->Name) }}')">
                                <i class="fas fa-trash"></i>
                            </button>

                            {{-- Hidden delete form --}}
                            <form id="delete-form-{{ $product->ID }}"
                                  action="{{ route('products.delete_transaction', $product->ID) }}"
                                  method="POST" style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="13">
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h4>No Products Found</h4>
                            <p>
                                @if(request('search') || request('start_date'))
                                    No products match your current filters.
                                    <a href="{{ route('products.index') }}">Clear filters</a>
                                @else
                                    No products have been added yet.
                                    <a href="{{ route('products.create') }}">Add your first product</a>
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination Bar --}}
    <div class="pagination-bar">
        <span>
            Showing {{ count($products) }} of {{ count($products) }} results
        </span>
        {{--
            If you add Laravel pagination later, replace with:
            {{ $products->appends(request()->query())->links() }}
        --}}
    </div>

</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-trash me-2"></i> Archive Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to archive <strong id="deleteProductName"></strong>?</p>
                <p class="text-muted" style="font-size:13px;">
                    <i class="fas fa-info-circle me-1"></i>
                    This will set the product status to archived. It will not be permanently deleted.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
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
        document.getElementById('deleteProductName').textContent = name;
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteProductId) {
            document.getElementById('delete-form-' + deleteProductId).submit();
        }
    });
</script>
@endsection