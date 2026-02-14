{{--
    Unified Customer Form Component
    Path: resources/views/customers/form.blade.php
    
    Modes: 'create', 'edit', 'view'
    
    Accepts:
    - $mode (string)
    - $customer (Customer model instance or null)
--}}

@props(['mode' => 'create', 'customer' => null])

@extends('layouts.master')

@section('styles')
{{-- Font Awesome for icons --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Form Mode Badges & Styling */
    .form-mode-badge {
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 600;
        margin-bottom: 20px;
        display: inline-block;
    }

    .form-mode-badge.create { background: linear-gradient(135deg, #6f42c1 0%, #9b59b6 100%); }
    .form-mode-badge.edit { background: linear-gradient(135deg, #fd7e14 0%, #ff9f40 100%); }
    .form-mode-badge.view { background: linear-gradient(135deg, #17a2b8 0%, #36b9cc 100%); }

    .readonly-field {
        background-color: #f8f9fa !important;
        cursor: not-allowed;
        border-color: #dee2e6;
    }

    .customer-info-badge {
        padding: 15px;
        border-radius: 8px;
        color: white;
        margin-bottom: 25px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .customer-info-badge.create { background: linear-gradient(135deg, #6f42c1 0%, #9b59b6 100%); }
    .customer-info-badge.edit { background: linear-gradient(135deg, #fd7e14 0%, #ff9f40 100%); }
    .customer-info-badge.view { background: linear-gradient(135deg, #17a2b8 0%, #36b9cc 100%); }
    
    .customer-info-badge i {
        opacity: 0.8;
        margin-right: 5px;
    }

    .customer-form-container {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .form-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 20px;
        border-left: 4px solid transparent;
    }
    
    .form-section:hover {
        border-left-color: #6f42c1;
        background: #fdfdfd;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .form-section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 10px;
    }
    
    .form-section-title i {
        color: #6f42c1;
    }
    
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
    
    .action-buttons-bottom {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        margin-top: 35px; /* Align with inputs */
        user-select: none;
    }
</style>
@endsection

@section('content')

{{-- Mode Detection --}}
@php
    $isCreate = ($mode === 'create');
    $isEdit = ($mode === 'edit');
    $isView = ($mode === 'view');
    
    $pageTitle = $isCreate ? 'Add New Customer' : ($isEdit ? 'Edit Customer' : 'Customer Details');
@endphp

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">{{ $pageTitle }}</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
            <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                {{ $isCreate ? 'Add Customer' : ($isEdit ? 'Edit Customer' : 'View Customer') }}
            </li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

{{-- Success Messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Error Messages --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="customer-form-container">

    {{-- Customer Info Badge (Edit & View Modes Only) --}}
    @if($isEdit || $isView)
    <div class="customer-info-badge {{ $mode }}">
        <div class="row align-items-center">
            <div class="col-md-2">
                <strong><i class="fas fa-id-badge"></i> ID:</strong> #{{ $customer->ID }}
            </div>
            <div class="col-md-3">
                <strong><i class="fas fa-hashtag"></i> Account:</strong> {{ $customer->AccountNo ?? 'N/A' }}
            </div>
            <div class="col-md-3">
                <strong><i class="fas fa-wallet"></i> Balance:</strong> PKR {{ number_format($customer->PartyBalance ?? 0, 2) }}
            </div>
            <div class="col-md-2">
                <strong><i class="fas fa-toggle-on"></i> Status:</strong>
                @if(($customer->Status ?? 0) == 1)
                    <span class="badge bg-success">Active</span>
                @else
                    <span class="badge bg-danger">Inactive</span>
                @endif
            </div>
            <div class="col-md-2 text-end">
                @if($isView)
                <a href="{{ route('customers.edit', $customer->ID) }}" class="btn btn-light btn-sm fw-bold" style="color: var(--primary-color);">
                    <i class="fas fa-pencil-alt"></i> Edit
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Form Opening Tag --}}
    @if(!$isView)
    <form action="{{ $isCreate ? route('customers.save_transaction') : route('customers.edit_transaction', $customer->ID) }}" 
          method="POST" 
          id="customerForm">
        @csrf
        @if($isEdit) @method('PUT') @endif
    @else
    <div id="customerViewContainer">
    @endif

        {{-- SECTION 1: Basic Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-user"></i> Basic Information
            </h3>
            
            <div class="row">
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="Title" class="form-label">Title</label>
                        <select name="Title" id="Title" class="form-select {{ $isView ? 'readonly-field' : '' }}" {{ $isView ? 'disabled' : '' }}>
                            <option value="">Select</option>
                            @foreach(['Mr', 'Mrs', 'Ms', 'Dr', 'Prof'] as $title)
                                <option value="{{ $title }}" {{ old('Title', $customer->Title ?? '') == $title ? 'selected' : '' }}>{{ $title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="FirstName" class="form-label {{ !$isView ? 'required-field' : '' }}">First Name</label>
                        <input type="text" 
                               name="FirstName" 
                               id="FirstName" 
                               value="{{ old('FirstName', $customer->FirstName ?? 'Muhammad Kamran') }} Muhammad Kamran" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }} @error('FirstName') is-invalid @enderror" 
                               placeholder="{{ $isView ? '' : 'Enter first name' }}" 
                               {{ $isView ? 'readonly disabled' : '' }} 
                               {{ $isCreate || $isEdit ? 'required' : '' }}>
                        @error('FirstName')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="LastName" class="form-label {{ !$isView ? 'required-field' : '' }}">Last Name</label>
                        <input type="text" 
                               name="LastName" 
                               id="LastName" 
                               value="{{ old('LastName', $customer->LastName ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }} @error('LastName') is-invalid @enderror" 
                               placeholder="{{ $isView ? '' : 'Enter last name' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}
                               {{ $isCreate || $isEdit ? 'required' : '' }}>
                         @error('LastName')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="BusinessName" class="form-label">Business Name</label>
                        <input type="text" 
                               name="BusinessName" 
                               id="BusinessName" 
                               value="{{ old('BusinessName', $customer->BusinessName ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }} @error('BusinessName') is-invalid @enderror" 
                               placeholder="{{ $isView ? '' : 'Enter business name' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="DisplayName" class="form-label">Display Name</label>
                        <input type="text" 
                               name="DisplayName" 
                               id="DisplayName" 
                               value="{{ old('DisplayName', $customer->DisplayName ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                               placeholder="{{ $isView ? '' : 'Auto-generated if left empty' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                        @if(!$isView)
                        <small class="text-muted">This will be used to display the customer name</small>
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
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="Email" class="form-label {{ !$isView ? 'required-field' : '' }}">Email</label>
                        <input type="email" 
                               name="Email" 
                               id="Email" 
                               value="{{ old('Email', $customer->Email ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }} @error('Email') is-invalid @enderror" 
                               placeholder="{{ $isView ? '' : 'customer@example.com' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}
                               {{ $isCreate || $isEdit ? 'required' : '' }}>
                        @error('Email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Mobile" class="form-label {{ !$isView ? 'required-field' : '' }}">Mobile</label>
                        <input type="text" 
                               name="Mobile" 
                               id="Mobile" 
                               value="{{ old('Mobile', $customer->Mobile ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }} @error('Mobile') is-invalid @enderror" 
                               placeholder="{{ $isView ? '' : '3001234567' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}
                               {{ $isCreate || $isEdit ? 'required' : '' }}>
                        @error('Mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Phone" class="form-label">Phone</label>
                        <input type="text" 
                               name="Phone" 
                               id="Phone" 
                               value="{{ old('Phone', $customer->Phone ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                               placeholder="{{ $isView ? '' : '042 1234567' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="Website" class="form-label">Website</label>
                        <input type="url" 
                               name="Website" 
                               id="Website" 
                               value="{{ old('Website', $customer->Website ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                               placeholder="{{ $isView ? '' : 'https://www.example.com' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 3: Address Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-map-marker-alt"></i> Address Information
            </h3>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="Address" class="form-label">Street Address</label>
                        <textarea name="Address" 
                                  id="Address" 
                                  rows="2" 
                                  class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                                  placeholder="{{ $isView ? '' : 'Enter street address' }}" 
                                  {{ $isView ? 'readonly disabled' : '' }}>{{ old('Address', $customer->Address ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="City" class="form-label">City</label>
                        <input type="text" 
                               name="City" 
                               id="City" 
                               value="{{ old('City', $customer->City ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                               placeholder="{{ $isView ? '' : 'City' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="County" class="form-label">State/Province</label>
                        <input type="text" 
                               name="County" 
                               id="County" 
                               value="{{ old('County', $customer->County ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                               placeholder="{{ $isView ? '' : 'State/Province' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="PostalCode" class="form-label">Postal Code</label>
                        <input type="text" 
                               name="PostalCode" 
                               id="PostalCode" 
                               value="{{ old('PostalCode', $customer->PostalCode ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                               placeholder="{{ $isView ? '' : '54000' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="Country" class="form-label">Country</label>
                        <input type="text" 
                               name="Country" 
                               id="Country" 
                               value="{{ old('Country', $customer->Country ?? 'Pakistan') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                               placeholder="{{ $isView ? '' : 'Pakistan' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 4: Financial & Tax Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-calculator"></i> Financial & Tax Information
            </h3>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="NTN" class="form-label">NTN Number</label>
                        <input type="text" 
                               name="NTN" 
                               id="NTN" 
                               value="{{ old('NTN', $customer->NTN ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }} @error('NTN') is-invalid @enderror" 
                               placeholder="{{ $isView ? '' : 'Enter NTN' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                        @error('NTN')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="CNIC" class="form-label">CNIC</label>
                        <input type="text" 
                               name="CNIC" 
                               id="CNIC" 
                               value="{{ old('CNIC', $customer->CNIC ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }} @error('CNIC') is-invalid @enderror" 
                               placeholder="{{ $isView ? '' : '12345-1234567-1' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                        @error('CNIC')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="CreditLimit" class="form-label">Credit Limit (PKR)</label>
                        <input type="number" 
                               step="0.01" 
                               name="CreditLimit" 
                               id="CreditLimit" 
                               value="{{ old('CreditLimit', $customer->CreditLimit ?? '0.00') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                               placeholder="{{ $isView ? '' : '0.00' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="SalesTaxNumber" class="form-label">Sales Tax Number</label>
                        <input type="text" 
                               name="SalesTaxNumber" 
                               id="SalesTaxNumber" 
                               value="{{ old('SalesTaxNumber', $customer->SalesTaxNumber ?? '') }}" 
                               class="form-control {{ $isView ? 'readonly-field' : '' }} @error('SalesTaxNumber') is-invalid @enderror" 
                               placeholder="{{ $isView ? '' : 'Enter STN' }}" 
                               {{ $isView ? 'readonly disabled' : '' }}>
                        @error('SalesTaxNumber')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="PaymentTerms" class="form-label">Payment Terms</label>
                        <select name="PaymentTerms" id="PaymentTerms" class="form-select {{ $isView ? 'readonly-field' : '' }}" {{ $isView ? 'disabled' : '' }}>
                            <option value="">Select</option>
                            @foreach(['Cash', 'Net 15', 'Net 30', 'Net 45', 'Net 60', 'Due on Receipt', 'COD'] as $term)
                                <option value="{{ $term }}" {{ old('PaymentTerms', $customer->PaymentTerms ?? '') == $term ? 'selected' : '' }}>{{ $term }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="checkbox-label">
                        <input type="hidden" name="IsFiler" value="0">
                        <input type="checkbox" 
                               name="IsFiler" 
                               id="IsFiler" 
                               value="1" 
                               {{ old('IsFiler', $customer->IsFiler ?? true) ? 'checked' : '' }} 
                               {{ $isView ? 'disabled' : '' }}>
                        <span>Is Tax Filer?</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- SECTION 5: Additional Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-sticky-note"></i> Additional Information
            </h3>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="Notes" class="form-label">Notes</label>
                        <textarea name="Notes" 
                                  id="Notes" 
                                  rows="3" 
                                  class="form-control {{ $isView ? 'readonly-field' : '' }}" 
                                  placeholder="{{ $isView ? '' : 'Any additional notes' }}" 
                                  {{ $isView ? 'readonly disabled' : '' }}>{{ old('Notes', $customer->Notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="Status" class="form-label">Status</label>
                        <select name="Status" id="Status" class="form-select {{ $isView ? 'readonly-field' : '' }}" {{ $isView ? 'disabled' : '' }}>
                            <option value="1" {{ old('Status', $customer->Status ?? '1') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('Status', $customer->Status ?? '1') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Conditional Buttons --}}
        <div class="action-buttons-bottom">
            @if($isView)
                <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            @else
                <button type="button" class="btn btn-secondary" onclick="window.location='{{ route('customers.index') }}'">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ $isCreate ? 'Save Customer' : 'Update Customer' }}
                </button>
            @endif
        </div>

    {{-- Form Closing Tag --}}
    @if(!$isView)
    </form>
    @else
    </div>
    @endif

</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mode = '{{ $mode }}';
    
    // Auto-generate Display Name (only for create/edit modes)
    if (mode !== 'view') {
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
        
        document.getElementById('BusinessName')?.addEventListener('blur', updateDisplayName);
        document.getElementById('FirstName')?.addEventListener('blur', updateDisplayName);
        document.getElementById('LastName')?.addEventListener('blur', updateDisplayName);
    }
    
    // Form validation before submit
    const form = document.getElementById('customerForm');
    if (form && mode !== 'view') {
        form.addEventListener('submit', function(e) {
            const firstName = document.getElementById('FirstName').value.trim();
            const email = document.getElementById('Email').value.trim();
            const mobile = document.getElementById('Mobile').value.trim();
            
            if (!firstName || !email || !mobile) {
                e.preventDefault();
                alert('Please fill in all required fields (First Name, Email, Mobile)');
                return false;
            }
        });
    }
});
</script>
@endsection
