{{-- Tenant List — Select a tenant (stored in session for later use) --}}

@extends('layouts.master')

@section('styles')
<style>
    /* Full-width layout when sidebar is hidden on tenant selection page */
    .tenants-selection-page .main-content.app-content {
        margin-inline-start: 0 !important;
    }
    .tenants-selection-page .app-header {
        padding-inline-start: 0 !important;
    }
    .tenants-selection-page .sidemenu-toggle {
        display: none !important;
    }
</style>
@endsection

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Select Tenant</h1>
        <button type="button"
                class="btn btn-sm btn-primary ms-auto"
                data-bs-toggle="modal"
                data-bs-target="#addTenantModal">
            <i class="ri-add-line align-middle me-1"></i> Add Tenant
        </button>
    </div>
</div>

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

@if($errors->any())
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle"></i>
    @foreach ($errors->all() as $error) {{ $error }} @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card custom-card">
            <div class="card-body">
                @if($tenants->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="ri-building-line fs-1"></i>
                        <p class="mb-0 mt-2">No tenants assigned to you.</p>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach($tenants as $tenant)
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center p-3">
                                    <div class="tenant-list-image mb-2">
                                        @if(!empty($tenant->ImageUrl))
                                            @php $imgSrc = str_starts_with($tenant->ImageUrl, 'http') ? $tenant->ImageUrl : asset($tenant->ImageUrl); @endphp
                                            <img src="{{ $imgSrc }}" alt="{{ $tenant->TenantName }}" class="rounded" style="max-height: 120px; width: auto; object-fit: contain;">
                                        @else
                                            <div class="bg-light rounded d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                                <i class="ri-building-line fs-1 text-secondary"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <h6 class="card-title fw-semibold mb-2">{{ $tenant->TenantName ?? 'Unnamed' }}</h6>
                                    <a href="{{ route('tenants.select', $tenant->ID) }}" class="btn btn-sm btn-primary btn-wave waves-light">
                                        <i class="ri-arrow-right-line align-middle me-1"></i> Select
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Tenant Modal -->
<div class="modal fade" id="addTenantModal" tabindex="-1" aria-labelledby="addTenantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTenantModalLabel">Add Tenant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('tenants.add') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tenant-business-name" class="form-label">Business Name</label>
                        <input type="text"
                               name="BusinessName"
                               id="tenant-business-name"
                               class="form-control"
                               value="{{ old('BusinessName') }}"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="tenant-image" class="form-label">Image (URL or path)</label>
                        <input type="text"
                               name="Image"
                               id="tenant-image"
                               class="form-control"
                               value="{{ old('Image') }}"
                               placeholder="https://example.com/logo.png or uploads/logo.png">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
