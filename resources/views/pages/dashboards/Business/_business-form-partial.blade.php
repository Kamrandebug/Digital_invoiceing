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
    $rowGapClass = $inModal ? 'g-2' : 'g-3';
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

    /* Business modal — align with Invoice AJAX form (.ifp-modal-shell + _pfp-tab-panel from custom.css) */
    #business_modal_body .bfp-modal-shell .form-label,
    #business_modal_body .bfp-modal-shell label.form-label {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #6c757d;
        margin-bottom: 0.35rem;
    }
    #business_modal_body .bfp-modal-shell .form-control,
    #business_modal_body .bfp-modal-shell textarea.form-control {
        border: 1px solid var(--cm-border-color, #dee2e6) !important;
        border-radius: 5px !important;
        background-color: var(--cm-input-bg, #ffffff) !important;
        box-sizing: border-box !important;
        font-family: "Space Grotesk", sans-serif !important;
        font-size: 12px !important;
        font-weight: 400;
        line-height: 1.5;
        color: rgb(1, 26, 66) !important;
        min-height: calc(1.5em + 10px + 2px);
        padding: 5px 11px;
    }
    #business_modal_body .bfp-modal-shell .form-control:focus,
    #business_modal_body .bfp-modal-shell textarea.form-control:focus {
        border-color: rgb(var(--primary-rgb)) !important;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        outline: none;
    }
    #business_modal_body .bfp-modal-shell input[type="file"].form-control {
        padding: 0.28rem 0.45rem;
        line-height: 1.35;
    }
    #business_modal_body .bfp-modal-shell .form-text {
        font-size: 11px;
        color: var(--cm-text-muted, #666);
    }
    #business_modal_body .bfp-modal-shell .show-mode ._cfp-detail-label {
        font-family: "Space Grotesk", sans-serif;
    }
    #business_modal_body .bfp-modal-shell .show-mode ._cfp-detail-value {
        font-family: "Space Grotesk", sans-serif !important;
        font-size: 12px !important;
        font-weight: 400;
        line-height: 1.5;
        color: rgb(1, 26, 66) !important;
        padding: 5px 11px;
        border-radius: 5px;
        min-height: calc(1.5em + 10px + 2px);
    }
    #business_modal_body .bfp-modal-footer {
        background-color: var(--bs-tertiary-bg, #f8f9fa);
        margin-top: 0.35rem;
        padding: 0.65rem 0.75rem !important;
        border-top: 1px solid var(--bs-border-color, #dee2e6) !important;
        border-radius: 0 0 0.5rem 0.5rem;
    }
    /* Address + Business logo — fixed control height (53.6px), default label gap */
    .bf-address-fixed-h,
    .bf-logo-card.bf-logo-fixed-h {
        height: 53.6px;
        min-height: 53.6px;
        max-height: 53.6px;
        box-sizing: border-box;
    }
    .bf-logo-card {
        border: 1px solid var(--cm-border-color, #dee2e6);
        border-radius: 8px;
        background: linear-gradient(165deg, #ffffff 0%, var(--bs-tertiary-bg, #f8f9fa) 100%);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        box-sizing: border-box;
    }
    textarea.form-control-sm.bf-address-fixed-h {
        resize: none;
        overflow: auto;
        line-height: 1.35;
        padding-top: 0.22rem;
        padding-bottom: 0.22rem;
    }
    ._cfp-detail-value.bf-address-fixed-h {
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
        display: flex;
        align-items: center;
    }
    .bf-logo-card.bf-logo-fixed-h {
        display: flex;
        align-items: center;
        padding: 0.2rem 0.4rem;
        overflow: hidden;
    }
    .bf-logo-card.bf-logo-fixed-h .bf-logo-upload-row {
        width: 100%;
        min-height: 0 !important;
        margin: 0;
    }
    .bf-logo-card.bf-logo-fixed-h .bf-logo-file-wrap {
        flex: 1 1 0;
        min-width: 0;
        max-width: calc(100% - 34px - 0.35rem);
    }
    .bf-logo-card.bf-logo-fixed-h .bf-logo-file-wrap .bf-logo-file-input {
        width: 100%;
        max-width: 100%;
    }
    .bf-logo-card.bf-logo-fixed-h input[type="file"].bf-logo-file-input.form-control {
        padding: 0.12rem 0.3rem !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
        min-height: 0 !important;
    }
    .bf-logo-card.bf-logo-fixed-h input[type="file"].bf-logo-file-input::file-selector-button {
        padding: 0.2rem 0.45rem;
        font-size: 10px;
        margin-right: 0.4rem;
    }
    .bf-logo-thumb-wrap.bf-logo-thumb-wrap--inline {
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed rgba(var(--primary-rgb), 0.3);
        border-radius: 5px;
        background: var(--cm-input-bg, #fff);
        overflow: hidden;
        flex-shrink: 0;
        box-sizing: border-box;
    }
    .bf-logo-thumb-wrap--inline .bf-logo-thumb-img {
        max-width: 28px;
        max-height: 28px;
        width: auto;
        height: auto;
        object-fit: contain;
        padding: 1px;
    }
    .bf-logo-thumb-wrap--inline .bf-logo-fallback {
        line-height: 1;
        font-size: 7px;
        max-width: 32px;
        padding: 0;
    }
    .bf-logo-thumb-wrap--inline .bf-logo-fallback i {
        font-size: 0.65rem;
        opacity: 0.42;
    }
    .bf-logo-label-types {
        font-weight: 400;
        font-size: 10px;
        line-height: 1.2;
        color: #6c757d;
    }
    label._cfp-detail-label .bf-logo-label-types {
        font-weight: 400;
        font-size: 10px;
        color: #6c757d;
    }
    #business_modal_body .bfp-modal-shell .bf-logo-label-types {
        font-weight: 400 !important;
        font-size: 10px !important;
        line-height: 1.2 !important;
        color: #6c757d !important;
    }
    input[type="file"].bf-logo-file-input.form-control {
        cursor: pointer;
        padding: 0.28rem 0.4rem !important;
        border-radius: 6px !important;
    }
    input[type="file"].bf-logo-file-input::file-selector-button {
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-weight: 600;
        font-size: 11px;
        letter-spacing: 0.02em;
        padding: 0.32rem 0.65rem;
        margin-right: 0.5rem;
        border: 0;
        border-radius: 5px;
        background: rgb(var(--primary-rgb));
        color: #fff;
        cursor: pointer;
        transition: filter 0.15s ease;
    }
    input[type="file"].bf-logo-file-input:hover::file-selector-button {
        filter: brightness(1.05);
    }
    input[type="file"].bf-logo-file-input:focus::file-selector-button {
        outline: 2px solid rgba(var(--primary-rgb), 0.45);
        outline-offset: 1px;
    }
    .bf-logo-upload-row {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 0.35rem;
    }
    #business_modal_body .bfp-modal-shell textarea.form-control-sm.bf-address-fixed-h {
        height: 53.6px !important;
        min-height: 53.6px !important;
        max-height: 53.6px !important;
    }
</style>
@endonce

@if(!$isReadonly)
<form action="{{ $formAction }}" method="POST" id="{{ $uid }}_form" enctype="multipart/form-data" novalidate autocomplete="off">
    @csrf
    @if($isEdit) @method('PUT') @endif
@endif

@if($inModal)
<div class="ifp-modal-shell bfp-modal-shell">
    <div class="_pfp-tab-panel-shell border rounded overflow-hidden mb-3">
        <div class="_pfp-tab-panel-body">
@endif

<div class="{{ $isReadonly ? 'show-mode' : '' }}">
    <div class="row justify-content-center">
        <div class="{{ $inModal ? 'col-12' : 'col-md-11' }}">
            <div class="row {{ $rowGapClass }} py-2">
                {{-- 1. Business Name + Province --}}
                <div class="col-md-8">
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

                <div class="col-md-4">
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

                {{-- 2. NTN + Sales Tax Number + CNIC --}}
                <div class="col-md-4">
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

                {{-- 3. Address + Logo — widths match Business Name (8) + Province (4); fixed 53.6px control height --}}
                <div class="col-md-8">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label _cfp-required' }}">Address</label>
                    @if($isReadonly)
                        <div class="_cfp-detail-value bf-address-fixed-h">{{ $record->Address ?? '-' }}</div>
                    @else
                        <textarea name="Address" rows="2"
                                  class="form-control form-control-sm bf-address-fixed-h @error('Address') is-invalid @enderror"
                                  placeholder="Business address" required>{{ old('Address', $record->Address ?? '') }}</textarea>
                        @error('Address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="{{ $isReadonly ? '_cfp-detail-label' : 'form-label' }}">
                        Business logo<span class="bf-logo-label-types"> (JPEG, PNG, GIF or WebP)</span>
                    </label>
                    @php
                        $bfLogoRaw = trim((string) ($isReadonly ? ($record->ImageUrl ?? '') : old('ImageUrl', $record->ImageUrl ?? '')));
                        $bfLogoDisplayUrl = null;
                        if ($bfLogoRaw !== '') {
                            if (preg_match('#^https?://#i', $bfLogoRaw)) {
                                $bfLogoDisplayUrl = $bfLogoRaw;
                            } else {
                                $norm = ltrim(str_replace('\\', '/', $bfLogoRaw), '/');
                                $bfLogoDisplayUrl = str_starts_with($norm, 'storage/')
                                    ? asset($norm)
                                    : asset('storage/' . $norm);
                            }
                        }
                    @endphp
                    @if($isReadonly)
                        <div class="bf-logo-card bf-logo-fixed-h">
                            <div class="d-flex align-items-center gap-2 bf-logo-upload-row justify-content-end">
                                <div class="bf-logo-thumb-wrap bf-logo-thumb-wrap--inline flex-shrink-0">
                                    @if($bfLogoDisplayUrl)
                                        <img class="bf-logo-thumb-img" src="{{ $bfLogoDisplayUrl }}" alt="Business logo" decoding="async"
                                             onerror="this.classList.add('d-none'); var x=this.nextElementSibling; if(x) x.classList.remove('d-none');">
                                        <div class="bf-logo-fallback text-muted d-none text-center px-1">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @else
                                        <div class="bf-logo-fallback text-muted text-center px-1">
                                            <i class="fas fa-image"></i><br>No logo
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="ImageUrl" value="{{ e($bfLogoRaw) }}">
                        <div class="bf-logo-card bf-logo-fixed-h">
                            <div class="d-flex align-items-center gap-2 bf-logo-upload-row">
                                <div class="bf-logo-file-wrap">
                                    <input type="file" name="BusinessLogo" accept="image/jpeg,image/png,image/gif,image/webp"
                                           class="form-control form-control-sm bf-logo-file-input @error('BusinessLogo') is-invalid @enderror">
                                </div>
                                <div class="bf-logo-thumb-wrap bf-logo-thumb-wrap--inline flex-shrink-0">
                                    @if($bfLogoDisplayUrl)
                                        <img class="bf-logo-thumb-img" src="{{ $bfLogoDisplayUrl }}" alt="Current logo" decoding="async"
                                             onerror="this.classList.add('d-none'); var x=this.nextElementSibling; if(x) x.classList.remove('d-none');">
                                        <div class="bf-logo-fallback text-muted d-none text-center px-1">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @else
                                        <div class="bf-logo-fallback text-muted text-center px-1">
                                            <i class="fas fa-cloud-upload-alt"></i><br>No logo
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @error('BusinessLogo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @endif
                </div>

                {{-- First Name → Phone: three columns per row --}}
                <div class="col-md-4">
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

                <div class="col-md-4">
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

                {{-- Row: First, Last, Email --}}
                <div class="col-md-4">
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

                <div class="col-md-4">
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

@if($inModal)
        </div>{{-- ._pfp-tab-panel-body --}}
    </div>{{-- ._pfp-tab-panel-shell --}}
@endif

@if(!$isReadonly)
    <div class="d-flex justify-content-end align-items-center gap-2 {{ $inModal ? 'bfp-modal-footer' : 'mt-3 pt-2 border-top' }}">
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
@endif

@if($inModal)
</div>{{-- .ifp-modal-shell --}}
@endif

@if(!$isReadonly)
</form>
@endif

@if(!$isReadonly)
<script>
(function () {
    var form = document.getElementById(@json($uid . '_form'));
    if (!form) return;
    var input = form.querySelector('input[name="BusinessLogo"]');
    var thumbWrap = form.querySelector('.bf-logo-thumb-wrap');
    if (!input || !thumbWrap) return;
    input.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;
        var u = URL.createObjectURL(this.files[0]);
        var img = thumbWrap.querySelector('.bf-logo-thumb-img');
        var fb = thumbWrap.querySelector('.bf-logo-fallback');
        if (!img) {
            img = document.createElement('img');
            img.className = 'bf-logo-thumb-img';
            img.alt = 'Preview';
            img.decoding = 'async';
            thumbWrap.insertBefore(img, thumbWrap.firstChild);
        }
        img.classList.remove('d-none');
        img.onerror = function () {
            img.classList.add('d-none');
            if (fb) fb.classList.remove('d-none');
        };
        img.onload = function () { URL.revokeObjectURL(u); };
        img.src = u;
        if (fb) fb.classList.add('d-none');
    });
})();
</script>
@endif
