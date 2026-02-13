{{--
    Customer Create/Add Page - Matching Existing Design
    
    Path: resources/views/pages/dashboards/customers-create.blade.php
    URL: /customers/create
    
    Uses save_transaction function to add new customers
--}}

@extends('layouts.master')

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
    
    .btn-save {
        background: #6f42c1;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-save:hover {
        background: #5a32a3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
    }
    
    .btn-cancel {
        background: #6c757d;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-cancel:hover {
        background: #5a6268;
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
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
    }
</style>
@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Add New Customer</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
            <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add Customer</li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

<!-- Error Messages -->
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Please fix the following errors:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Customer Create Form -->
<div class="customer-form-container">
    <div class="form-header">
        <h1><i class="fas fa-user-plus"></i> Add New Customer</h1>
    </div>

    <form action="{{ route('customers.save_transaction') }}" method="POST" id="customerForm">
        @csrf
        
        {{-- SECTION 1: Basic Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-user"></i> Basic Information
            </h3>
            
            <div class="row">
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="Title" class="form-label">Title</label>
                        <select name="Title" id="Title" class="form-select" value="{{ old('Title') }}">
                            <option value="">Select</option>
                            <option value="Mr">Mr</option>
                            <option value="Mrs">Mrs</option>
                            <option value="Miss">Miss</option>
                            <option value="Dr">Dr</option>
                            <option value="Prof">Prof</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="FirstName" class="form-label required-field">First Name</label>
                        <input type="text" class="form-control" id="FirstName" name="FirstName" 
                               value="{{ old('FirstName') }}" placeholder="Enter first name" required>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="LastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="LastName" name="LastName" 
                               value="{{ old('LastName') }}" placeholder="Enter last name">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="BusinessName" class="form-label">Business Name</label>
                        <input type="text" class="form-control" id="BusinessName" name="BusinessName" 
                               value="{{ old('BusinessName') }}" placeholder="Enter business name">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="DisplayName" class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="DisplayName" name="DisplayName" 
                               value="{{ old('DisplayName') }}" placeholder="Auto-generated if left empty">
                        <small class="text-muted">This will be used to display the customer name</small>
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
                        <label for="Email" class="form-label required-field">Email</label>
                        <input type="email" class="form-control" id="Email" name="Email" 
                               value="{{ old('Email') }}" placeholder="customer@example.com" required>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Mobile" class="form-label required-field">Mobile</label>
                        <input type="text" class="form-control" id="Mobile" name="Mobile" 
                               value="{{ old('Mobile') }}" placeholder="3001234567" required>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="Phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="Phone" name="Phone" 
                               value="{{ old('Phone') }}" placeholder="042 1234567">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="Website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="Website" name="Website" 
                               value="{{ old('Website') }}" placeholder="https://www.example.com">
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
                        <textarea class="form-control" id="Address" name="Address" rows="2" 
                                  placeholder="Enter street address">{{ old('Address') }}</textarea>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="City" class="form-label">City</label>
                        <input type="text" class="form-control" id="City" name="City" 
                               value="{{ old('City') }}" placeholder="City">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="County" class="form-label">State/Province</label>
                        <input type="text" class="form-control" id="County" name="County" 
                               value="{{ old('County') }}" placeholder="State/Province">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="PostalCode" class="form-label">Postal Code</label>
                        <input type="text" class="form-control" id="PostalCode" name="PostalCode" 
                               value="{{ old('PostalCode') }}" placeholder="54000">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="Country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="Country" name="Country" 
                               value="{{ old('Country', 'Pakistan') }}" placeholder="Pakistan">
                    </div>
                </div>
            </div>
        </div>
        
        {{-- SECTION 4: Financial Information --}}
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="fas fa-calculator"></i> Financial & Tax Information
            </h3>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="NTN" class="form-label">NTN Number</label>
                        <input type="text" class="form-control" id="NTN" name="NTN" 
                               value="{{ old('NTN') }}" placeholder="Enter NTN">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="CNIC" class="form-label">CNIC</label>
                        <input type="text" class="form-control" id="CNIC" name="CNIC" 
                               value="{{ old('CNIC') }}" placeholder="12345-1234567-1">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="CreditLimit" class="form-label">Credit Limit (PKR)</label>
                        <input type="number" step="0.01" class="form-control" id="CreditLimit" name="CreditLimit" 
                               value="{{ old('CreditLimit', '0.00') }}" placeholder="0.00">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="PaymentTerms" class="form-label">Payment Terms</label>
                        <select name="PaymentTerms" id="PaymentTerms" class="form-select">
                            <option value="">Select</option>
                            <option value="Cash" {{ old('PaymentTerms') == 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Net 15" {{ old('PaymentTerms') == 'Net 15' ? 'selected' : '' }}>Net 15 Days</option>
                            <option value="Net 30" {{ old('PaymentTerms') == 'Net 30' ? 'selected' : '' }}>Net 30 Days</option>
                            <option value="Net 45" {{ old('PaymentTerms') == 'Net 45' ? 'selected' : '' }}>Net 45 Days</option>
                            <option value="Net 60" {{ old('PaymentTerms') == 'Net 60' ? 'selected' : '' }}>Net 60 Days</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="checkbox-label">
                        <input type="checkbox" name="IsFiler" id="IsFiler" value="1" 
                               {{ old('IsFiler', true) ? 'checked' : '' }}>
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
                        <textarea class="form-control" id="Notes" name="Notes" rows="3" 
                                  placeholder="Any additional notes about the customer">{{ old('Notes') }}</textarea>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="Status" class="form-label">Status</label>
                        <select name="Status" id="Status" class="form-select">
                            <option value="1" {{ old('Status', '1') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('Status') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Form Actions --}}
        <div class="action-buttons-bottom">
            <button type="button" class="btn-cancel" onclick="window.location.href='{{ route('customers.index') }}'">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Save Customer
            </button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
    // Auto-generate Display Name
    function updateDisplayName() {
        const businessName = document.getElementById('BusinessName').value.trim();
        const firstName = document.getElementById('FirstName').value.trim();
        const lastName = document.getElementById('LastName').value.trim();
        const displayNameField = document.getElementById('DisplayName');
        
        if (!displayNameField.value) {
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
    
    // Form validation
    document.getElementById('customerForm').addEventListener('submit', function(e) {
        const firstName = document.getElementById('FirstName').value.trim();
        const email = document.getElementById('Email').value.trim();
        const mobile = document.getElementById('Mobile').value.trim();
        
        if (!firstName || !email || !mobile) {
            e.preventDefault();
            alert('Please fill in all required fields (First Name, Email, Mobile)');
            return false;
        }
    });
</script>
@endsection
