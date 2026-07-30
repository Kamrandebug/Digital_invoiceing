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
		margin-bottom: 15px;
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
		border: dashed 1px #ccc;
/*		border-left: 0px;*/
		padding: 4px 10px !important;
		border-radius: 5px;
/*		border-top-left-radius: 0px;*/
/*		border-bottom-left-radius: 0px;*/
    }
    
	.tenant-card-meta .tenant-expiry.tenant-expiry--past {
        color: #a94442;
        font-weight: 500;
    }
    
	.tenant-card-meta .tenant-expiry.tenant-expiry--soon {
        color: #8a6d3b;
        font-weight: 500;
    }
	
	.app-header{
		display: none;
	}
	
	.company-card{
		border: 0px !important;
		box-shadow: rgba(0, 0, 0, 0.1) 0px 20px 25px -5px, rgba(0, 0, 0, 0.04) 0px 10px 10px -5px !important;
		border-radius: 25px !important;
		background: #fff;
		padding: 7px;
	}
	
	.company-header{
		background: #fff;
		border-radius: 50px;
    	padding: 10px 10px 10px 30px;
		box-shadow: rgba(0, 0, 0, 0.05) 0px 5px 12px;
		
		.title-tenant{
			display: flex;
			align-items: center;
			font-size: 18px;
		}
	}
	
	.company-title{
		font-size: 18px;
		padding: 15px 0;
		margin: 0;
		font-weight: 600;
		font-family: system-ui;
	}
	
	.company-logo-card{
		width: 120px;
		height: 120px;
		border: solid 1px rgba(0, 0, 0, 0.07);
		background: #fff !important;
/*		box-shadow: rgba(0, 0, 0, 0.07) 0px 0px 7px;*/
	}
	
</style>
@endsection

@section('content')
<div class="row justify-content-center">
	<div class="col-10">
		<div class="page-header-breadcrumb mb-5 company-header">
			<div class="d-flex align-items-center justify-content-between flex-wrap w-100">
				<span class="title-tenant">Select Tenant</span>
				
				<!-- Profile Dropdown (Replaces the Add Tenant button) -->
				<div class="dropdown">
					<button class="btn btn-outline-light dropdown-toggle text-dark d-flex align-items-center gap-2 rounded-pill px-3 py-2" 
							type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false" 
							style="border: 1px solid rgba(0,0,0,0.08); background: #fff; box-shadow: rgba(0, 0, 0, 0.04) 0px 3px 5px;">
						<span class="avatar avatar-xs rounded-circle bg-purple-transparent text-purple" 
                              style="width: 26px; height: 26px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; background: rgba(138, 43, 226, 0.1); color: #8a2be2;">
							{{ substr(auth()->user()->name ?? 'U', 0, 1) }}
						</span>
						<span class="d-none d-sm-inline-block fw-semibold text-muted" style="font-size: 0.875rem;">
							{{ auth()->user()->name }}
						</span>
					</button>
					<ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenuButton" 
						style="border-radius: 12px; min-width: 200px; padding: 0.5rem 0; z-index: 1050;">
						<li class="px-3 py-2 border-bottom text-muted" style="font-size: 0.75rem;">
							<div>Signed in as</div>
							<div class="text-dark fw-semibold text-truncate">{{ auth()->user()->email }}</div>
						</li>
						<li>
							<a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('profile.edit') }}" style="font-size: 0.875rem;">
								<i class="ri-user-line text-muted"></i> View Profile
							</a>
						</li>
						<li><hr class="dropdown-divider my-1" style="border-color: rgba(0,0,0,0.05);"></li>
						<li>
							<form method="POST" action="{{ route('logout') }}">
								@csrf
								<button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" style="font-size: 0.875rem;">
									<i class="ri-logout-box-line"></i> Log Out
								</button>
							</form>
						</li>
					</ul>
				</div>
			</div>
		</div>
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

<div class="row justify-content-center">
    <div class="col-10">
        <div class="">
            <div class="card-body">
                @if($tenants->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="ri-building-line fs-1"></i>
                        <p class="mb-0 mt-2">No tenants assigned to you.</p>
                    </div>
                @else
                    <div class="row g-4 justify-content-center">
                        @foreach($tenants as $tenant)
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
                                $isExpired = $expiry && $expiry->isPast();
                                $expiryWarning = $expiry && $expiry->isFuture() && $expiry->lte(now()->copy()->addDays(7));

                                $saleBadgeClass = 'tenant-sale--default';
                                $saleIcon = 'ri-price-tag-3-line';
                                if (filled($saleStatus)) {
                                    $compact = strtolower(preg_replace('/[\s._-]+/', '', (string) $saleStatus));
                                    if (str_contains($compact, 'unpaid') || $compact === 'overdue') {
                                        $saleBadgeClass = 'ribbon-danger';
                                        $saleIcon = 'ri-error-warning-line';
                                    } elseif (in_array($compact, ['paid', 'fullypaid', 'complete', 'completed'], true)) {
                                        $saleBadgeClass = 'ribbon-success';
                                        $saleIcon = 'ri-checkbox-circle-line';
                                    } elseif (in_array($compact, ['trial', 'trail'], true) || str_starts_with($compact, 'trial') || str_starts_with($compact, 'trail')) {
                                        $saleBadgeClass = 'ribbon-secondary';
                                        $saleIcon = 'ri-timer-flash-line';
                                    } elseif (str_contains($compact, 'invoice')) {
                                        $saleBadgeClass = 'ribbon-primary';
                                        $saleIcon = 'ri-file-list-3-line';
                                    } elseif (in_array($compact, ['pending', 'processing', 'draft', 'due'], true) || str_contains($compact, 'pending')) {
                                        $saleBadgeClass = 'ribbon-orange';
                                        $saleIcon = 'ri-loader-4-line';
                                    }
                                }

                             @endphp
                            <div class="col-md-6 col-lg-4 col-xl-3" @if($isExpired) style="opacity: 0.72;" @endif>
                                <div class="company-card card ribbon-card">
                                    <div class="card-body text-center p-3">
                                        <div class="tenant-list-image mb-2">
                                            @if(!empty($tenant->ImageUrl))
                                                @php $imgSrc = str_starts_with($tenant->ImageUrl, 'http') ? $tenant->ImageUrl : asset($tenant->ImageUrl); @endphp
                                                <img src="{{ $imgSrc }}" alt="{{ $tenant->TenantName }}" class="rounded" style="max-height: 120px; width: auto; object-fit: contain;">
                                            @else
                                                <div class="bg-light rounded d-inline-flex align-items-center justify-content-center company-logo-card">
                                                    <i class="ri-building-line fs-1 text-secondary"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <h6 class="company-title">{{ $tenant->TenantName ?? 'Unnamed' }}</h6>
                                    <div class="tenant-card-meta row g-3 justify-content-center">
                                        @if(filled($saleStatus))
                                            <div class="ribbon ribbon-top-left mt-0 {{ $saleBadgeClass }}">
                                                <span>{{ $saleStatus }}</span>
                                            </div>
                                        @else
                                            <span class="ribbon ribbon-danger mt-0 ribbon-top-left">
                                                <span>No status</span>
                                            </span>
                                        @endif
                                        @if($expiry)
                                            <div class="tenant-expiry text-muted col-md-10 {{ $expiry->isPast() ? 'tenant-expiry--past' : ($expirySoon ? 'tenant-expiry--soon' : '') }}">
                                                <i class="ri-calendar-event-line align-middle me-1"></i>
                                                @if($expiry->isPast())
                                                    Expired {{ $expiry->format('M j, Y') }}
                                                @else
                                                    Expires {{ $expiry->format('M j, Y') }}
                                                @endif
                                            </div>
                                        @else
                                            <div class="tenant-expiry text-muted opacity-50 tenant-expiry text-muted col-md-8">
                                                <i class="ri-calendar-line align-middle me-1"></i>—
                                            </div>
                                        @endif
                                    </div>
                                    @if($isExpired)
                                        <button class="btn btn-secondary label-btn label-end rounded-pill" disabled style="opacity: 0.6; cursor: not-allowed;">
                                            Expired 
                                            <i class="ri-lock-2-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>
                                    @else
                                        <a href="{{ route('tenants.select', $tenant->ID) }}" class="btn btn-success label-btn label-end rounded-pill">
                                            Select 
                                            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
                                        </a>
                                    @endif
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
