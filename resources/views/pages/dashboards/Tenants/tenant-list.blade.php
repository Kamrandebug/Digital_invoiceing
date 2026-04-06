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
    .tenant-card-meta {
        min-height: 2.75rem;
    }
    .tenant-card-meta .tenant-sale-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 500;
        letter-spacing: normal;
        text-transform: none;
        padding: 0.25rem 0.55rem;
        border-radius: 0.35rem;
        border: 1px solid transparent;
        background: #f8f9fa;
        color: #495057;
    }
    .tenant-card-meta .tenant-sale-badge i {
        font-size: 0.9em;
        opacity: 0.65;
    }
    .tenant-card-meta .tenant-sale-badge.tenant-sale--paid {
        background: #f3f9f5;
        color: #2d6a4f;
        border-color: #cfe8dc;
    }
    .tenant-card-meta .tenant-sale-badge.tenant-sale--trial {
        background: #fdfaf3;
        color: #7c5f2a;
        border-color: #ebe0cc;
    }
    .tenant-card-meta .tenant-sale-badge.tenant-sale--invoiced {
        background: #f5f6fa;
        color: #4a5568;
        border-color: #dde1ea;
    }
    .tenant-card-meta .tenant-sale-badge.tenant-sale--pending {
        background: #f8f9fa;
        color: #5c636a;
        border-color: #dee2e6;
    }
    .tenant-card-meta .tenant-sale-badge.tenant-sale--unpaid {
        background: #fcf6f6;
        color: #8b4046;
        border-color: #e9d6d8;
    }
    .tenant-card-meta .tenant-sale-badge.tenant-sale--default {
        background: #f7f6f9;
        color: #5c5470;
        border-color: #e2dfe8;
    }
    .tenant-card-meta .tenant-sale-badge.tenant-sale--empty {
        background: #fafbfc;
        color: #adb5bd;
        border: 1px dashed #dee2e6;
        font-weight: 400;
    }
    .tenant-card-meta .tenant-expiry {
        font-size: 0.75rem;
        line-height: 1.4;
        color: #6c757d;
    }
    .tenant-card-meta .tenant-expiry.tenant-expiry--past {
        color: #a94442;
        font-weight: 500;
    }
    .tenant-card-meta .tenant-expiry.tenant-expiry--soon {
        color: #8a6d3b;
        font-weight: 500;
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
                                    @php
                                        $saleStatus = data_get($tenant, 'SaleStatus') ?? data_get($tenant, 'salestatus');
                                        $expiry = data_get($tenant, 'ExpiryDate') ?? data_get($tenant, 'expirydate');
                                        if ($expiry && ! $expiry instanceof \Carbon\Carbon) {
                                            try {
                                                $expiry = \Carbon\Carbon::parse($expiry);
                                            } catch (\Throwable $e) {
                                                $expiry = null;
                                            }
                                        }
                                        $expirySoon = $expiry && $expiry->isFuture() && $expiry->lte(now()->copy()->addDays(30));

                                        $saleBadgeClass = 'tenant-sale--default';
                                        $saleIcon = 'ri-price-tag-3-line';
                                        if (filled($saleStatus)) {
                                            $compact = strtolower(preg_replace('/[\s._-]+/', '', (string) $saleStatus));
                                            if (str_contains($compact, 'unpaid') || $compact === 'overdue') {
                                                $saleBadgeClass = 'tenant-sale--unpaid';
                                                $saleIcon = 'ri-error-warning-line';
                                            } elseif (in_array($compact, ['paid', 'fullypaid', 'complete', 'completed'], true)) {
                                                $saleBadgeClass = 'tenant-sale--paid';
                                                $saleIcon = 'ri-checkbox-circle-line';
                                            } elseif (in_array($compact, ['trial', 'trail'], true) || str_starts_with($compact, 'trial') || str_starts_with($compact, 'trail')) {
                                                $saleBadgeClass = 'tenant-sale--trial';
                                                $saleIcon = 'ri-timer-flash-line';
                                            } elseif (str_contains($compact, 'invoice')) {
                                                $saleBadgeClass = 'tenant-sale--invoiced';
                                                $saleIcon = 'ri-file-list-3-line';
                                            } elseif (in_array($compact, ['pending', 'processing', 'draft', 'due'], true) || str_contains($compact, 'pending')) {
                                                $saleBadgeClass = 'tenant-sale--pending';
                                                $saleIcon = 'ri-loader-4-line';
                                            }
                                        }
                                    @endphp
                                    <div class="tenant-card-meta d-flex flex-column align-items-center gap-2 w-100 mb-3">
                                        @if(filled($saleStatus))
                                            <span class="tenant-sale-badge {{ $saleBadgeClass }}">
                                                <i class="{{ $saleIcon }}" aria-hidden="true"></i>
                                                <span>{{ $saleStatus }}</span>
                                            </span>
                                        @else
                                            <span class="tenant-sale-badge tenant-sale--empty">
                                                <span>No status</span>
                                            </span>
                                        @endif
                                        @if($expiry)
                                            <div class="tenant-expiry text-muted w-100 px-1 {{ $expiry->isPast() ? 'tenant-expiry--past' : ($expirySoon ? 'tenant-expiry--soon' : '') }}">
                                                <i class="ri-calendar-event-line align-middle me-1"></i>
                                                @if($expiry->isPast())
                                                    Expired {{ $expiry->format('M j, Y') }}
                                                @else
                                                    Expires {{ $expiry->format('M j, Y') }}
                                                @endif
                                            </div>
                                        @else
                                            <div class="tenant-expiry text-muted opacity-50 w-100 px-1 small">
                                                <i class="ri-calendar-line align-middle me-1"></i>—
                                            </div>
                                        @endif
                                    </div>
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
