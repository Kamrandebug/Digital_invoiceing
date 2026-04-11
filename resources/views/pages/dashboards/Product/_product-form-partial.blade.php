{{--
    Product Form Partial for Modal
    create | edit | show — with left-side tabs to avoid scrolling.
--}}

@php
    $mode      = $mode ?? 'create';
    $product   = $product ?? null;
    $inModal   = $inModal ?? false;
    $isCreate  = ($mode === 'create');
    $isEdit    = ($mode === 'edit');
    $isReadonly = ($mode === 'show');
    $formAction = $isCreate ? route('products.save_transaction') : ($isEdit ? route('products.edit_transaction', $product->ID) : '#');
    $uid = 'pfp_' . $mode . '_' . ($product->ID ?? 'new');
    $taxDisplayLabels = isset($taxDisplayLabels) && is_array($taxDisplayLabels) ? $taxDisplayLabels : null;
@endphp

@once

@endonce
<link rel="stylesheet" href="{{asset('build/assets/themecss/custom.css')}}">
@if(!$isCreate && $product)
<div class="_pfp-info-badge {{ $isReadonly ? 'show-mode' : 'edit-mode' }}">
    <div class="row align-items-center g-2">
        <div class="col-auto"><strong><i class="fas fa-id-badge"></i> ID:</strong> #{{ $product->ID }}</div>
        <div class="col-auto"><strong><i class="fas fa-barcode"></i> Code:</strong> {{ $product->Code ?? 'N/A' }}</div>
        <div class="col-auto"><strong><i class="fas fa-tag"></i> Price:</strong> PKR {{ number_format($product->SalePrice ?? 0, 2) }}</div>
        <div class="col-auto"><strong><i class="fas fa-boxes"></i> Qty:</strong> {{ $product->CurrentQuantity ?? 0 }}</div>
        @if($isReadonly && $inModal)
        <div class="col text-end">
            <button type="button" class="btn btn-light btn-sm fw-bold text-primary" onclick="openProductModal('edit', {{ $product->ID }})">
                <i class="fas fa-edit"></i> Edit Product
            </button>
        </div>
        @endif
    </div>
</div>
@endif

@if(!$isReadonly)
<form action="{{ $formAction }}" method="POST" id="product_modal_form" novalidate>
    @csrf
    @if($isEdit) @method('PUT') @endif
    {{-- Hidden defaults (only Status is needed now) --}}
    <input type="hidden" name="Status" value="{{ old('Status', $product->Status ?? '1') }}">
@endif

<div class="{{ $isReadonly ? 'show-mode' : '' }}">
    <div class="row _pfp-tabs-row">
        <div class="col-md-3 _pfp-nav">
            <nav class="nav flex-column" role="tablist">
                <a class="nav-link active" data-bs-toggle="tab" href="#{{ $uid }}_basic" role="tab"><i class="fas fa-box-open"></i> Basic</a>
                <a class="nav-link" data-bs-toggle="tab" href="#{{ $uid }}_pricing" role="tab"><i class="fas fa-dollar-sign"></i> Pricing</a>
                <a class="nav-link" data-bs-toggle="tab" href="#{{ $uid }}_tax" role="tab"><i class="fas fa-receipt"></i> Tax</a>
            </nav>
        </div>
        <div class="col-md-9">
            <div class="tab-content">
                {{-- Tab: Basic --}}
                <div class="tab-pane fade show active _pfp-tab-pane" id="{{ $uid }}_basic" role="tabpanel">
                    <h6 class="_pfp-section-title"><i class="fas fa-box-open"></i> Basic Information</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label _pfp-required' }}">Product Name</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->Name ?? '-' }}</div>
                            @else<input type="text" name="Name" class="form-control form-control-sm @error('Name') is-invalid @enderror" value="{{ old('Name', $product->Name ?? '') }}" placeholder="Product name" required>@error('Name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label _pfp-required' }}">Code</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->Code ?? '-' }}</div>
                            @else<input type="text" name="Code" class="form-control form-control-sm @error('Code') is-invalid @enderror" value="{{ old('Code', $product->Code ?? '') }}" placeholder="PROD-001" required>@error('Code')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Type</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->Type ?? '-' }}</div>
                            @else<select name="Type" class="form-select form-select-sm @error('Type') is-invalid @enderror">
                                <option value="">Select</option>
                                <option value="Product" {{ old('Type', $product->Type ?? '') == 'Product' ? 'selected' : '' }}>Product</option>
                                <option value="Service" {{ old('Type', $product->Type ?? '') == 'Service' ? 'selected' : '' }}>Service</option>
                            </select>@error('Type')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-6">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Category</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->CategoryName ?? $product->Category ?? '-' }}</div>
                            @else<select name="Category" class="form-select form-select-sm @error('Category') is-invalid @enderror"><option value="">Select Category</option></select>@error('Category')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-6">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Unit</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->UnitName ?? $product->Unit ?? '-' }}</div>
                            @else<select name="Unit" class="form-select form-select-sm @error('Unit') is-invalid @enderror"><option value="0">Select Unit</option></select>@error('Unit')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Current Qty</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->CurrentQuantity ?? 0 }}</div>
                            @else<input type="number" step="0.000001" name="CurrentQuantity" class="form-control form-control-sm @error('CurrentQuantity') is-invalid @enderror" value="{{ old('CurrentQuantity', $product->CurrentQuantity ?? '0') }}">@error('CurrentQuantity')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                    </div>
                </div>

                {{-- Tab: Pricing --}}
                <div class="tab-pane fade _pfp-tab-pane" id="{{ $uid }}_pricing" role="tabpanel">
                    <h6 class="_pfp-section-title"><i class="fas fa-dollar-sign"></i> Pricing</h6>
                    <div class="row g-2">
                        <div class="col-md-4"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label _pfp-required' }}">Sale Price (PKR)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->SalePrice ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="SalePrice" class="form-control form-control-sm @error('SalePrice') is-invalid @enderror" value="{{ old('SalePrice', $product->SalePrice ?? '0.00') }}" required>@error('SalePrice')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-4"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Cost (PKR)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->Cost ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="Cost" class="form-control form-control-sm @error('Cost') is-invalid @enderror" value="{{ old('Cost', $product->Cost ?? '0.00') }}">@error('Cost')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-4"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Weight (kg)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->Weight ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="Weight" class="form-control form-control-sm @error('Weight') is-invalid @enderror" value="{{ old('Weight', $product->Weight ?? '0.00') }}">@error('Weight')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-3">
                            @if($isReadonly)
                                <label class="_pfp-detail-label">IsFixedValue</label>
                                @php $isFixedValue = (float)($product->TPRate ?? 0) > 0; @endphp
                                <div class="_pfp-detail-value">{{ $isFixedValue ? 'Yes' : 'No' }}</div>
                            @else
                                @php
                                    $isFixedValueOld = old('IsFixedValue');
                                    $isFixedValueChecked = $isFixedValueOld !== null
                                        ? ((string)$isFixedValueOld === '1')
                                        : ((float)($product->TPRate ?? 0) > 0);
                                @endphp
                                <label class="form-label d-block">IsFixedValue</label>
                                <input type="hidden" name="IsFixedValue" value="0">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="{{ $uid }}_is_fixed_value" name="IsFixedValue" value="1" {{ $isFixedValueChecked ? 'checked' : '' }}>
                                    <label class="form-check-label" for="{{ $uid }}_is_fixed_value">Enable fixed notified value</label>
                                </div>
                                @error('IsFixedValue')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-5">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">FixedNotifiedValueOrRetailPrice</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ number_format($product->TPRate ?? 0, 2) }}</div>
                            @else
                                <input type="number" step="0.000001" min="0" name="TPRate" id="{{ $uid }}_fixed_notified_value" class="form-control form-control-sm @error('TPRate') is-invalid @enderror" value="{{ old('TPRate', $product->TPRate ?? '0.00') }}">
                                @error('TPRate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tab: Tax --}}
                <div class="tab-pane fade _pfp-tab-pane" id="{{ $uid }}_tax" role="tabpanel">
                    <h6 class="_pfp-section-title"><i class="fas fa-receipt"></i> Tax</h6>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">HS Code</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ $product->HsCode ?? '-' }}</div>
                            @else
                                <input type="text"
                                       name="HsCode"
                                       class="form-control form-control-sm @error('HsCode') is-invalid @enderror"
                                       value="{{ old('HsCode', $product->HsCode ?? '') }}"
                                       placeholder="e.g. 8517.12">
                                @error('HsCode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">UOM</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['uom'] ?? '—') : ($product->UOM ?? '-') }}</div>
                            @else
                                <select name="UOM"
                                        class="form-select form-select-sm @error('UOM') is-invalid @enderror"
                                        data-selected="{{ old('UOM', $product->UOM ?? '') }}">
                                    <option value="">Select UOM</option>
                                </select>
                                @error('UOM')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Sale Type</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['sale_type'] ?? '—') : ($product->SaleType ?? '-') }}</div>
                            @else
                                <select name="SaleType"
                                        class="form-select form-select-sm @error('SaleType') is-invalid @enderror"
                                        data-selected="{{ old('SaleType', $product->SaleType ?? '') }}"
                                        @if($isCreate && (string) old('SaleType', $product->SaleType ?? '') === '')
                                        data-apply-default-sale-type="1"
                                        @endif>
                                    <option value="">Loading...</option>
                                </select>
                                @error('SaleType')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Sale Type To Rate</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['sale_type_to_rate'] ?? '—') : ($product->SaleTypeToRate ?? '-') }}</div>
                            @else
                                <select name="SaleTypeToRate"
                                        class="form-select form-select-sm @error('SaleTypeToRate') is-invalid @enderror"
                                        data-selected="{{ old('SaleTypeToRate', $product->SaleTypeToRate ?? '') }}">
                                    <option value="">Select Sale Type first</option>
                                </select>
                                @error('SaleTypeToRate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">SRO Schedule</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['sro_schedule'] ?? '—') : ($product->SROSchedule ?? '-') }}</div>
                            @else
                                <select name="SROSchedule"
                                        class="form-select form-select-sm @error('SROSchedule') is-invalid @enderror"
                                        data-selected="{{ old('SROSchedule', $product->SROSchedule ?? '') }}">
                                    <option value="">Select Sale Type To Rate first</option>
                                </select>
                                @error('SROSchedule')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">SRO Item</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['sro_item'] ?? '—') : ($product->SROItem ?? '-') }}</div>
                            @else
                                <select name="SROItem"
                                        class="form-select form-select-sm @error('SROItem') is-invalid @enderror"
                                        data-selected="{{ old('SROItem', $product->SROItem ?? '') }}">
                                    <option value="">Select SRO Schedule first</option>
                                </select>
                                @error('SROItem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-12">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Sale Information</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ $product->SaleInformation ?? '-' }}</div>
                            @else
                                <textarea name="SaleInformation"
                                          class="form-control form-control-sm @error('SaleInformation') is-invalid @enderror"
                                          rows="2"
                                          placeholder="Sale info">{{ old('SaleInformation', $product->SaleInformation ?? '') }}</textarea>
                                @error('SaleInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!$isReadonly)
</form>
@endif

<script>
(function() {
    var form = document.getElementById('product_modal_form');
    if (!form) return;

    // Focus the first invalid field (if any)
    var firstInvalid = form.querySelector('.is-invalid');
    if (firstInvalid) {
        var pane = firstInvalid.closest('.tab-pane');
        if (pane) {
            var id = pane.id;
            var navLink = document.querySelector('a[href="#' + id + '"]');
            if (navLink) { navLink.click(); }
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
        }
    }

    // HS Code → load UOM list + description
    var hsInput        = form.querySelector('input[name="HsCode"]');
    var uomSelect      = form.querySelector('select[name="UOM"]');
    var saleInfo       = form.querySelector('textarea[name="SaleInformation"]');
    var isFixedValueCheckbox = form.querySelector('input[name="IsFixedValue"][type="checkbox"]');
    var fixedNotifiedValueInput = form.querySelector('input[name="TPRate"]');
    var saleTypeSelect      = form.querySelector('select[name="SaleType"]');
    var saleTypeToRateSelect = form.querySelector('select[name="SaleTypeToRate"]');
    var sroScheduleSelect   = form.querySelector('select[name="SROSchedule"]');
    var sroItemSelect       = form.querySelector('select[name="SROItem"]');

    function syncFixedValueUi() {
        if (!isFixedValueCheckbox || !fixedNotifiedValueInput) return;
        var checked = !!isFixedValueCheckbox.checked;
        fixedNotifiedValueInput.readOnly = !checked;
        if (!checked) {
            fixedNotifiedValueInput.value = '0.00';
        }
    }

    function clearUomOptions() {
        if (!uomSelect) return;
        uomSelect.innerHTML = '';
        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Select UOM';
        uomSelect.appendChild(opt);
    }

    function fetchHsCodeInfo() {
        var code = (hsInput && hsInput.value) ? hsInput.value.trim() : '';
        if (uomSelect) clearUomOptions();

        if (!code) {
            if (saleInfo) saleInfo.value = '';
            return;
        }

        fetch("{{ route('products.hscode-info') }}?code=" + encodeURIComponent(code), {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success') return;
                if (uomSelect) {
                    var selectedUom = uomSelect.value || uomSelect.getAttribute('data-selected') || '';
                    clearUomOptions();
                    if (Array.isArray(data.uoms) && data.uoms.length) {
                        data.uoms.forEach(function (item) {
                            // item.id = UOM ID (uoM_ID), item.text = description
                            var opt = document.createElement('option');
                            opt.value = item.id;
                            opt.textContent = item.text;
                            if (selectedUom && String(selectedUom) === String(item.id)) {
                                opt.selected = true;
                            }
                            uomSelect.appendChild(opt);
                        });
                        if (selectedUom) {
                            uomSelect.setAttribute('data-selected', String(selectedUom));
                        }
                    }
                }
                if (saleInfo && data.description) {
                    saleInfo.value = data.description;
                }
            })
            .catch(function () {});
    }

    // Load Sale Types from FBR table (fbr_trans_type_code)
    function loadSaleTypes() {
        if (!saleTypeSelect) return;

        var selected = saleTypeSelect.getAttribute('data-selected') || '';
        saleTypeSelect.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select Sale Type';
        saleTypeSelect.appendChild(placeholder);

        fetch("{{ route('products.sale-types') }}", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) return;

                data.items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;      // transactioN_TYPE_ID
                    opt.textContent = item.text; // transactioN_DESC
                    if (selected && String(selected) === String(item.id)) {
                        opt.selected = true;
                    }
                    saleTypeSelect.appendChild(opt);
                });
                // Add Product: default Sale Type to "Goods at standard rate (default)" when nothing saved
                if (!selected && saleTypeSelect.getAttribute('data-apply-default-sale-type') === '1') {
                    var want = 'Goods at standard rate (default)';
                    var wantLc = want.toLowerCase();
                    var opts = saleTypeSelect.querySelectorAll('option');
                    for (var i = 0; i < opts.length; i++) {
                        var opt = opts[i];
                        if (!opt.value) continue;
                        var label = (opt.textContent || '').trim();
                        var ll = label.toLowerCase();
                        if (ll === wantLc || (ll.indexOf('goods at standard rate') !== -1 && ll.indexOf('default') !== -1)) {
                            opt.selected = true;
                            saleTypeSelect.setAttribute('data-selected', opt.value);
                            break;
                        }
                    }
                }
                // After Sale Types load, if one is selected, load Sale Type To Rate options
                var selVal = saleTypeSelect.value;
                if (selVal) {
                    loadSaleTypeToRates(selVal);
                }
            })
            .catch(function () {});
    }

    // Load Sale Type To Rate options from fbr_sale_type_to_rate (filtered by transactioN_TYPE_ID)
    function loadSaleTypeToRates(transactionTypeId) {
        if (!saleTypeToRateSelect) return;

        var selected = saleTypeToRateSelect.getAttribute('data-selected') || '';
        saleTypeToRateSelect.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select Sale Type To Rate';
        saleTypeToRateSelect.appendChild(placeholder);

        if (!transactionTypeId) {
            return;
        }

        fetch("{{ route('products.sale-type-to-rates') }}?transaction_type_id=" + encodeURIComponent(transactionTypeId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) return;

                data.items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;       // ratE_ID
                    opt.textContent = item.text; // ratE_DESC
                    if (selected && String(selected) === String(item.id)) {
                        opt.selected = true;
                    }
                    saleTypeToRateSelect.appendChild(opt);
                });
                // After Sale Type To Rate options load, if one is selected, load SRO Schedule
                var rateVal = saleTypeToRateSelect.value;
                if (rateVal && sroScheduleSelect) {
                    loadSroSchedules(rateVal);
                }
            })
            .catch(function () {});
    }

    // Load SRO Schedule options from fbr_sro_schedule (filtered by ratE_ID)
    function loadSroSchedules(rateId) {
        if (!sroScheduleSelect) return;

        var selected = sroScheduleSelect.getAttribute('data-selected') || '';
        sroScheduleSelect.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select SRO Schedule';
        sroScheduleSelect.appendChild(placeholder);

        if (!rateId) {
            return;
        }

        fetch("{{ route('products.sro-schedules') }}?rate_id=" + encodeURIComponent(rateId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) return;

                data.items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;       // srO_ID
                    opt.textContent = item.text; // srO_DESC
                    if (selected && String(selected) === String(item.id)) {
                        opt.selected = true;
                    }
                    sroScheduleSelect.appendChild(opt);
                });
                // After SRO Schedule options load, if one is selected, load SRO Item
                var sroVal = sroScheduleSelect.value;
                if (sroVal && sroItemSelect) {
                    loadSroItems(sroVal);
                }
            })
            .catch(function () {});
    }

    // Load SRO Item options from fbr_sro_item (filtered by srO_ID)
    function loadSroItems(sroId) {
        if (!sroItemSelect) return;

        var selected = sroItemSelect.getAttribute('data-selected') || '';
        sroItemSelect.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select SRO Item';
        sroItemSelect.appendChild(placeholder);

        if (!sroId) {
            return;
        }

        fetch("{{ route('products.sro-items') }}?sro_id=" + encodeURIComponent(sroId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) return;

                data.items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;       // srO_ITEM_ID
                    opt.textContent = item.text; // srO_ITEM_DESC
                    if (selected && String(selected) === String(item.id)) {
                        opt.selected = true;
                    }
                    sroItemSelect.appendChild(opt);
                });
            })
            .catch(function () {});
    }

    if (hsInput) {
        var debounceTimer;
        hsInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchHsCodeInfo, 400);
        });
        hsInput.addEventListener('blur', function () {
            clearTimeout(debounceTimer);
            fetchHsCodeInfo();
        });
        hsInput.addEventListener('change', function () {
            clearTimeout(debounceTimer);
            fetchHsCodeInfo();
        });
    }

    if (uomSelect) {
        uomSelect.addEventListener('change', function () {
            uomSelect.setAttribute('data-selected', uomSelect.value || '');
        });
    }

    if (saleTypeSelect) {
        loadSaleTypes();
        saleTypeSelect.addEventListener('change', function () {
            var transactionTypeId = saleTypeSelect.value || '';
            if (saleTypeToRateSelect) {
                saleTypeToRateSelect.setAttribute('data-selected', '');
            }
            if (sroScheduleSelect) {
                sroScheduleSelect.setAttribute('data-selected', '');
                sroScheduleSelect.innerHTML = '';
                var opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Select Sale Type To Rate first';
                sroScheduleSelect.appendChild(opt);
            }
            if (sroItemSelect) {
                sroItemSelect.setAttribute('data-selected', '');
                sroItemSelect.innerHTML = '';
                var opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Select SRO Schedule first';
                sroItemSelect.appendChild(opt);
            }
            loadSaleTypeToRates(transactionTypeId);
        });
    }

    if (saleTypeToRateSelect) {
        saleTypeToRateSelect.addEventListener('change', function () {
            var rateId = saleTypeToRateSelect.value || '';
            if (sroScheduleSelect) {
                sroScheduleSelect.setAttribute('data-selected', '');
            }
            if (sroItemSelect) {
                sroItemSelect.setAttribute('data-selected', '');
                sroItemSelect.innerHTML = '';
                var opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Select SRO Schedule first';
                sroItemSelect.appendChild(opt);
            }
            loadSroSchedules(rateId);
        });
    }

    if (sroScheduleSelect) {
        sroScheduleSelect.addEventListener('change', function () {
            var sroId = sroScheduleSelect.value || '';
            if (sroItemSelect) {
                sroItemSelect.setAttribute('data-selected', '');
            }
            loadSroItems(sroId);
        });
    }

    // When editing, if HS Code already has a value, pre-load UOM list (and pre-select existing UOM)
    if (hsInput && uomSelect && hsInput.value && hsInput.value.trim() !== '') {
        fetchHsCodeInfo();
    }

    if (isFixedValueCheckbox && fixedNotifiedValueInput) {
        syncFixedValueUi();
        isFixedValueCheckbox.addEventListener('change', syncFixedValueUi);
    }
})();
</script>

