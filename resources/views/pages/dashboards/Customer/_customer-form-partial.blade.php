{{--
    ════════════════════════════════════════════════════════════
    MASTER Customer Form Partial
    Handles: create | edit | show (readonly/view)
    ════════════════════════════════════════════════════════════

    USAGE
    ─────
    Standalone page (create):
        @include('pages.dashboards._customer-form-partial', [
            'mode' => 'create'
        ])

    Standalone page (edit):
        @include('pages.dashboards._customer-form-partial', [
            'mode'     => 'edit',
            'customer' => $customer
        ])

    Standalone page (view/readonly):
        @include('pages.dashboards._customer-form-partial', [
            'mode'     => 'show',
            'customer' => $customer
        ])

    Inside a Modal (create):
        Modal body  → @include('pages.dashboards._customer-form-partial', [
                            'mode'    => 'create',
                            'inModal' => true
                        ])
        Modal footer Save button:
            onclick="document.getElementById('cfp_create_new_form').submit()"
    ════════════════════════════════════════════════════════════
--}}

@php
    $mode     = $mode     ?? 'create';
    $customer = $customer ?? null;
    $inModal  = $inModal  ?? false;

    $isCreate   = $mode === 'create';
    $isEdit     = $mode === 'edit';
    $isView     = $mode === 'show';
    $isReadonly = $isView;

    $formAction = $isCreate
        ? route('customers.save_transaction')
        : ($isEdit ? route('customers.edit_transaction', $customer->ID) : '#');

    // Unique ID prefix — prevents conflicts if partial is included multiple times
    $uid = 'cfp_' . $mode . '_' . ($customer->ID ?? 'new');
@endphp

{{-- ── Shared Styles (rendered only once per page) ──────────── --}}
@once
<style>
    ._cfp-section {
        background: var(--cm-bg-body, #f8f9fa);
        border: 1px solid var(--cm-border-color, #dee2e6);
        border-radius: 8px;
        padding: 20px 20px 10px;
        margin-bottom: 16px;
    }
    ._cfp-section-title {
        font-size: 15px;
        font-weight: 600;
        color: #6f42c1;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .show-mode ._cfp-section-title { color: #17a2b8; }

    ._cfp-detail-label {
        font-weight: 600;
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 4px;
        display: block;
    }
    ._cfp-detail-value {
        font-size: 14px;
        font-weight: 500;
        padding: 7px 11px;
        background: var(--cm-input-bg, #fff);
        border: 1px solid var(--cm-border-color, #dee2e6);
        border-radius: 4px;
        min-height: 36px;
        color: var(--cm-text-main, #212529);
        word-break: break-word;
    }
    ._cfp-required::after { content: " *"; color: #dc3545; }

    ._cfp-info-badge {
        padding: 14px 18px;
        border-radius: 8px;
        margin-bottom: 18px;
        color: #fff;
        font-size: 14px;
    }
    ._cfp-info-badge.edit-mode {
        background: linear-gradient(135deg, #6f42c1, #9b59b6);
        box-shadow: 0 4px 12px rgba(111,66,193,.2);
    }
    ._cfp-info-badge.show-mode {
        background: linear-gradient(135deg, #17a2b8, #36b9cc);
        box-shadow: 0 4px 12px rgba(23,162,184,.2);
    }

    ._cfp-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-top: 24px;
        padding-top: 16px;
        border-top: 2px solid var(--cm-border-color, #dee2e6);
    }
    ._cfp-actions.create-mode { justify-content: flex-end; }
    ._cfp-muted { color: #6c757d; font-size: 13px; }

    ._cfp-btn {
        padding: 9px 24px;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all .25s ease;
    }
    ._cfp-btn-primary  { background: #6f42c1; color: #fff; }
    ._cfp-btn-primary:hover  { background: #5a32a3; color: #fff; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(111,66,193,.3); }
    ._cfp-btn-secondary { background: #6c757d; color: #fff; }
    ._cfp-btn-secondary:hover { background: #5a6268; color: #fff; }
</style>
@endonce


{{-- ── Info Badge (edit / show modes only) ────────────────────── --}}
@if(!$isCreate && $customer)
<div class="_cfp-info-badge {{ $isView ? 'show-mode' : 'edit-mode' }}">
    <div class="row align-items-center g-2">
        <div class="col-auto"><strong><i class="fas fa-id-badge"></i> ID:</strong> #{{ $customer->ID }}</div>
        <div class="col-auto"><strong><i class="fas fa-hashtag"></i> Account:</strong> {{ $customer->AccountNo ?? 'N/A' }}</div>
        <div class="col-auto"><strong><i class="fas fa-wallet"></i> Balance:</strong> PKR {{ number_format($customer->PartyBalance ?? 0, 2) }}</div>
        <div class="col-auto">
            <strong><i class="fas fa-toggle-on"></i> Status:</strong>
            <span class="badge {{ $customer->Status == 1 ? 'bg-success' : 'bg-danger' }}">
                {{ $customer->Status == 1 ? 'Active' : 'Inactive' }}
            </span>
        </div>
        @if($isView)
<div class="col text-end">
    @if($inModal)
        <button type="button"
                class="btn btn-light btn-sm fw-bold text-primary shadow-sm"
                onclick="openCustomerModal('edit', {{ $customer->ID }})">
            <i class="fas fa-edit"></i> Edit Customer
        </button>
    @else
        <a href="{{ route('customers.edit', $customer->ID) }}"
           class="btn btn-light btn-sm fw-bold text-primary shadow-sm">
            <i class="fas fa-edit"></i> Edit Customer
        </a>
    @endif
</div>
@endif
    </div>
</div>
@endif


{{-- ── Form open tag (skipped in view/readonly mode) ──────────── --}}
@if(!$isReadonly)
<form action="{{ $formAction }}" method="POST" id="{{ $uid }}_form" novalidate autocomplete="off">
    @csrf
    @if($isEdit) @method('PUT') @endif
@endif

<div class="{{ $isView ? 'show-mode' : '' }}">

<div class="row justify-content-center">
	<div class="col-md-12">
		<div class="row g-2">
			{{-- Title --}}
			<div class="col-md-3">
				<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Title</label>
				@if($isReadonly)
					<div class="_cfp-detail-value">{{ $customer->Title ?? '-' }}</div>
				@else
					<select name="Title" class="form-select form-select-sm @error('Title') is-invalid @enderror" required>
						<option value="">Select</option>
						@foreach(['Mr','Mrs','Miss','Dr','Prof','Engr'] as $t)
							<option value="{{ $t }}" {{ old('Title', $customer->Title ?? '') == $t ? 'selected' : '' }}>{{ $t }}</option>
						@endforeach
					</select>
					@error('Title')<div class="invalid-feedback">{{ $message }}</div>@enderror
				@endif
			</div>

			{{-- First Name --}}
			<div class="col-md-3">
				<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">First Name</label>
				@if($isReadonly)
					<div class="_cfp-detail-value">{{ $customer->FirstName ?? '-' }}</div>
				@else
					<input type="text" name="FirstName" id="{{ $uid }}_FirstName"
						   class="form-control form-control-sm @error('FirstName') is-invalid @enderror"
						   value="{{ old('FirstName', $customer->FirstName ?? '') }}" placeholder="First name" required>
					@error('FirstName')<div class="invalid-feedback">{{ $message }}</div>@enderror
				@endif
			</div>

			{{-- Last Name --}}
			<div class="col-md-3">
				<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Last Name</label>
				@if($isReadonly)
					<div class="_cfp-detail-value">{{ $customer->LastName ?? '-' }}</div>
				@else
					<input type="text" name="LastName" id="{{ $uid }}_LastName"
						   class="form-control form-control-sm @error('LastName') is-invalid @enderror"
						   value="{{ old('LastName', $customer->LastName ?? '') }}" placeholder="Last name" required>
					@error('LastName')<div class="invalid-feedback">{{ $message }}</div>@enderror
				@endif
			</div>

			{{-- Business Name --}}
			<div class="col-md-3">
				<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Business Name</label>
				@if($isReadonly)
					<div class="_cfp-detail-value">{{ $customer->BusinessName ?? '-' }}</div>
				@else
					<input type="text" name="BusinessName" id="{{ $uid }}_BusinessName"
						   class="form-control form-control-sm @error('BusinessName') is-invalid @enderror"
						   value="{{ old('BusinessName', $customer->BusinessName ?? '') }}" placeholder="Business name" required>
					@error('BusinessName')<div class="invalid-feedback">{{ $message }}</div>@enderror
				@endif
			</div>

			{{-- Display Name --}}
			<div class="col-md-3">
				<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">
					Display Name @if(!$isReadonly)<small class="text-muted fw-normal">(Optional — auto-filled)</small>@endif
				</label>
				@if($isReadonly)
					<div class="_cfp-detail-value">{{ $customer->DisplayName ?? '-' }}</div>
				@else
					<input type="text" name="DisplayName" id="{{ $uid }}_DisplayName"
						   class="form-control form-control-sm @error('DisplayName') is-invalid @enderror"
						   value="{{ old('DisplayName', $customer->DisplayName ?? '') }}" placeholder="Display name">
					@error('DisplayName')<div class="invalid-feedback">{{ $message }}</div>@enderror
				@endif
			</div>
			
			<div class="col-md-3">
				<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Email</label>
				@if($isReadonly)
					<div class="_cfp-detail-value">{{ $customer->Email ?? '-' }}</div>
				@else
					<input type="email" name="Email"
						   class="form-control form-control-sm @error('Email') is-invalid @enderror"
						   value="{{ old('Email', $customer->Email ?? '') }}" placeholder="customer@example.com" required>
					@error('Email')<div class="invalid-feedback">{{ $message }}</div>@enderror
				@endif
			</div>

			<div class="col-md-3">
				<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Mobile</label>
				@if($isReadonly)
					<div class="_cfp-detail-value">{{ $customer->Mobile ?? '-' }}</div>
				@else
					<input type="text" name="Mobile"
						   class="form-control form-control-sm @error('Mobile') is-invalid @enderror"
						   value="{{ old('Mobile', $customer->Mobile ?? '') }}" placeholder="3001234567" required>
					@error('Mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
				@endif
			</div>

			<div class="col-md-3">
				<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">
					Phone @if(!$isReadonly)<small class="text-muted fw-normal">(Optional)</small>@endif
				</label>
				@if($isReadonly)
					<div class="_cfp-detail-value">{{ $customer->Phone ?? '-' }}</div>
				@else
					<input type="text" name="Phone"
						   class="form-control form-control-sm @error('Phone') is-invalid @enderror"
						   value="{{ old('Phone', $customer->Phone ?? '') }}" placeholder="042 1234567">
					@error('Phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
				@endif
			</div>

			<div class="col-md-3">
				<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">
					Website @if(!$isReadonly)<small class="text-muted fw-normal">(Optional)</small>@endif
				</label>
				@if($isReadonly)
					<div class="_cfp-detail-value">
						@if(!empty($customer->Website))
							<a href="{{ $customer->Website }}" target="_blank">{{ $customer->Website }}</a>
						@else - @endif
					</div>
				@else
					<input type="url" name="Website"
						   class="form-control form-control-sm @error('Website') is-invalid @enderror"
						   value="{{ old('Website', $customer->Website ?? '') }}" placeholder="https://www.example.com">
					@error('Website')<div class="invalid-feedback">{{ $message }}</div>@enderror
				@endif
			</div>
			
			<div class="col-md-12 mt-4">
				<ul class="nav nav-tabs" id="myTab" role="tablist">
				  <li class="nav-item" role="presentation">
					<button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Address</button>
				  </li>
				  <li class="nav-item" role="presentation">
					<button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Financial & Tax Information</button>
				  </li>
				  <li class="nav-item" role="presentation">
					<button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact-tab-pane" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">Additional Information</button>
				  </li>
				</ul>
				<div class="tab-content" id="myTabContent">
				  <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
					<div class="row g-4">
						<div class="col-md-6">
							<div class="row g-2">
								<div class="col-md-12">
									<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }} float-start">Street Address</label>
									<div class="form-check float-end">
										<input type="hidden" name="SameAsBilling" value="0">
										<input class="form-check-input" type="checkbox" name="SameAsBilling" id="{{ $uid }}_SameAsBilling" value="1" {{ old('SameAsBilling', $customer->SameAsBilling ?? '0') == '1' ? 'checked' : '' }}>
										<label class="form-check-label" for="{{ $uid }}_SameAsBilling">Same as Primary Address</label>
									</div>
									@if($isReadonly)
										<div class="_cfp-detail-value clearfix">{{ $customer->Address ?? '-' }}</div>
									@else
										<textarea name="Address" rows="2"
												  class="form-control form-control-sm @error('Address') is-invalid @enderror"
												  placeholder="Enter street address" required>{{ old('Address', $customer->Address ?? '') }}</textarea>
										@error('Address')<div class="invalid-feedback">{{ $message }}</div>@enderror
									@endif
								</div>

								<div class="col-md-3">
									<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">City</label>
									@if($isReadonly)
										<div class="_cfp-detail-value">{{ $customer->City ?? '-' }}</div>
									@else
										<input type="text" name="City"
											   class="form-control form-control-sm @error('City') is-invalid @enderror"
											   value="{{ old('City', $customer->City ?? '') }}" placeholder="City" required>
										@error('City')<div class="invalid-feedback">{{ $message }}</div>@enderror
									@endif
								</div>

								<div class="col-md-3">
									<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">State / Province</label>
									@if($isReadonly)
										<div class="_cfp-detail-value">{{ $customer->County ?? '-' }}</div>
									@else
										<input type="text" name="County"
											   class="form-control form-control-sm @error('County') is-invalid @enderror"
											   value="{{ old('County', $customer->County ?? '') }}" placeholder="State/Province" required>
										@error('County')<div class="invalid-feedback">{{ $message }}</div>@enderror
									@endif
								</div>

								<div class="col-md-3">
									<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Postal Code</label>
									@if($isReadonly)
										<div class="_cfp-detail-value">{{ $customer->PostalCode ?? '-' }}</div>
									@else
										<input type="text" name="PostalCode"
											   class="form-control form-control-sm @error('PostalCode') is-invalid @enderror"
											   value="{{ old('PostalCode', $customer->PostalCode ?? '') }}" placeholder="54000" required>
										@error('PostalCode')<div class="invalid-feedback">{{ $message }}</div>@enderror
									@endif
								</div>

								<div class="col-md-3">
									<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Country</label>
									@if($isReadonly)
										<div class="_cfp-detail-value">{{ $customer->Country ?? '-' }}</div>
									@else
										<input type="text" name="Country"
											   class="form-control form-control-sm @error('Country') is-invalid @enderror"
											   value="{{ old('Country', $customer->Country ?? 'Pakistan') }}" placeholder="Pakistan" required>
										@error('Country')<div class="invalid-feedback">{{ $message }}</div>@enderror
									@endif
								</div>

							</div>
						</div>  
						<div class="col-md-6">
							
							<div id="{{ $uid }}_billingFields" class="row g-2" style="{{ old('SameAsBilling', $customer->SameAsBilling ?? '0') == '1' ? 'display:none' : '' }}">

								<div class="col-md-12">
									<label class="form-label _cfp-required">Street Address</label>
									<textarea name="BillingAddress" id="{{ $uid }}_BillingAddress" rows="2"
											  class="form-control form-control-sm @error('BillingAddress') is-invalid @enderror"
											  placeholder="Billing address" required>{{ old('BillingAddress', $customer->BillingAddress ?? '') }}</textarea>
									@error('BillingAddress')<div class="invalid-feedback">{{ $message }}</div>@enderror
								</div>

								<div class="col-md-3">
									<label class="form-label _cfp-required">City</label>
									<input type="text" name="BillingCity" id="{{ $uid }}_BillingCity"
										   class="form-control form-control-sm @error('BillingCity') is-invalid @enderror"
										   value="{{ old('BillingCity', $customer->BillingCity ?? '') }}" placeholder="City" required>
									@error('BillingCity')<div class="invalid-feedback">{{ $message }}</div>@enderror
								</div>

								<div class="col-md-3">
									<label class="form-label _cfp-required">State / Province</label>
									<input type="text" name="BillingCounty" id="{{ $uid }}_BillingCounty"
										   class="form-control form-control-sm @error('BillingCounty') is-invalid @enderror"
										   value="{{ old('BillingCounty', $customer->BillingCounty ?? '') }}" placeholder="State/Province" required>
									@error('BillingCounty')<div class="invalid-feedback">{{ $message }}</div>@enderror
								</div>

								<div class="col-md-3">
									<label class="form-label _cfp-required">Postal Code</label>
									<input type="text" name="BillingPostalCode" id="{{ $uid }}_BillingPostalCode"
										   class="form-control form-control-sm @error('BillingPostalCode') is-invalid @enderror"
										   value="{{ old('BillingPostalCode', $customer->BillingPostalCode ?? '') }}" placeholder="54000" required>
									@error('BillingPostalCode')<div class="invalid-feedback">{{ $message }}</div>@enderror
								</div>

								<div class="col-md-3">
									<label class="form-label _cfp-required">Country</label>
									<input type="text" name="BillingCountry" id="{{ $uid }}_BillingCountry"
										   class="form-control form-control-sm @error('BillingCountry') is-invalid @enderror"
										   value="{{ old('BillingCountry', $customer->BillingCountry ?? 'Pakistan') }}" placeholder="Pakistan" required>
									@error('BillingCountry')<div class="invalid-feedback">{{ $message }}</div>@enderror
								</div>
							</div>
						</div>  
					</div>
				  </div>
				  <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
					<div class="row g-2">
						<div class="col-md-4">
							<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">NTN Number</label>
							@if($isReadonly)
								<div class="_cfp-detail-value">{{ $customer->NTN ?? '-' }}</div>
							@else
								<input type="text" name="NTN"
									   class="form-control form-control-sm @error('NTN') is-invalid @enderror"
									   value="{{ old('NTN', $customer->NTN ?? '') }}" placeholder="Enter NTN" required>
								@error('NTN')<div class="invalid-feedback">{{ $message }}</div>@enderror
							@endif
						</div>

						<div class="col-md-4">
							<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">
								Sales Tax No. @if(!$isReadonly)<small class="text-muted fw-normal">(Optional)</small>@endif
							</label>
							@if($isReadonly)
								<div class="_cfp-detail-value">{{ $customer->SalesTaxNumber ?? '-' }}</div>
							@else
								<input type="text" name="SalesTaxNumber"
									   class="form-control form-control-sm @error('SalesTaxNumber') is-invalid @enderror"
									   value="{{ old('SalesTaxNumber', $customer->SalesTaxNumber ?? '') }}" placeholder="Enter STN">
								@error('SalesTaxNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror
							@endif
						</div>

						<div class="col-md-4">
							<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">CNIC</label>
							@if($isReadonly)
								<div class="_cfp-detail-value">{{ $customer->CNIC ?? '-' }}</div>
							@else
								<input type="text" name="CNIC"
									   class="form-control form-control-sm @error('CNIC') is-invalid @enderror"
									   value="{{ old('CNIC', $customer->CNIC ?? '') }}" placeholder="12345-1234567-1" required>
								@error('CNIC')<div class="invalid-feedback">{{ $message }}</div>@enderror
							@endif
						</div>

						<div class="col-md-4">
							<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Tax Filer Status</label>
							@if($isReadonly)
								<div class="_cfp-detail-value">
									@if(!empty($customer->IsFiler))
										<i class="fas fa-check-circle text-success"></i> Tax Filer
									@else
										<i class="fas fa-times-circle text-danger"></i> Non-Filer
									@endif
								</div>
							@else
								<div class="form-check mt-1">
									<input type="hidden" name="IsFiler" value="0">
									<input class="form-check-input" type="checkbox"
										   name="IsFiler" id="{{ $uid }}_IsFiler" value="1"
										   {{ old('IsFiler', $customer->IsFiler ?? '1') == '1' ? 'checked' : '' }}>
									<label class="form-check-label" for="{{ $uid }}_IsFiler">Is Tax Filer?</label>
								</div>
							@endif
						</div>

					</div>
				  </div>
				  <div class="tab-pane fade" id="contact-tab-pane" role="tabpanel" aria-labelledby="contact-tab" tabindex="0">
					<div class="row g-2">
					<div class="col-md-12">
						<label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Notes</label>
						@if($isReadonly)
							<div class="_cfp-detail-value" style="min-height:70px;">{{ $customer->Notes ?? 'No additional notes.' }}</div>
						@else
							<textarea name="Notes" rows="3"
									  class="form-control form-control-sm @error('Notes') is-invalid @enderror"
									  placeholder="Any additional notes" required>{{ old('Notes', $customer->Notes ?? '') }}</textarea>
							@error('Notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
						@endif
					</div>

					@if(!$isReadonly)
					<div class="col-md-4">
						<label class="form-label _cfp-required">Status</label>
						<select name="Status" class="form-select form-select-sm @error('Status') is-invalid @enderror" required>
							<option value="1" {{ old('Status', $customer->Status ?? '1') != '0' ? 'selected' : '' }}>Active</option>
							<option value="0" {{ old('Status', $customer->Status ?? '') == '0' ? 'selected' : '' }}>Inactive</option>
						</select>
						@error('Status')<div class="invalid-feedback">{{ $message }}</div>@enderror
					</div>
					@endif

				</div>
				  </div>
				</div>
			</div>

		</div>
	</div>
</div>
	



{{-- ════════════ ACTION BUTTONS ════════════ --}}
@if(!$inModal)
    {{-- Shown only on standalone pages --}}
    <div class="_cfp-actions {{ $isCreate ? 'create-mode' : '' }}">

        {{-- Left: timestamp info --}}
        @if(!$isCreate)
        <div class="_cfp-muted">
            @if($isView)
                <i class="fas fa-clock me-1"></i>
                Created: {{ isset($customer->CreatedOn) ? $customer->CreatedOn->format('M d, Y h:i A') : 'N/A' }}
                &nbsp;|&nbsp;
                <i class="fas fa-pen me-1"></i>
                Updated: {{ isset($customer->UpdatedOn) ? $customer->UpdatedOn->format('M d, Y h:i A') : 'Never' }}
            @else
                <i class="fas fa-info-circle me-1"></i>
                Last updated: {{ isset($customer->UpdatedOn) ? $customer->UpdatedOn->format('M d, Y h:i A') : 'Never' }}
            @endif
        </div>
        @endif

        {{-- Right: action buttons --}}
        <div class="d-flex gap-2">
            @if($isView)
                <a href="{{ route('customers.index') }}" class="_cfp-btn _cfp-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            @elseif($isEdit)
                <button type="button" class="_cfp-btn _cfp-btn-secondary"
                        onclick="window.location.href='{{ route('customers.index') }}'">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="_cfp-btn _cfp-btn-primary">
                    <i class="fas fa-save"></i> Update Customer
                </button>
            @else
                <button type="button" class="_cfp-btn _cfp-btn-secondary"
                        onclick="window.location.href='{{ route('customers.index') }}'">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="_cfp-btn _cfp-btn-primary">
                    <i class="fas fa-save"></i> Save Customer
                </button>
            @endif
        </div>

    </div>
@else
    {{-- In modal: hidden trigger button — modal footer Save button calls .click() on this --}}
    <button type="submit" id="{{ $uid }}_form_submit" class="d-none">Submit</button>
@endif

</div>{{-- /.show-mode wrapper --}}

@if(!$isReadonly)
</form>
@endif


{{-- ════════════ Per-instance JavaScript ════════════ --}}
@if(!$isReadonly)
<script>
(function () {
    const uid = '{{ $uid }}';

    /* Auto Display Name */
    function syncDisplayName() {
        const dn = document.getElementById(uid + '_DisplayName');
        if (!dn || dn.value.trim()) return;
        const bn = (document.getElementById(uid + '_BusinessName')?.value || '').trim();
        const fn = (document.getElementById(uid + '_FirstName')?.value  || '').trim();
        const ln = (document.getElementById(uid + '_LastName')?.value   || '').trim();
        dn.value = bn || [fn, ln].filter(Boolean).join(' ');
    }
    ['_BusinessName', '_FirstName', '_LastName'].forEach(suffix => {
        document.getElementById(uid + suffix)?.addEventListener('blur', syncDisplayName);
    });

    /* Billing Address Toggle */
    function toggleBilling() {
        const cb    = document.getElementById(uid + '_SameAsBilling');
        const panel = document.getElementById(uid + '_billingFields');
        if (!cb || !panel) return;

        const inputs = panel.querySelectorAll('input:not([type=hidden]), textarea');
        
        // Define map of Destination ID Suffix -> Source Name
        const map = {
            BillingAddress   : 'Address',
            BillingCity      : 'City',
            BillingCounty    : 'County',
            BillingPostalCode: 'PostalCode',
            BillingCountry   : 'Country',
        };

        if (cb.checked) {
            panel.style.display = 'none';
            inputs.forEach(i => i.removeAttribute('required'));
            // Sync values immediately
            syncValues();
        } else {
            panel.style.display = '';
            inputs.forEach(i => i.setAttribute('required', 'required'));
        }

        function syncValues() {
            Object.entries(map).forEach(([dst, src]) => {
                const dstEl = document.getElementById(uid + '_' + dst);
                const srcEl = document.querySelector('form#' + uid + '_form [name="' + src + '"]');
                if (dstEl && srcEl) {
                    dstEl.value = srcEl.value;
                    // Trigger input event on destination to handle any dependent logic (like validation cleanup)
                    dstEl.dispatchEvent(new Event('input')); 
                }
            });
        }

        // Attach real-time listeners to source fields if not already attached
        // We use a flag on the form to prevent double-binding if this script runs multiple times
        const form = document.getElementById(uid + '_form');
        if (form && !form.dataset.syncAttached) {
            Object.values(map).forEach(srcName => {
                const srcEl = form.querySelector('[name="' + srcName + '"]');
                if (srcEl) {
                    srcEl.addEventListener('input', () => {
                        if (cb.checked) syncValues();
                    });
                }
            });
            form.dataset.syncAttached = 'true';
        }
    }
    document.getElementById(uid + '_SameAsBilling')?.addEventListener('change', toggleBilling);
    toggleBilling();

    /* Scroll to first error */
    window.addEventListener('load', () => {
        const err = document.querySelector('.is-invalid');
        if (err) { err.scrollIntoView({ behavior: 'smooth', block: 'center' }); err.focus(); }
    });

    /* Auto-save draft */
    const form   = document.getElementById(uid + '_form');
    const prefix = 'cfp_{{ $mode }}_{{ $customer->ID ?? "new" }}_';
    if (form) {
        // ── NEW: Intercept Enter key submission in Modal ──
        @if($inModal)
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Stop default browser POST
            const saveBtn = document.getElementById('customer_modal_save_btn');
            if (saveBtn) saveBtn.click(); // Trigger AJAX save
        });
        @endif

        const inputs = form.querySelectorAll('input:not([type=hidden]):not([type=password]), textarea, select');
        if (!document.querySelector('.is-invalid')) {
            inputs.forEach(inp => {
                const val = localStorage.getItem(prefix + inp.name);
                if (val === null) return;
                if (inp.type === 'checkbox' || inp.type === 'radio') {
                    inp.checked = val === 'true';
                    inp.dispatchEvent(new Event('change'));
                } else { inp.value = val; }
            });
        }
        inputs.forEach(inp => {
            const save = function () {
                localStorage.setItem(prefix + this.name,
                    (this.type === 'checkbox' || this.type === 'radio') ? this.checked : this.value);
            };
            inp.addEventListener('input', save);
            inp.addEventListener('change', save);
        });
        form.addEventListener('submit', () =>
            inputs.forEach(inp => localStorage.removeItem(prefix + inp.name))
        );
    }
})();
</script>
@endif