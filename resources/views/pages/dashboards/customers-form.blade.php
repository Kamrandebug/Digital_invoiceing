{{--
    Customer Master Form - Unified Create/Edit/Show Logic
    
    Path: resources/views/pages/dashboards/customers-form.blade.php
    
    Modes:
    - Create: $mode = 'create'
    - Edit: $mode = 'edit'
    - Show: $mode = 'show'
--}}

@extends('layouts.master')

@php
    $isReadonly = ($mode === 'show');
    $isEdit = ($mode === 'edit');
    $isCreate = ($mode === 'create');
    $pageTitle = $isCreate ? 'Add New Customer' : ($isEdit ? 'Edit Customer' : 'Customer Details');
    $formAction = $isCreate ? route('customers.save_transaction') : ($isEdit ? route('customers.edit_transaction', $customer->ID) : '#');
@endphp

@section('styles')
{{-- Font Awesome for icons --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
{{-- Custom CSS matching your existing design --}}
<style>
    .customer-form-container {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .form-header h1 {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }
    
    .customer-info-badge {
        background: linear-gradient(135deg, #6f42c1 0%, #9b59b6 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.2);
    }

    /* Show mode specific badge color */
    .customer-info-badge.show-mode {
        background: linear-gradient(135deg, #17a2b8 0%, #36b9cc 100%);
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.2);
    }
    
    .customer-info-badge .row > div {
        padding: 0;
    }
    
    .form-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 20px;
    }
    
    .form-section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #6f42c1;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Show mode specific title color */
    .show-mode .form-section-title, .show-mode .form-section-title i {
        color: #17a2b8;
    }
    
    .form-section-title i {
        color: #6f42c1;
    }
    
    .required-field::after {
        content: " *";
        color: #dc3545;
        display: {{ $isReadonly ? 'none' : 'inline' }};
    }
    
    .action-buttons-bottom {
        display: flex;
        justify-content: {{ $isCreate ? 'flex-end' : 'space-between' }};
        align-items: center;
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
    }
    
    .btn-save, .btn-update {
        background: #6f42c1;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-save:hover, .btn-update:hover {
        background: #5a32a3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
    }
    
    .btn-cancel, .btn-back {
        background: #6c757d;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-cancel:hover, .btn-back:hover {
        background: #5a6268;
        color: white;
    }

    .btn-edit {
        background: #6f42c1;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-edit:hover {
        background: #5a32a3;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        margin-top: 10px;
    }
    
    .form-control, .form-select {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        font-size: 14px;
        width: 100%;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        outline: none;
    }
    
    .last-updated-info {
        color: #6c757d;
        font-size: 14px;
    }
    
    /* Validation Error Styles */
    .is-invalid {
        border-color: #dc3545 !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        padding-right: calc(1.5em + 0.75rem);
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #dc3545;
    }
    
    .error-icon {
        margin-right: 5px;
    }

    /* Read-only specific styles */
    .detail-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 13px;
        margin-bottom: 5px;
        display: block;
    }

    .detail-value {
        font-size: 16px;
        color: #333;
        font-weight: 500;
        padding: 8px 12px;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 4px;
        min-height: 40px;
    }
</style>
@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">{{ $pageTitle }}</h1>
        <div class="d-flex align-items-center gap-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
            </ol>
        </div>
    </div>
</div>
<!-- End::page-header -->

<!-- Display Success Message -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Display Error Message -->
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Customer Info Badge (Edit/Show only) -->
@if(!$isCreate)
<div class="customer-info-badge {{ $isReadonly ? 'show-mode' : '' }}">
    <div class="row align-items-center">
        <div class="col-md-2">
            <strong><i class="fas fa-id-badge"></i> ID:</strong> #{{ $customer->ID }}
        </div>
        <div class="col-md-3">
            <strong><i class="fas fa-hashtag"></i> Account:</strong> {{ $customer->AccountNo ?? 'N/A' }}
        </div>
        <div class="col-md-3">
            <strong><i class="fas fa-wallet"></i> Balance:</strong> PKR {{ number_format($customer->PartyBalance, 2) }}
        </div>
        <div class="col-md-2">
            <strong><i class="fas fa-toggle-on"></i> Status:</strong> 
            @if($customer->Status == 1)
                <span class="badge bg-success" style="{{ $isReadonly ? 'color: white; background-color: #28a745 !important;' : '' }}">Active</span>
            @else
                <span class="badge bg-danger" style="{{ $isReadonly ? 'color: white; background-color: #dc3545 !important;' : '' }}">Inactive</span>
            @endif
        </div>
        @if($isReadonly)
        <div class="col-md-2 text-end">
            <a href="{{ route('customers.edit', $customer->ID ?? 0) }}" class="btn btn-light btn-sm fw-bold text-primary shadow-sm">
                <i class="fas fa-edit"></i> Edit Customer
            </a>
        </div>
        @endif
    </div>
</div>
@endif

<!-- Customer Form Container -->
<div class="customer-form-container {{ $isReadonly ? 'show-mode' : '' }}">
    <div class="form-header">
        <h1>
            @if($isCreate)
                <i class="fas fa-user-plus"></i> Add New Customer
            @elseif($isEdit)
                <i class="fas fa-user-edit"></i> Edit Customer Details
            @else
                <i class="fas fa-user-circle"></i> Customer Profile
                <div class="mt-2" style="font-size: 14px; color: #666; font-weight: normal;">
                    <span class="badge bg-secondary"><i class="fas fa-fingerprint"></i> Req ID: {{ $customer->RequestID ?? 'N/A' }}</span>
                </div>
            @endif
        </h1>
    </div>

    @if(!$isReadonly)
    <form action="{{ $formAction }}" method="POST" id="customerForm" novalidate>
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
    @endif

        {{-- SECTION 1: Basic Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-user"></i> Basic Information
            </h3>
            
            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="Title" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Title</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->Title ?? '-' }}</div>
                        @else
                            <select name="Title" id="Title" class="form-select @error('Title') is-invalid @enderror" required>
                                <option value="">Select</option>
                                <option value="Mr" {{ old('Title', $customer->Title ?? '') == 'Mr' ? 'selected' : '' }}>Mr</option>
                                <option value="Mrs" {{ old('Title', $customer->Title ?? '') == 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                <option value="Miss" {{ old('Title', $customer->Title ?? '') == 'Miss' ? 'selected' : '' }}>Miss</option>
                                <option value="Dr" {{ old('Title', $customer->Title ?? '') == 'Dr' ? 'selected' : '' }}>Dr</option>
                                <option value="Prof" {{ old('Title', $customer->Title ?? '') == 'Prof' ? 'selected' : '' }}>Prof</option>
                            </select>
                            @error('Title')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="FirstName" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">First Name</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->FirstName ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('FirstName') is-invalid @enderror" 
                                   id="FirstName" name="FirstName" 
                                   value="{{ old('FirstName', $customer->FirstName ?? '') }}" placeholder="Enter first name" required>
                            @error('FirstName')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="LastName" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Last Name</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->LastName ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('LastName') is-invalid @enderror" 
                                   id="LastName" name="LastName" 
                                   value="{{ old('LastName', $customer->LastName ?? '') }}" placeholder="Enter last name" required>
                            @error('LastName')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="BusinessName" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Business Name</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->BusinessName ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('BusinessName') is-invalid @enderror" 
                                   id="BusinessName" name="BusinessName" 
                                   value="{{ old('BusinessName', $customer->BusinessName ?? '') }}" placeholder="Enter business name" required>
                            @error('BusinessName')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="DisplayName" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Display Name</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->DisplayName ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('DisplayName') is-invalid @enderror" 
                                   id="DisplayName" name="DisplayName" 
                                   value="{{ old('DisplayName', $customer->DisplayName ?? '') }}" placeholder="Display name">
                            <small class="text-muted">This will be used to display the customer name (Optional)</small>
                            @error('DisplayName')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        {{-- SECTION 2: Contact Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-phone"></i> Contact Information
            </h3>
            
            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="Email" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Email</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->Email ?? '-' }}</div>
                        @else
                            <input type="email" class="form-control @error('Email') is-invalid @enderror" 
                                   id="Email" name="Email" 
                                   value="{{ old('Email', $customer->Email ?? '') }}" placeholder="customer@example.com" required>
                            @error('Email')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Mobile" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Mobile</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->Mobile ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('Mobile') is-invalid @enderror" 
                                   id="Mobile" name="Mobile" 
                                   value="{{ old('Mobile', $customer->Mobile ?? '') }}" placeholder="3001234567" required>
                            @error('Mobile')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Phone" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Phone</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->Phone ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('Phone') is-invalid @enderror" 
                                   id="Phone" name="Phone" 
                                   value="{{ old('Phone', $customer->Phone ?? '') }}" placeholder="042 1234567">
                            <small class="text-muted">(Optional)</small>
                            @error('Phone')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="Website" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Website</label>
                        @if($isReadonly)
                            <div class="detail-value">
                                @if(isset($customer->Website) && $customer->Website)
                                    <a href="{{ $customer->Website }}" target="_blank">{{ $customer->Website }}</a>
                                @else
                                    -
                                @endif
                            </div>
                        @else
                            <input type="url" class="form-control @error('Website') is-invalid @enderror" 
                                   id="Website" name="Website" 
                                   value="{{ old('Website', $customer->Website ?? '') }}" placeholder="https://www.example.com">
                            <small class="text-muted">(Optional)</small>
                            @error('Website')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        {{-- SECTION 3: Address Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-map-marker-alt"></i> Address Information
            </h3>
            
            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="Address" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Street Address</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->Address ?? '-' }}</div>
                        @else
                            <textarea class="form-control @error('Address') is-invalid @enderror" 
                                      id="Address" name="Address" rows="2" 
                                      placeholder="Enter street address" required>{{ old('Address', $customer->Address ?? '') }}</textarea>
                            @error('Address')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="City" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">City</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->City ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('City') is-invalid @enderror" 
                                   id="City" name="City" 
                                   value="{{ old('City', $customer->City ?? '') }}" placeholder="City" required>
                            @error('City')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="County" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">State/Province</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->County ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('County') is-invalid @enderror" 
                                   id="County" name="County" 
                                   value="{{ old('County', $customer->County ?? '') }}" placeholder="State/Province" required>
                            @error('County')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="PostalCode" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Postal Code</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->PostalCode ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('PostalCode') is-invalid @enderror" 
                                   id="PostalCode" name="PostalCode" 
                                   value="{{ old('PostalCode', $customer->PostalCode ?? '') }}" placeholder="54000" required>
                            @error('PostalCode')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="Country" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Country</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->Country ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('Country') is-invalid @enderror" 
                                   id="Country" name="Country" 
                                   value="{{ old('Country', $customer->Country ?? 'Pakistan') }}" placeholder="Pakistan" required>
                            @error('Country')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        {{-- SECTION 4: Billing Address --}}
        @if(!$isReadonly)
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-file-invoice"></i> Billing Address
                </h3>
                
                <div class="row">
                    <div class="col-md-12">
                        <label class="checkbox-label">
                            <input type="checkbox" name="SameAsBilling" id="SameAsBilling" value="1" 
                                   {{ old('SameAsBilling', $customer->SameAsBilling ?? '0') == '1' ? 'checked' : '' }}>
                            <span>Same as Primary Address</span>
                        </label>
                    </div>
                </div>
                
                <div id="billingAddressFields" style="{{ old('SameAsBilling', $customer->SameAsBilling ?? '0') == '1' ? 'display:none' : '' }}">
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="BillingAddress" class="form-label required-field">Billing Street Address</label>
                                <textarea class="form-control @error('BillingAddress') is-invalid @enderror" 
                                          id="BillingAddress" name="BillingAddress" rows="2" 
                                          placeholder="Enter billing address" required>{{ old('BillingAddress', $customer->BillingAddress ?? '') }}</textarea>
                                @error('BillingAddress')
                                    <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="BillingCity" class="form-label required-field">Billing City</label>
                                <input type="text" class="form-control @error('BillingCity') is-invalid @enderror" 
                                       id="BillingCity" name="BillingCity" 
                                       value="{{ old('BillingCity', $customer->BillingCity ?? '') }}" placeholder="City" required>
                                @error('BillingCity')
                                    <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="BillingCounty" class="form-label required-field">Billing State/Province</label>
                                <input type="text" class="form-control @error('BillingCounty') is-invalid @enderror" 
                                       id="BillingCounty" name="BillingCounty" 
                                       value="{{ old('BillingCounty', $customer->BillingCounty ?? '') }}" placeholder="State/Province" required>
                                @error('BillingCounty')
                                    <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="BillingPostalCode" class="form-label required-field">Billing Postal Code</label>
                                <input type="text" class="form-control @error('BillingPostalCode') is-invalid @enderror" 
                                       id="BillingPostalCode" name="BillingPostalCode" 
                                       value="{{ old('BillingPostalCode', $customer->BillingPostalCode ?? '') }}" placeholder="54000" required>
                                @error('BillingPostalCode')
                                    <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="BillingCountry" class="form-label required-field">Billing Country</label>
                                <input type="text" class="form-control @error('BillingCountry') is-invalid @enderror" 
                                       id="BillingCountry" name="BillingCountry" 
                                       value="{{ old('BillingCountry', $customer->BillingCountry ?? 'Pakistan') }}" placeholder="Pakistan" required>
                                @error('BillingCountry')
                                    <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- SECTION 5: Financial Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-calculator"></i> Financial & Tax Information
            </h3>
            
            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="NTN" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">NTN Number</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->NTN ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('NTN') is-invalid @enderror" 
                                   id="NTN" name="NTN" 
                                   value="{{ old('NTN', $customer->NTN ?? '') }}" placeholder="Enter NTN" required>
                            @error('NTN')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="SalesTaxNumber" class="{{ $isReadonly ? 'detail-label' : 'form-label' }}">Sales Tax Number</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->SalesTaxNumber ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('SalesTaxNumber') is-invalid @enderror" 
                                   id="SalesTaxNumber" name="SalesTaxNumber" 
                                   value="{{ old('SalesTaxNumber', $customer->SalesTaxNumber ?? '') }}" placeholder="Enter STN">
                            <small class="text-muted">(Optional)</small>
                            @error('SalesTaxNumber')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="CNIC" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">CNIC</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->CNIC ?? '-' }}</div>
                        @else
                            <input type="text" class="form-control @error('CNIC') is-invalid @enderror" 
                                   id="CNIC" name="CNIC" 
                                   value="{{ old('CNIC', $customer->CNIC ?? '') }}" placeholder="12345-1234567-1" required>
                            @error('CNIC')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="CreditLimit" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Credit Limit (PKR)</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ number_format($customer->CreditLimit ?? 0, 2) }}</div>
                        @else
                            <input type="number" step="0.01" class="form-control @error('CreditLimit') is-invalid @enderror" 
                                   id="CreditLimit" name="CreditLimit" 
                                   value="{{ old('CreditLimit', $customer->CreditLimit ?? '0.00') }}" placeholder="0.00" required>
                            @error('CreditLimit')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="PaymentTerms" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Payment Terms</label>
                        @if($isReadonly)
                            <div class="detail-value">{{ $customer->PaymentTerms ?? '-' }}</div>
                        @else
                            <select name="PaymentTerms" id="PaymentTerms" class="form-select @error('PaymentTerms') is-invalid @enderror" required>
                                <option value="">Select</option>
                                <option value="Cash" {{ old('PaymentTerms', $customer->PaymentTerms ?? '') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Net 15" {{ old('PaymentTerms', $customer->PaymentTerms ?? '') == 'Net 15' ? 'selected' : '' }}>Net 15 Days</option>
                                <option value="Net 30" {{ old('PaymentTerms', $customer->PaymentTerms ?? '') == 'Net 30' ? 'selected' : '' }}>Net 30 Days</option>
                                <option value="Net 45" {{ old('PaymentTerms', $customer->PaymentTerms ?? '') == 'Net 45' ? 'selected' : '' }}>Net 45 Days</option>
                                <option value="Net 60" {{ old('PaymentTerms', $customer->PaymentTerms ?? '') == 'Net 60' ? 'selected' : '' }}>Net 60 Days</option>
                            </select>
                            @error('PaymentTerms')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="IsFiler" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Tax Filer Status</label>
                    @if($isReadonly)
                        <div class="detail-value">
                            @if(isset($customer->IsFiler) && $customer->IsFiler)
                                <i class="fas fa-check-circle text-success"></i> Tax Filer
                            @else
                                <i class="fas fa-times-circle text-danger"></i> Non-Filer
                            @endif
                        </div>
                    @else
                        <div class="checkbox-label">
                            <input type="hidden" name="IsFiler" value="0">
                            <input type="checkbox" name="IsFiler" id="IsFiler" value="1" 
                                   {{ old('IsFiler', $customer->IsFiler ?? '1') == '1' ? 'checked' : '' }}>
                            <span>Is Tax Filer?</span>
                        </div>
                        @error('IsFiler')
                            <div class="invalid-feedback d-block"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                        @enderror
                    @endif
                </div>
            </div>
        </div>
        
        {{-- SECTION 6: Additional Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-sticky-note"></i> Additional Information
            </h3>
            
            <div class="row {{ $isReadonly ? 'mb-3' : '' }}">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="Notes" class="{{ $isReadonly ? 'detail-label' : 'form-label required-field' }}">Notes</label>
                        @if($isReadonly)
                            <div class="detail-value" style="min-height: 80px;">{{ $customer->Notes ?? 'No additional notes.' }}</div>
                        @else
                            <textarea class="form-control @error('Notes') is-invalid @enderror" 
                                      id="Notes" name="Notes" rows="3" 
                                      placeholder="Any additional notes about the customer" required>{{ old('Notes', $customer->Notes ?? '') }}</textarea>
                            @error('Notes')
                                <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
            
            @if(!$isReadonly)
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="Status" class="form-label required-field">Status</label>
                        <select name="Status" id="Status" class="form-select @error('Status') is-invalid @enderror" required>
                            <option value="1" {{ old('Status', $customer->Status ?? '1') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('Status', $customer->Status ?? '') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('Status')
                            <div class="invalid-feedback"><i class="fas fa-exclamation-circle error-icon"></i>{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        {{-- Form Actions --}}
        <div class="action-buttons-bottom">
            @if($isReadonly)
                <div class="last-updated-info">
                    <i class="fas fa-clock"></i> Created: {{ isset($customer->CreatedOn) ? $customer->CreatedOn->format('M d, Y h:i A') : 'N/A' }}
                    <span class="mx-2">|</span>
                    <i class="fas fa-pen"></i> Updated: {{ isset($customer->UpdatedOn) ? $customer->UpdatedOn->format('M d, Y h:i A') : 'Never' }}
                </div>
                <div>
                    <a href="{{ route('customers.index') }}" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            @elseif($isEdit)
                <div class="last-updated-info">
                    <i class="fas fa-info-circle"></i> Last updated: 
                    {{ isset($customer->UpdatedOn) ? $customer->UpdatedOn->format('M d, Y h:i A') : 'Never' }}
                </div>
                <div>
                    <button type="button" class="btn-cancel" onclick="window.location.href='{{ route('customers.index') }}'">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-update">
                        <i class="fas fa-save"></i> Update Customer
                    </button>
                </div>
            @else
                <button type="button" class="btn-cancel" onclick="window.location.href='{{ route('customers.index') }}'">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Customer
                </button>
            @endif
        </div>
        
    @if(!$isReadonly)
    </form>
    @endif
</div>

@endsection

@section('scripts')
<script>
    // Only run form scripts if not in read-only mode
    @if(!$isReadonly)
    
    // Auto-generate Display Name
    function updateDisplayName() {
        const displayNameField = document.getElementById('DisplayName');
        if (!displayNameField.value || displayNameField.value === '') {
            const businessName = document.getElementById('BusinessName').value.trim();
            const firstName = document.getElementById('FirstName').value.trim();
            const lastName = document.getElementById('LastName').value.trim();
            
            if (businessName) {
                displayNameField.value = businessName;
            } else if (firstName || lastName) {
                displayNameField.value = (firstName + ' ' + lastName).trim();
            }
        }
    }
    
    document.getElementById('BusinessName').addEventListener('blur', updateDisplayName);
    document.getElementById('FirstName').addEventListener('blur', updateDisplayName);
    document.getElementById('LastName').addEventListener('blur', updateDisplayName);
    
    // Toggle billing address fields
    function toggleBillingFields() {
        const checkbox = document.getElementById('SameAsBilling');
        if (!checkbox) return; // Guard clause
        
        const billingFields = document.getElementById('billingAddressFields');
        const billingInputs = billingFields.querySelectorAll('input, textarea');
        
        if (checkbox.checked) {
            billingFields.style.display = 'none';
            // Remove required attribute and clear validation when hidden
            billingInputs.forEach(input => {
                input.removeAttribute('required');
                input.classList.remove('is-invalid');
            });
            
            // Copy values from primary address to billing address
            const addr = document.getElementById('Address').value;
            const city = document.getElementById('City').value;
            const county = document.getElementById('County').value;
            const postal = document.getElementById('PostalCode').value;
            const country = document.getElementById('Country').value;
            
            if(document.getElementById('BillingAddress')) document.getElementById('BillingAddress').value = addr;
            if(document.getElementById('BillingCity')) document.getElementById('BillingCity').value = city;
            if(document.getElementById('BillingCounty')) document.getElementById('BillingCounty').value = county;
            if(document.getElementById('BillingPostalCode')) document.getElementById('BillingPostalCode').value = postal;
            if(document.getElementById('BillingCountry')) document.getElementById('BillingCountry').value = country;
            
        } else {
            billingFields.style.display = 'block';
            // Add back required attribute when visible
            billingInputs.forEach(input => {
                if (input.id !== 'BillingAddress' && input.tagName !== 'TEXTAREA') {
                    if (input.type !== 'hidden') {
                        input.setAttribute('required', 'required');
                    }
                } else if (input.tagName === 'TEXTAREA') {
                    input.setAttribute('required', 'required');
                }
            });
        }
    }
    
    // Initialize billing fields state on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleBillingFields();
    });
    
    // Add event listener for checkbox change
    const sameAsBillingCheckbox = document.getElementById('SameAsBilling');
    if (sameAsBillingCheckbox) {
        sameAsBillingCheckbox.addEventListener('change', toggleBillingFields);
    }
    
    // Scroll to first error if validation fails
    window.addEventListener('load', function() {
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    });

    /**
     * AUTO-SAVE / DRAFT FEATURE (LocalStorage)
     * Persists form data across page refreshes (F5)
     */
    const formId = 'customerForm';
    const formMode = '{{ $mode }}';
    // Use customer ID for edits, or 'new' for create mode
    const customerId = '{{ $customer->ID ?? "new" }}';
    const storagePrefix = 'customer_draft_' + formMode + '_' + customerId + '_';

    if (document.getElementById(formId)) {
        const form = document.getElementById(formId);
        const inputs = form.querySelectorAll('input, textarea, select');

        // 1. Restore data on load
        // Only restore if there are NO validation errors (server-side old() takes precedence)
        // If the user just refreshed (F5), there are no errors, so we restore.
        if (!document.querySelector('.alert-danger')) {
            inputs.forEach(input => {
                // Skip hidden inputs (like _token, _method) and passwords
                if (input.type === 'hidden' || input.type === 'password') return;
                
                const key = storagePrefix + input.name;
                const storedValue = localStorage.getItem(key);
                
                if (storedValue !== null) {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        const shouldBeChecked = (storedValue === 'true');
                        if (input.checked !== shouldBeChecked) {
                            input.checked = shouldBeChecked;
                            // Trigger change for dependencies (like billing address toggle)
                            input.dispatchEvent(new Event('change'));
                        }
                    } else {
                        if (input.value !== storedValue) {
                            input.value = storedValue;
                            // Trigger input event for any listeners
                            input.dispatchEvent(new Event('input'));
                        }
                    }
                }
            });
        }

        // 2. Save data on change
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

        // 3. Clear data on submit
        form.addEventListener('submit', function() {
            // We clear storage so the next visit starts fresh
            // If submission fails, Laravel redirects back with input, 
            // and our 'if (!document.querySelector('.alert-danger'))' check prevents
            // overwriting the server's repopulated data with the (now deleted) local storage.
            // Wait! If we delete it now, and submission fails, we rely ENTIRELY on Laravel old().
            // That is correct behavior.
            inputs.forEach(input => {
                localStorage.removeItem(storagePrefix + input.name);
            });
        });
    }
    
    @endif
</script>
@endsection
