{{--
    Customer View Details Page
    
    Path: resources/views/pages/dashboards/customers-show.blade.php
    URL: /customers/{id}
    
    Displays customer details in read-only mode
--}}

@extends('layouts.master')

@section('styles')
{{-- Font Awesome for icons --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        background: linear-gradient(135deg, #17a2b8 0%, #36b9cc 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.2);
    }
    
    .customer-info-badge .row > div {
        padding: 10px 0;
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
        color: #17a2b8;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-section-title i {
        color: #17a2b8;
    }
    
    .action-buttons-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
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
    
    .btn-back {
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
    
    .btn-back:hover {
        background: #5a6268;
        color: white;
    }

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
    
    .last-updated-info {
        color: #6c757d;
        font-size: 14px;
    }
</style>
@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Customer Details</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
            <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
            <li class="breadcrumb-item active" aria-current="page">View Customer</li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

<!-- Customer Info Badge -->
<div class="customer-info-badge">
    <div class="row">
        <div class="col-md-3">
            <strong><i class="fas fa-id-badge"></i> Customer ID:</strong> #{{ $customer->ID }}
        </div>
        <div class="col-md-3">
            <strong><i class="fas fa-hashtag"></i> Account No:</strong> {{ $customer->AccountNo ?? 'N/A' }}
        </div>
        <div class="col-md-3">
            <strong><i class="fas fa-wallet"></i> Balance:</strong> PKR {{ number_format($customer->PartyBalance, 2) }}
        </div>
        <div class="col-md-3">
            <strong><i class="fas fa-toggle-on"></i> Status:</strong> 
            @if($customer->Status == 1)
                <span class="badge bg-success" style="color: white; background-color: #28a745 !important;">Active</span>
            @else
                <span class="badge bg-danger" style="color: white; background-color: #dc3545 !important;">Inactive</span>
            @endif
        </div>
    </div>
</div>

<!-- Customer Details Container -->
<div class="customer-form-container">
    <div class="form-header">
        <h1><i class="fas fa-user-circle"></i> Customer Profile</h1>
    </div>

    {{-- SECTION 1: Basic Information --}}
    <div class="form-section">
        <h3 class="form-section-title">
            <i class="fas fa-user"></i> Basic Information
        </h3>
        
        <div class="row mb-3">
            <div class="col-md-2">
                <span class="detail-label">Title</span>
                <div class="detail-value">{{ $customer->Title ?? '-' }}</div>
            </div>
            
            <div class="col-md-5">
                <span class="detail-label">First Name</span>
                <div class="detail-value">{{ $customer->FirstName ?? '-' }}</div>
            </div>
            
            <div class="col-md-5">
                <span class="detail-label">Last Name</span>
                <div class="detail-value">{{ $customer->LastName ?? '-' }}</div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <span class="detail-label">Business Name</span>
                <div class="detail-value">{{ $customer->BusinessName ?? '-' }}</div>
            </div>
            
            <div class="col-md-6">
                <span class="detail-label">Display Name</span>
                <div class="detail-value">{{ $customer->DisplayName ?? '-' }}</div>
            </div>
        </div>
    </div>
    
    {{-- SECTION 2: Contact Information --}}
    <div class="form-section">
        <h3 class="form-section-title">
            <i class="fas fa-phone"></i> Contact Information
        </h3>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <span class="detail-label">Email</span>
                <div class="detail-value">{{ $customer->Email ?? '-' }}</div>
            </div>
            
            <div class="col-md-3">
                <span class="detail-label">Mobile</span>
                <div class="detail-value">{{ $customer->Mobile ?? '-' }}</div>
            </div>
            
            <div class="col-md-3">
                <span class="detail-label">Phone</span>
                <div class="detail-value">{{ $customer->Phone ?? '-' }}</div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-12">
                <span class="detail-label">Website</span>
                <div class="detail-value">
                    @if($customer->Website)
                        <a href="{{ $customer->Website }}" target="_blank">{{ $customer->Website }}</a>
                    @else
                        -
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
        
        <div class="row mb-3">
            <div class="col-md-12">
                <span class="detail-label">Street Address</span>
                <div class="detail-value">{{ $customer->Address ?? '-' }}</div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-4">
                <span class="detail-label">City</span>
                <div class="detail-value">{{ $customer->City ?? '-' }}</div>
            </div>
            
            <div class="col-md-4">
                <span class="detail-label">State/Province</span>
                <div class="detail-value">{{ $customer->County ?? '-' }}</div>
            </div>
            
            <div class="col-md-2">
                <span class="detail-label">Postal Code</span>
                <div class="detail-value">{{ $customer->PostalCode ?? '-' }}</div>
            </div>
            
            <div class="col-md-2">
                <span class="detail-label">Country</span>
                <div class="detail-value">{{ $customer->Country ?? '-' }}</div>
            </div>
        </div>
    </div>
    
    {{-- SECTION 4: Financial Information --}}
    <div class="form-section">
        <h3 class="form-section-title">
            <i class="fas fa-calculator"></i> Financial & Tax Information
        </h3>
        
        <div class="row mb-3">
            <div class="col-md-4">
                <span class="detail-label">NTN Number</span>
                <div class="detail-value">{{ $customer->NTN ?? '-' }}</div>
            </div>
            
            <div class="col-md-4">
                <span class="detail-label">CNIC</span>
                <div class="detail-value">{{ $customer->CNIC ?? '-' }}</div>
            </div>
            
            <div class="col-md-4">
                <span class="detail-label">Credit Limit (PKR)</span>
                <div class="detail-value">{{ number_format($customer->CreditLimit, 2) }}</div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <span class="detail-label">Payment Terms</span>
                <div class="detail-value">{{ $customer->PaymentTerms ?? '-' }}</div>
            </div>
            
            <div class="col-md-6">
                <span class="detail-label">Tax Status</span>
                <div class="detail-value">
                    @if($customer->IsFiler)
                        <i class="fas fa-check-circle text-success"></i> Tax Filer
                    @else
                        <i class="fas fa-times-circle text-danger"></i> Non-Filer
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    {{-- SECTION 5: Additional Information --}}
    <div class="form-section">
        <h3 class="form-section-title">
            <i class="fas fa-sticky-note"></i> Additional Information
        </h3>
        
        <div class="row mb-3">
            <div class="col-md-12">
                <span class="detail-label">Notes</span>
                <div class="detail-value" style="min-height: 80px;">{{ $customer->Notes ?? 'No additional notes.' }}</div>
            </div>
        </div>
    </div>
    
    {{-- Action Buttons --}}
    <div class="action-buttons-bottom">
        <div class="last-updated-info">
            <i class="fas fa-clock"></i> Created: {{ $customer->CreatedOn ? $customer->CreatedOn->format('M d, Y h:i A') : 'N/A' }}
            <span class="mx-2">|</span>
            <i class="fas fa-pen"></i> Updated: {{ $customer->UpdatedOn ? $customer->UpdatedOn->format('M d, Y h:i A') : 'Never' }}
        </div>
        <div>
            <a href="{{ route('customers.index') }}" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('customers.edit', $customer->ID ?? 0) }}" class="btn-edit">
                <i class="fas fa-edit"></i> Edit Customer
            </a>
        </div>
    </div>
</div>

@endsection
