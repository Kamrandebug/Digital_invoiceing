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
                <div class="col-md-3">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Title</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Title ?? '-' }}</div>
                    @else
                        <select name="Title" class="form-select form-select-sm @error('Title') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach(['Mr','Mrs','Miss','Ms','Dr','Prof','Engr'] as $t)
                                <option value="{{ $t }}" {{ old('Title', $record->Title ?? '') == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                        @error('Title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-3">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">First Name</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->FirstName ?? '-' }}</div>
                    @else
                        <input type="text" name="FirstName"
                               class="form-control form-control-sm @error('FirstName') is-invalid @enderror"
                               value="{{ old('FirstName', $record->FirstName ?? '') }}" placeholder="First name" required>
                        @error('FirstName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-3">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Last Name</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->LastName ?? '-' }}</div>
                    @else
                        <input type="text" name="LastName"
                               class="form-control form-control-sm @error('LastName') is-invalid @enderror"
                               value="{{ old('LastName', $record->LastName ?? '') }}" placeholder="Last name" required>
                        @error('LastName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-3">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Business Name</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->BusinessName ?? '-' }}</div>
                    @else
                        <input type="text" name="BusinessName"
                               class="form-control form-control-sm @error('BusinessName') is-invalid @enderror"
                               value="{{ old('BusinessName', $record->BusinessName ?? '') }}" placeholder="Business name">
                        @error('BusinessName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Email</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Email ?? '-' }}</div>
                    @else
                        <input type="email" name="Email"
                               class="form-control form-control-sm @error('Email') is-invalid @enderror"
                               value="{{ old('Email', $record->Email ?? '') }}" placeholder="business@example.com" required>
                        @error('Email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Mobile</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Mobile ?? '-' }}</div>
                    @else
                        <input type="text" name="Mobile"
                               class="form-control form-control-sm @error('Mobile') is-invalid @enderror"
                               value="{{ old('Mobile', $record->Mobile ?? '') }}" placeholder="3001234567" required>
                        @error('Mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-4">
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

                <div class="col-md-4">
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

                <div class="col-md-8">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Address</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->Address ?? '-' }}</div>
                    @else
                        <textarea name="Address" rows="2"
                                  class="form-control form-control-sm @error('Address') is-invalid @enderror"
                                  placeholder="Business address">{{ old('Address', $record->Address ?? '') }}</textarea>
                        @error('Address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">NTN</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">{{ $record->NTN ?? '-' }}</div>
                    @else
                        <input type="text" name="NTN"
                               class="form-control form-control-sm @error('NTN') is-invalid @enderror"
                               value="{{ old('NTN', $record->NTN ?? '') }}" placeholder="Enter NTN">
                        @error('NTN')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-4">
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

                <div class="col-md-4">
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

                <div class="col-md-4">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">Tax Filer Status</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">
                            @if(!empty($record->IsFiler))
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
                                   {{ old('IsFiler', $record->IsFiler ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $uid }}_IsFiler">Is Tax Filer?</label>
                        </div>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Status</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value">
                            {{ ($record->Status ?? 1) == 1 ? 'Active' : 'Inactive' }}
                        </div>
                    @else
                        <select name="Status" class="form-select form-select-sm @error('Status') is-invalid @enderror" required>
                            <option value="1" {{ old('Status', $record->Status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('Status', $record->Status ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('Status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

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

