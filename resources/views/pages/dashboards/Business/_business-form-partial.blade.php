@php
    $mode    = $mode ?? 'create';
    $record  = $record ?? null;
    $inModal = $inModal ?? false;

    $isCreate   = $mode === 'create';
    $isEdit     = $mode === 'edit';
    $isReadonly = $mode === 'show';
    $formAction = $isCreate
        ? route('business.save_transaction')
        : ($isEdit ? route('business.edit_transaction', $record->ID) : '#');
    $uid = 'bfp_' . $mode . '_' . ($record->ID ?? 'new');
@endphp

@once
<style>
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
    ._cfp-required::after {
        content: " *";
        color: #dc3545;
    }
</style>
@endonce

@if(!$isReadonly)
<form action="{{ $formAction }}" method="POST" id="{{ $uid }}_form" novalidate autocomplete="off">
    @csrf
    @if($isEdit) @method('PUT') @endif
@endif

<div class="{{ $isReadonly ? 'show-mode' : '' }}">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="row g-3 py-2">
                {{-- 1. Business Name --}}
                <div class="col-md-12">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Business Name</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->BusinessName ?? '-' }}</div>
                    @else
                        <input type="text" name="BusinessName"
                               class="form-control form-control-sm @error('BusinessName') is-invalid @enderror"
                               value="{{ old('BusinessName', $record->BusinessName ?? '') }}" placeholder="Business name" required>
                        @error('BusinessName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- 2. NTN --}}
                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">NTN</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->NTN ?? '-' }}</div>
                    @else
                        <input type="text" name="NTN"
                               class="form-control form-control-sm @error('NTN') is-invalid @enderror"
                               value="{{ old('NTN', $record->NTN ?? '') }}" placeholder="Enter NTN" required>
                        @error('NTN')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- 3. Province --}}
                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Province</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Province ?? '-' }}</div>
                    @else
                        <input type="text" name="Province"
                               class="form-control form-control-sm @error('Province') is-invalid @enderror"
                               value="{{ old('Province', $record->Province ?? '') }}" placeholder="Province">
                        @error('Province')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- 4. Address --}}
                <div class="col-md-12">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Address</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Address ?? '-' }}</div>
                    @else
                        <textarea name="Address" rows="2"
                                  class="form-control form-control-sm @error('Address') is-invalid @enderror"
                                  placeholder="Business address" required>{{ old('Address', $record->Address ?? '') }}</textarea>
                        @error('Address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- 5–6. First / Last Name --}}
                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">First Name</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->FirstName ?? '-' }}</div>
                    @else
                        <input type="text" name="FirstName"
                               class="form-control form-control-sm @error('FirstName') is-invalid @enderror"
                               value="{{ old('FirstName', $record->FirstName ?? '') }}" placeholder="First name">
                        @error('FirstName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Last Name</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->LastName ?? '-' }}</div>
                    @else
                        <input type="text" name="LastName"
                               class="form-control form-control-sm @error('LastName') is-invalid @enderror"
                               value="{{ old('LastName', $record->LastName ?? '') }}" placeholder="Last name">
                        @error('LastName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- 7–8. Email / Mobile --}}
                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Email</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Email ?? '-' }}</div>
                    @else
                        <input type="email" name="Email"
                               class="form-control form-control-sm @error('Email') is-invalid @enderror"
                               value="{{ old('Email', $record->Email ?? '') }}" placeholder="business@example.com">
                        @error('Email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Mobile</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Mobile ?? '-' }}</div>
                    @else
                        <input type="text" name="Mobile"
                               class="form-control form-control-sm @error('Mobile') is-invalid @enderror"
                               value="{{ old('Mobile', $record->Mobile ?? '') }}" placeholder="3001234567">
                        @error('Mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- 9–10. Website / Phone --}}
                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Website</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">
                            @if(!empty($record->Website))
                                <a href="{{ $record->Website }}" target="_blank">{{ $record->Website }}</a>
                            @else - @endif
                        </div>
                    @else
                        <input type="url" name="Website"
                               class="form-control form-control-sm @error('Website') is-invalid @enderror"
                               value="{{ old('Website', $record->Website ?? '') }}" placeholder="https://www.example.com">
                        @error('Website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Phone</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Phone ?? '-' }}</div>
                    @else
                        <input type="text" name="Phone"
                               class="form-control form-control-sm @error('Phone') is-invalid @enderror"
                               value="{{ old('Phone', $record->Phone ?? '') }}" placeholder="042 1234567">
                        @error('Phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- 11–12. Sales Tax Number / CNIC --}}
                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Sales Tax Number</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->SalesTaxNumber ?? '-' }}</div>
                    @else
                        <input type="text" name="SalesTaxNumber"
                               class="form-control form-control-sm @error('SalesTaxNumber') is-invalid @enderror"
                               value="{{ old('SalesTaxNumber', $record->SalesTaxNumber ?? '') }}" placeholder="Enter STN">
                        @error('SalesTaxNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">CNIC</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->CNIC ?? '-' }}</div>
                    @else
                        <input type="text" name="CNIC"
                               class="form-control form-control-sm @error('CNIC') is-invalid @enderror"
                               value="{{ old('CNIC', $record->CNIC ?? '') }}" placeholder="12345-1234567-1">
                        @error('CNIC')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- 13. Notes --}}
                <div class="col-md-12">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Notes</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value" style="min-height:70px;">{{ $record->Notes ?? 'No additional notes.' }}</div>
                    @else
                        <textarea name="Notes" rows="3"
                                  class="form-control form-control-sm @error('Notes') is-invalid @enderror"
                                  placeholder="Any additional notes">{{ old('Notes', $record->Notes ?? '') }}</textarea>
                        @error('Notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- 14–15. Tokens --}}
                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Sandbox Access Token</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Sandbox_access_token ?? '-' }}</div>
                    @else
                        <input type="text" name="Sandbox_access_token"
                               class="form-control form-control-sm @error('Sandbox_access_token') is-invalid @enderror"
                               value="{{ old('Sandbox_access_token', $record->Sandbox_access_token ?? '') }}"
                               placeholder="Enter sandbox access token">
                        @error('Sandbox_access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">IRIS Access Token</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->iris_access_token ?? '-' }}</div>
                    @else
                        <input type="text" name="iris_access_token"
                               class="form-control form-control-sm @error('iris_access_token') is-invalid @enderror"
                               value="{{ old('iris_access_token', $record->iris_access_token ?? '') }}"
                               placeholder="Enter IRIS access token">
                        @error('iris_access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(!$isReadonly)
    <div class="d-flex justify-content-end align-items-center gap-2 mt-3 pt-2 border-top">
        <button type="button"
                class="btn btn-sm btn-light"
                data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit"
                class="btn btn-sm btn-primary">
            {{ $isCreate ? 'Save Business' : 'Update Business' }}
        </button>
    </div>
</form>
@endif
