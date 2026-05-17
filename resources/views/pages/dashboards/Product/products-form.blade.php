{{--
    Product Form
--}}

@extends('layouts.master')

@php
    $isReadonly = ($mode === 'show');
    $isEdit = ($mode === 'edit');
    $isCreate = ($mode === 'create');
    $pageTitle = $isCreate ? 'Add New Product' : ($isEdit ? 'Edit Product' : 'Product Details');
    $formAction = $isCreate ? route('products.save_transaction') : ($isEdit ? route('products.edit_transaction', $product->ID) : '#');
    $categories = $categories ?? collect();
    $units = $units ?? collect();
    $defaultCategoryId = $defaultCategoryId ?? null;
    $defaultUnitId = $defaultUnitId ?? null;
    $catFromProduct = $product->CategoryID ?? $product->Category ?? null;
    $catFallback = ($catFromProduct !== null && $catFromProduct !== '' && (string) $catFromProduct !== '0')
        ? $catFromProduct
        : ($defaultCategoryId ?? '');
    $pfpSelectedCategory = old('Category', $catFallback);
    $unitFromProduct = $product->Unit ?? null;
    $unitFallback = ($unitFromProduct !== null && $unitFromProduct !== '' && (int) $unitFromProduct !== 0)
        ? $unitFromProduct
        : ($defaultUnitId ?? '');
    $pfpSelectedUnit = old('Unit', $unitFallback);
@endphp

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">
<style>
.modal.pfp-quick-lookup-modal { z-index: 2060; }
.modal.pfp-quick-lookup-modal .modal-dialog {
    max-width: 400px;
}
.modal.pfp-quick-lookup-modal .modal-content {
    border: none;
    box-shadow: 0 0.75rem 2rem rgba(15, 23, 42, 0.28);
    border-radius: 0.5rem;
}
.modal.pfp-quick-lookup-modal .modal-header {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
}
.modal.pfp-quick-lookup-modal .modal-body {
    padding: 0.75rem 1rem;
}
.modal.pfp-quick-lookup-modal .modal-body .form-label {
    margin-bottom: 0.35rem;
    font-size: 12px;
}
.modal.pfp-quick-lookup-modal .modal-footer {
    padding: 0.5rem 1rem;
    border-top: 1px solid var(--bs-border-color, #dee2e6);
}
.modal-backdrop.pfp-quick-lookup-backdrop { z-index: 2055; }
</style>
@endsection

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">{{ $pageTitle }}</h1>
        <div class="d-flex align-items-center gap-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
            </ol>
        </div>
    </div>
</div>

{{-- Messages --}}
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

{{-- Info Badge --}}
@if(!$isCreate)
<div class="product-info-badge {{ $isReadonly ? 'show-mode' : '' }}">
    <div class="row align-items-center">
        <div class="col-md-2">
            <strong><i class="fas fa-id-badge"></i> ID:</strong> #{{ $product->ID }}
        </div>
        <div class="col-md-2">
            <strong><i class="fas fa-barcode"></i> Code:</strong> {{ $product->Code ?? 'N/A' }}
        </div>
        <div class="col-md-2">
            <strong><i class="fas fa-tag"></i> Price:</strong> PKR {{ number_format($product->SalePrice ?? 0, 2) }}
        </div>
        <div class="col-md-2">
            <strong><i class="fas fa-boxes"></i> Qty:</strong> {{ $product->CurrentQuantity ?? 0 }}
        </div>
        <div class="col-md-2">
            <strong><i class="fas fa-circle"></i> Stock:</strong>
            @php $ss = $product->StockStatus ?? 1; @endphp
            @if($ss == 1)
                <span class="stock-badge stock-in">In Stock</span>
            @elseif($ss == 0)
                <span class="stock-badge stock-low">Low Stock</span>
            @else
                <span class="stock-badge stock-out">Out of Stock</span>
            @endif
        </div>
        @if($isReadonly)
        <div class="col-md-2 text-end">
            <a href="{{ route('products.edit', $product->ID ?? 0) }}" class="btn btn-light btn-sm fw-bold text-primary shadow-sm">
                <i class="fas fa-edit"></i> Edit Product
            </a>
        </div>
        @endif
    </div>
</div>
@endif

<div class="product-form-container {{ $isReadonly ? 'show-mode' : '' }}">
    <div class="form-header">
        <h1>
            @if($isCreate)
                <i class="fas fa-plus-circle"></i> Add New Product
            @elseif($isEdit)
                <i class="fas fa-edit"></i> Edit Product Details
            @else
                <i class="fas fa-box"></i> Product Profile
                <div class="mt-2" style="font-size: 12px; color: #666; font-weight: normal;">
                    <span class="badge bg-secondary"><i class="fas fa-fingerprint"></i> Req ID: {{ $product->RequestID ?? 'N/A' }}</span>
                </div>
            @endif
        </h1>
    </div>

    @if(!$isReadonly)
    <form action="{{ $formAction }}" method="POST" id="productForm" novalidate>
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
    @endif

        {{-- Basic Info --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-box-open"></i> Basic Information
            </h3>

            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="Name" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Product Name</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->Name ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('Name') is-invalid @enderror"
                                   id="Name" name="Name"
                                   value="{{ old('Name', $product->Name ?? '') }}" placeholder="Enter product name" required>
                            @error('Name')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Code" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Product Code</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->Code ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('Code') is-invalid @enderror"
                                   id="Code" name="Code"
                                   value="{{ old('Code', $product->Code ?? '') }}"
                                   placeholder="{{ $isCreate ? 'Auto-assigned if left blank' : '' }}"
                                   autocomplete="off">
                            @error('Code')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Type" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Type</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->Type ?? '-' }}</div>
                        @else
                            <select name="Type" id="Type" class="form-select @error('Type') is-invalid @enderror">
                                <option value="">Select Type</option>
                                <option value="Product" {{ old('Type', $product->Type ?? '') == 'Product' ? 'selected' : '' }}>Product</option>
                                <option value="Service" {{ old('Type', $product->Type ?? '') == 'Service' ? 'selected' : '' }}>Service</option>
                                <option value="Raw Material" {{ old('Type', $product->Type ?? '') == 'Raw Material' ? 'selected' : '' }}>Raw Material</option>
                            </select>
                            @error('Type')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>

            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="Category" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Category</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->CategoryName ?? $product->Category ?? '-' }}</div>
                        @else
                            <div class="d-flex gap-1 align-items-center">
                                <select name="Category" id="Category" class="form-select @error('Category') is-invalid @enderror flex-grow-1" style="min-width:0;">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (string) $pfpSelectedCategory === (string) $cat->id ? 'selected' : '' }}>{{ $cat->CategoryName }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-primary pfp-line-add-entity-btn flex-shrink-0" id="productForm_newCategoryBtn" title="New category" aria-label="New category"><i class="fas fa-plus"></i></button>
                            </div>
                            @error('Category')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="Unit" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Unit of Measure</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->UnitName ?? $product->Unit ?? '-' }}</div>
                        @else
                            <div class="d-flex gap-1 align-items-center">
                                <select name="Unit" id="Unit" class="form-select @error('Unit') is-invalid @enderror flex-grow-1" style="min-width:0;">
                                    <option value="">Select Unit</option>
                                    @foreach($units as $un)
                                        <option value="{{ $un->id }}" {{ (string) $pfpSelectedUnit === (string) $un->id ? 'selected' : '' }}>{{ $un->UnitName }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-primary pfp-line-add-entity-btn flex-shrink-0" id="productForm_newUnitBtn" title="New unit" aria-label="New unit"><i class="fas fa-plus"></i></button>
                            </div>
                            @error('Unit')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>

            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="SaleInformation" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Sale Information</label>
                        @if($isReadonly)
                            <div class="detail-value" style="min-height: 60px;">{{ $product->SaleInformation ?? '-' }}</div>
                        @else
                            <textarea class="form-control @error('SaleInformation') is-invalid @enderror"
                                      id="SaleInformation" name="SaleInformation" rows="2"
                                      placeholder="Enter sale information / description">{{ old('SaleInformation', $product->SaleInformation ?? '') }}</textarea>
                            <small class="text-muted">(Optional)</small>
                            @error('SaleInformation')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>

            {{-- Flags --}}
            @if(!$isReadonly)
            <div class="row">
                <div class="col-md-3">
                    <label class="checkbox-label">
                        <input type="hidden" name="IsSelltoCustomer" value="0">
                        <input type="checkbox" name="IsSelltoCustomer" id="IsSelltoCustomer" value="1"
                               {{ old('IsSelltoCustomer', $product->IsSelltoCustomer ?? '1') == '1' ? 'checked' : '' }}>
                        <span>Sell to Customer</span>
                    </label>
                </div>
                <div class="col-md-3">
                    <label class="checkbox-label">
                        <input type="hidden" name="IsPurchasing" value="0">
                        <input type="checkbox" name="IsPurchasing" id="IsPurchasing" value="1"
                               {{ old('IsPurchasing', $product->IsPurchasing ?? '0') == '1' ? 'checked' : '' }}>
                        <span>Enable Purchasing</span>
                    </label>
                </div>
                <div class="col-md-3">
                    <label class="checkbox-label">
                        <input type="hidden" name="IsShipping" value="0">
                        <input type="checkbox" name="IsShipping" id="IsShipping" value="1"
                               {{ old('IsShipping', $product->IsShipping ?? '0') == '1' ? 'checked' : '' }}>
                        <span>Requires Shipping</span>
                    </label>
                </div>
                <div class="col-md-3">
                    <label class="checkbox-label">
                        <input type="hidden" name="IsRawMaterial" value="0">
                        <input type="checkbox" name="IsRawMaterial" id="IsRawMaterial" value="1"
                               {{ old('IsRawMaterial', $product->IsRawMaterial ?? '0') == '1' ? 'checked' : '' }}>
                        <span>Is Raw Material</span>
                    </label>
                </div>
            </div>
            @else
            <div class="row mt-3">
                <div class="col-md-3">
                    <span class="detail-label">Sell to Customer</span>
                    <div class="detail-value">
                        {!! $product->IsSelltoCustomer ? '<i class="fas fa-check-circle text-success"></i> Yes' : '<i class="fas fa-times-circle text-danger"></i> No' !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <span class="detail-label">Purchasing</span>
                    <div class="detail-value">
                        {!! $product->IsPurchasing ? '<i class="fas fa-check-circle text-success"></i> Yes' : '<i class="fas fa-times-circle text-danger"></i> No' !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <span class="detail-label">Shipping</span>
                    <div class="detail-value">
                        {!! $product->IsShipping ? '<i class="fas fa-check-circle text-success"></i> Yes' : '<i class="fas fa-times-circle text-danger"></i> No' !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <span class="detail-label">Raw Material</span>
                    <div class="detail-value">
                        {!! $product->IsRawMaterial ? '<i class="fas fa-check-circle text-success"></i> Yes' : '<i class="fas fa-times-circle text-danger"></i> No' !!}
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Pricing Info --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-dollar-sign"></i> Pricing Information
            </h3>

            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="SalePrice" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Sale Price (PKR)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($product->SalePrice ?? 0, 2) }}</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('SalePrice') is-invalid @enderror"
                                   id="SalePrice" name="SalePrice"
                                   value="{{ old('SalePrice', $product->SalePrice ?? '0.00') }}" placeholder="0.00" required>
                            @error('SalePrice')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Cost" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Cost (PKR)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($product->Cost ?? 0, 2) }}</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('Cost') is-invalid @enderror"
                                   id="Cost" name="Cost"
                                   value="{{ old('Cost', $product->Cost ?? '0.00') }}" placeholder="0.00">
                            @error('Cost')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Discount" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Discount (%)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($product->Discount ?? 0, 2) }}%</div>
                        @else
                            <input type="number" step="0.0001" class="form-control @error('Discount') is-invalid @enderror"
                                   id="Discount" name="Discount"
                                   value="{{ old('Discount', $product->Discount ?? '0.00') }}" placeholder="0.00">
                            @error('Discount')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="PurchaseDiscount" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Purchase Discount (%)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($product->PurchaseDiscount ?? 0, 2) }}%</div>
                        @else
                            <input type="number" step="0.0001" class="form-control @error('PurchaseDiscount') is-invalid @enderror"
                                   id="PurchaseDiscount" name="PurchaseDiscount"
                                   value="{{ old('PurchaseDiscount', $product->PurchaseDiscount ?? '0.00') }}" placeholder="0.00">
                            @error('PurchaseDiscount')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>

            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="TPRate" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">eeOrRetailPrice</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($product->TPRate ?? 0, 2) }}</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('TPRate') is-invalid @enderror"
                                   id="TPRate" name="TPRate"
                                   value="{{ old('TPRate', $product->TPRate ?? '0.00') }}" placeholder="0.00">
                            @error('TPRate')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="TPMargin" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">TP Margin (%)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($product->TPMargin ?? 0, 2) }}%</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('TPMargin') is-invalid @enderror"
                                   id="TPMargin" name="TPMargin"
                                   value="{{ old('TPMargin', $product->TPMargin ?? '0.00') }}" placeholder="0.00">
                            @error('TPMargin')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="TrialRate" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Trial Rate</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($product->TrialRate ?? 0, 2) }}</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('TrialRate') is-invalid @enderror"
                                   id="TrialRate" name="TrialRate"
                                   value="{{ old('TrialRate', $product->TrialRate ?? '0.00') }}" placeholder="0.00">
                            @error('TrialRate')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Weight" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Weight (kg)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($product->Weight ?? 0, 2) }}</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('Weight') is-invalid @enderror"
                                   id="Weight" name="Weight"
                                   value="{{ old('Weight', $product->Weight ?? '0.00') }}" placeholder="0.00">
                            @error('Weight')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tax Info --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-receipt"></i> Tax Information
            </h3>

            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="TaxCode" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Tax Code</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->TaxCode ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('TaxCode') is-invalid @enderror"
                                   id="TaxCode" name="TaxCode"
                                   value="{{ old('TaxCode', $product->TaxCode ?? '') }}" placeholder="e.g. GST17">
                            @error('TaxCode')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="TaxRate" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Tax Rate (%)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->TaxRate ?? 0 }}%</div>
                        @else
                            <input type="number" class="form-control @error('TaxRate') is-invalid @enderror"
                                   id="TaxRate" name="TaxRate"
                                   value="{{ old('TaxRate', $product->TaxRate ?? '0') }}" placeholder="0" required>
                            @error('TaxRate')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="NFTaxRate" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">NF Tax Rate (%)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->NFTaxRate ?? '-' }}</div>
                        @else
                            <input type="number" class="form-control @error('NFTaxRate') is-invalid @enderror"
                                   id="NFTaxRate" name="NFTaxRate"
                                   value="{{ old('NFTaxRate', $product->NFTaxRate ?? '') }}" placeholder="0">
                            <small class="text-muted">Non-Filer Tax Rate (Optional)</small>
                            @error('NFTaxRate')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="TaxSchID" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Tax Scheme</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->TaxSchID ?? 4 }}</div>
                        @else
                            <input type="number" class="form-control @error('TaxSchID') is-invalid @enderror"
                                   id="TaxSchID" name="TaxSchID"
                                   value="{{ old('TaxSchID', $product->TaxSchID ?? '4') }}" placeholder="4">
                            @error('TaxSchID')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Stock Info --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-warehouse"></i> Stock Information
            </h3>

            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="LowStockLevel" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Low Stock Level</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->LowStockLevel ?? 0 }}</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('LowStockLevel') is-invalid @enderror"
                                   id="LowStockLevel" name="LowStockLevel"
                                   value="{{ old('LowStockLevel', $product->LowStockLevel ?? '0') }}" placeholder="0" required>
                            @error('LowStockLevel')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="CurrentQuantity" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Current Quantity</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->CurrentQuantity ?? 0 }}</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('CurrentQuantity') is-invalid @enderror"
                                   id="CurrentQuantity" name="CurrentQuantity"
                                   value="{{ old('CurrentQuantity', $product->CurrentQuantity ?? '0') }}" placeholder="0">
                            @error('CurrentQuantity')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="OpeningQuantity" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Opening Quantity</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $product->OpeningQuantity ?? 0 }}</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('OpeningQuantity') is-invalid @enderror"
                                   id="OpeningQuantity" name="OpeningQuantity"
                                   value="{{ old('OpeningQuantity', $product->OpeningQuantity ?? '0') }}" placeholder="0">
                            @error('OpeningQuantity')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="OpeningRate" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Opening Rate (PKR)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($product->OpeningRate ?? 0, 2) }}</div>
                        @else
                            <input type="number" step="0.000001" class="form-control @error('OpeningRate') is-invalid @enderror"
                                   id="OpeningRate" name="OpeningRate"
                                   value="{{ old('OpeningRate', $product->OpeningRate ?? '0.00') }}" placeholder="0.00">
                            @error('OpeningRate')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden defaults for removed Account/Optional sections --}}
        @if(!$isReadonly)
        <input type="hidden" name="Status" value="{{ old('Status', $product->Status ?? '1') }}">
        <input type="hidden" name="IncomeAccount" value="{{ old('IncomeAccount', $product->IncomeAccount ?? '0') }}">
        <input type="hidden" name="StockAccount" value="{{ old('StockAccount', $product->StockAccount ?? '0') }}">
        <input type="hidden" name="ExpenseAccount" value="{{ old('ExpenseAccount', $product->ExpenseAccount ?? '0') }}">
        <input type="hidden" name="PreferredSupplier" value="{{ old('PreferredSupplier', $product->PreferredSupplier ?? '0') }}">
        <input type="hidden" name="OptionalField1" value="{{ old('OptionalField1', $product->OptionalField1 ?? '') }}">
        <input type="hidden" name="OptionalField2" value="{{ old('OptionalField2', $product->OptionalField2 ?? '') }}">
        <input type="hidden" name="OptionalField3" value="{{ old('OptionalField3', $product->OptionalField3 ?? '0') }}">
        @endif

        {{-- Actions --}}
        <div class="action-buttons-bottom">
            @if($isReadonly)
                <div class="last-updated-info">
                    <i class="fas fa-clock"></i> Created: {{ isset($product->CreatedOn) ? \Carbon\Carbon::parse($product->CreatedOn)->format('M d, Y h:i A') : 'N/A' }}
                    <span class="mx-2">|</span>
                    <i class="fas fa-pen"></i> Updated: {{ isset($product->UpdatedOn) ? \Carbon\Carbon::parse($product->UpdatedOn)->format('M d, Y h:i A') : 'Never' }}
                </div>
                <div>
                    <a href="{{ route('products.index') }}" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            @elseif($isEdit)
                <div class="last-updated-info">
                    <i class="fas fa-info-circle"></i> Last updated:
                    {{ isset($product->UpdatedOn) ? \Carbon\Carbon::parse($product->UpdatedOn)->format('M d, Y h:i A') : 'Never' }}
                </div>
                <div>
                    <button type="button" class="btn-cancel" onclick="window.location.href='{{ route('products.index') }}'">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-update">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                </div>
            @else
                <button type="button" class="btn-cancel" onclick="window.location.href='{{ route('products.index') }}'">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Product
                </button>
            @endif
        </div>

    @if(!$isReadonly)
    </form>
    <div class="modal fade pfp-quick-lookup-modal" id="productForm_category_quick_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold mb-0">Add Category</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none py-2 mb-2 small" id="productForm_category_quick_err"></div>
                    <label class="form-label mb-1">Category name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="productForm_category_quick_name" maxlength="100" placeholder="Category name" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="productForm_category_quick_save">Save Category</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade pfp-quick-lookup-modal" id="productForm_unit_quick_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold mb-0">Add Unit</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none py-2 mb-2 small" id="productForm_unit_quick_err"></div>
                    <label class="form-label mb-1">Unit name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="productForm_unit_quick_name" maxlength="100" placeholder="e.g. PCS, KG" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="productForm_unit_quick_save">Save Unit</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
    @if(!$isReadonly)

    // Scroll to first error on load
    window.addEventListener('load', function() {
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    });

    (function () {
        var form = document.getElementById('productForm');
        function pfpCsrfToken() {
            var inp = form && form.querySelector('input[name="_token"]');
            if (inp && inp.value) return inp.value;
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? (m.getAttribute('content') || '') : '';
        }
        function pfpFetchHeaders() {
            var tok = pfpCsrfToken();
            return {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': tok
            };
        }
        function pfpElevateQuickModal(el) {
            if (!el || el.dataset.pfpElevated === '1') return;
            el._pfpParent = el.parentNode;
            el._pfpNext = el.nextSibling;
            document.body.appendChild(el);
            el.dataset.pfpElevated = '1';
        }
        function pfpRestoreQuickModal(el) {
            if (!el || el.dataset.pfpElevated !== '1') return;
            var p = el._pfpParent;
            if (p) {
                if (el._pfpNext && el._pfpNext.parentNode === p) p.insertBefore(el, el._pfpNext);
                else p.appendChild(el);
            }
            delete el._pfpParent;
            delete el._pfpNext;
            delete el.dataset.pfpElevated;
        }
        function pfpAppendSelectOption(select, id, label) {
            if (!select || id == null || id === '') return;
            var sid = String(id);
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === sid) {
                    select.value = sid;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    return;
                }
            }
            var o = document.createElement('option');
            o.value = sid;
            o.textContent = label || sid;
            select.appendChild(o);
            select.value = sid;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        function pfpShowQuickErr(el, msg) {
            if (!el) return;
            if (!msg) {
                el.classList.add('d-none');
                el.textContent = '';
                return;
            }
            el.textContent = msg;
            el.classList.remove('d-none');
        }
        var catSel = document.getElementById('Category');
        var unitSel = document.getElementById('Unit');
        var catModalEl = document.getElementById('productForm_category_quick_modal');
        var unitModalEl = document.getElementById('productForm_unit_quick_modal');
        var catErrEl = document.getElementById('productForm_category_quick_err');
        var unitErrEl = document.getElementById('productForm_unit_quick_err');
        var catNameInput = document.getElementById('productForm_category_quick_name');
        var unitNameInput = document.getElementById('productForm_unit_quick_name');

        [catModalEl, unitModalEl].forEach(function(el) {
            if (!el) return;
            el.addEventListener('show.bs.modal', function() {
                pfpElevateQuickModal(el);
                setTimeout(function() {
                    var bd = document.querySelector('.modal-backdrop:last-of-type');
                    if (bd) bd.classList.add('pfp-quick-lookup-backdrop');
                }, 0);
            });
            el.addEventListener('hidden.bs.modal', function() {
                pfpRestoreQuickModal(el);
            });
        });

        document.getElementById('productForm_newCategoryBtn')?.addEventListener('click', function() {
            pfpShowQuickErr(catErrEl, '');
            if (catNameInput) catNameInput.value = '';
            if (catModalEl) bootstrap.Modal.getOrCreateInstance(catModalEl, { backdrop: true, keyboard: true }).show();
        });
        document.getElementById('productForm_newUnitBtn')?.addEventListener('click', function() {
            pfpShowQuickErr(unitErrEl, '');
            if (unitNameInput) unitNameInput.value = '';
            if (unitModalEl) bootstrap.Modal.getOrCreateInstance(unitModalEl, { backdrop: true, keyboard: true }).show();
        });

        document.getElementById('productForm_category_quick_save')?.addEventListener('click', function() {
            var name = (catNameInput && catNameInput.value) ? catNameInput.value.trim() : '';
            if (!name) {
                pfpShowQuickErr(catErrEl, 'Category name is required.');
                return;
            }
            pfpShowQuickErr(catErrEl, '');
            var tok = pfpCsrfToken();
            if (!tok) {
                pfpShowQuickErr(catErrEl, 'Security token missing. Refresh the page and try again.');
                return;
            }
            var fd = new FormData();
            fd.append('CategoryName', name);
            fd.append('_token', tok);
            fetch(@json(route('products.lookups.category-store')), {
                method: 'POST',
                headers: pfpFetchHeaders(),
                body: fd,
                credentials: 'same-origin'
            })
            .then(function(res) {
                return res.text().then(function(text) {
                    var data = {};
                    try { data = text ? JSON.parse(text) : {}; } catch (e) {}
                    return { res: res, data: data };
                });
            })
            .then(function(pair) {
                if (pair.res.status === 419) {
                    pfpShowQuickErr(catErrEl, 'Session expired (CSRF). Refresh the page and try again.');
                    return;
                }
                if (pair.res.ok && pair.data && pair.data.status === 'success' && pair.data.item) {
                    pfpAppendSelectOption(catSel, pair.data.item.id, pair.data.item.name);
                    if (catModalEl) bootstrap.Modal.getInstance(catModalEl)?.hide();
                    return;
                }
                var msg = (pair.data && pair.data.message) ? pair.data.message : 'Could not save.';
                if (pair.data && pair.data.errors && pair.data.errors.CategoryName && pair.data.errors.CategoryName[0]) {
                    msg = pair.data.errors.CategoryName[0];
                }
                pfpShowQuickErr(catErrEl, msg);
            })
            .catch(function() { pfpShowQuickErr(catErrEl, 'Network error.'); });
        });

        document.getElementById('productForm_unit_quick_save')?.addEventListener('click', function() {
            var name = (unitNameInput && unitNameInput.value) ? unitNameInput.value.trim() : '';
            if (!name) {
                pfpShowQuickErr(unitErrEl, 'Unit name is required.');
                return;
            }
            pfpShowQuickErr(unitErrEl, '');
            var tok = pfpCsrfToken();
            if (!tok) {
                pfpShowQuickErr(unitErrEl, 'Security token missing. Refresh the page and try again.');
                return;
            }
            var fd = new FormData();
            fd.append('UnitName', name);
            fd.append('_token', tok);
            fetch(@json(route('products.lookups.unit-store')), {
                method: 'POST',
                headers: pfpFetchHeaders(),
                body: fd,
                credentials: 'same-origin'
            })
            .then(function(res) {
                return res.text().then(function(text) {
                    var data = {};
                    try { data = text ? JSON.parse(text) : {}; } catch (e) {}
                    return { res: res, data: data };
                });
            })
            .then(function(pair) {
                if (pair.res.status === 419) {
                    pfpShowQuickErr(unitErrEl, 'Session expired (CSRF). Refresh the page and try again.');
                    return;
                }
                if (pair.res.ok && pair.data && pair.data.status === 'success' && pair.data.item) {
                    pfpAppendSelectOption(unitSel, pair.data.item.id, pair.data.item.name);
                    if (unitModalEl) bootstrap.Modal.getInstance(unitModalEl)?.hide();
                    return;
                }
                var msg = (pair.data && pair.data.message) ? pair.data.message : 'Could not save.';
                if (pair.data && pair.data.errors && pair.data.errors.UnitName && pair.data.errors.UnitName[0]) {
                    msg = pair.data.errors.UnitName[0];
                }
                pfpShowQuickErr(unitErrEl, msg);
            })
            .catch(function() { pfpShowQuickErr(unitErrEl, 'Network error.'); });
        });
    })();

    /**
     * Auto-save draft
     */
    const formId    = 'productForm';
    const formMode  = '{{ $mode }}';
    const productId = '{{ $product->ID ?? "new" }}';
    const storagePrefix = 'product_draft_' + formMode + '_' + productId + '_';

    if (document.getElementById(formId)) {
        const form   = document.getElementById(formId);
        const inputs = form.querySelectorAll('input, textarea, select');

        // Restore
        if (!document.querySelector('.alert-danger')) {
            inputs.forEach(input => {
                if (input.type === 'hidden' || input.type === 'password') return;

                const key         = storagePrefix + input.name;
                const storedValue = localStorage.getItem(key);

                if (storedValue !== null) {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        const shouldBeChecked = (storedValue === 'true');
                        if (input.checked !== shouldBeChecked) {
                            input.checked = shouldBeChecked;
                            input.dispatchEvent(new Event('change'));
                        }
                    } else {
                        if (input.value !== storedValue) {
                            input.value = storedValue;
                            input.dispatchEvent(new Event('input'));
                        }
                    }
                }
            });
        }

        // Save
        inputs.forEach(input => {
            const saveHandler = function() {
                if (this.type === 'hidden' || this.type === 'password') return;

                const key = storagePrefix + this.name;
                let value;

                if (this.type === 'checkbox' || this.type === 'radio') {
                    value = this.checked;
                } else {
                    value = this.value;
                }

                localStorage.setItem(key, value);
            };

            input.addEventListener('input', saveHandler);
            input.addEventListener('change', saveHandler);
        });

        // Clear on submit
        form.addEventListener('submit', function() {
            inputs.forEach(input => {
                localStorage.removeItem(storagePrefix + input.name);
            });
        });
    }

    @endif
</script>
@endsection
