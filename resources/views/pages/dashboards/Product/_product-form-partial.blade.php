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
    $categories = $categories ?? collect();
    $units = $units ?? collect();
    $pfpSelectedCategory = old('Category', $product->CategoryID ?? $product->Category ?? '');
    $pfpSelectedUnit = old('Unit', $product->Unit ?? '');
@endphp

@once
<style>
/* Quick-add dialogs above parent product/invoice modals + dimmed backdrop */
.modal.pfp-quick-lookup-modal { z-index: 2060; }
.modal.pfp-quick-lookup-modal .modal-dialog {
    max-width: 400px;
}
.modal.pfp-quick-lookup-modal .modal-content {
    border: none;
    box-shadow: 0 0.75rem 2rem rgba(15, 23, 42, 0.28);
    border-radius: 0.5rem;
}
.modal.pfp-quick-lookup-modal .modal-header {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
}
.modal.pfp-quick-lookup-modal .modal-body {
    padding: 0.75rem 1rem;
}
.modal.pfp-quick-lookup-modal .modal-body .form-label {
    margin-bottom: 0.35rem;
    font-size: 0.875rem;
}
.modal.pfp-quick-lookup-modal .modal-footer {
    padding: 0.5rem 1rem;
    border-top: 1px solid var(--bs-border-color, #dee2e6);
}
.modal-backdrop.pfp-quick-lookup-backdrop { z-index: 2055; }
</style>
@endonce
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
                            @else<input type="text" name="Name" class="form-control @error('Name') is-invalid @enderror" value="{{ old('Name', $product->Name ?? '') }}" placeholder="Product name" required>@error('Name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Code</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->Code ?? '-' }}</div>
                            @else<input type="text" name="Code" class="form-control @error('Code') is-invalid @enderror" value="{{ old('Code', $product->Code ?? '') }}" placeholder="{{ $isCreate ? 'Auto-assigned if left blank' : '' }}" autocomplete="off">@error('Code')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Type</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->Type ?? '-' }}</div>
                            @else<select name="Type" class="form-select @error('Type') is-invalid @enderror">
                                <option value="">Select</option>
                                <option value="Product" {{ old('Type', $product->Type ?? '') == 'Product' ? 'selected' : '' }}>Product</option>
                                <option value="Service" {{ old('Type', $product->Type ?? '') == 'Service' ? 'selected' : '' }}>Service</option>
                            </select>@error('Type')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-6">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Category</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->CategoryName ?? $product->Category ?? '-' }}</div>
                            @else
                            <div class="d-flex gap-1 align-items-center">
                                <select name="Category" id="{{ $uid }}_Category" class="form-select @error('Category') is-invalid @enderror flex-grow-1" style="min-width:0;">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (string) $pfpSelectedCategory === (string) $cat->id ? 'selected' : '' }}>{{ $cat->CategoryName }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-primary pfp-line-add-entity-btn flex-shrink-0" id="{{ $uid }}_newCategoryBtn" title="New category" aria-label="New category"><i class="ri-add-line"></i></button>
                            </div>
                            @error('Category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Unit</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->UnitName ?? $product->Unit ?? '-' }}</div>
                            @else
                            <div class="d-flex gap-1 align-items-center">
                                <select name="Unit" id="{{ $uid }}_Unit" class="form-select @error('Unit') is-invalid @enderror flex-grow-1" style="min-width:0;">
                                    <option value="">Select Unit</option>
                                    @foreach($units as $un)
                                        <option value="{{ $un->id }}" {{ (string) $pfpSelectedUnit === (string) $un->id ? 'selected' : '' }}>{{ $un->UnitName }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-primary pfp-line-add-entity-btn flex-shrink-0" id="{{ $uid }}_newUnitBtn" title="New unit" aria-label="New unit"><i class="ri-add-line"></i></button>
                            </div>
                            @error('Unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Current Qty</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->CurrentQuantity ?? 0 }}</div>
                            @else<input type="number" step="0.000001" name="CurrentQuantity" class="form-control @error('CurrentQuantity') is-invalid @enderror" value="{{ old('CurrentQuantity', $product->CurrentQuantity ?? '0') }}">@error('CurrentQuantity')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                    </div>
                </div>

                {{-- Tab: Pricing --}}
                <div class="tab-pane fade _pfp-tab-pane" id="{{ $uid }}_pricing" role="tabpanel">
                    <h6 class="_pfp-section-title"><i class="fas fa-dollar-sign"></i> Pricing</h6>
                    <div class="row g-2">
                        <div class="col-md-4"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label _pfp-required' }}">Sale Price (PKR)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->SalePrice ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="SalePrice" class="form-control @error('SalePrice') is-invalid @enderror" value="{{ old('SalePrice', $product->SalePrice ?? '0.00') }}" required>@error('SalePrice')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-4"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Cost (PKR)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->Cost ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="Cost" class="form-control @error('Cost') is-invalid @enderror" value="{{ old('Cost', $product->Cost ?? '0.00') }}">@error('Cost')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-4"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Weight (kg)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->Weight ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="Weight" class="form-control @error('Weight') is-invalid @enderror" value="{{ old('Weight', $product->Weight ?? '0.00') }}">@error('Weight')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-3 d-flex align-items-center">
                            @php
                                $isFixedValue = (float)($product->TPRate ?? 0) > 0;
                                $isFixedValueOld = old('IsFixedValue');
                                $isFixedValueChecked = $isFixedValueOld !== null
                                    ? ((string)$isFixedValueOld === '1')
                                    : $isFixedValue;
                            @endphp
                            @if($isReadonly)
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" disabled {{ $isFixedValue ? 'checked' : '' }}>
                                    <label class="form-check-label ms-1">IsFixedRate</label>
                                </div>
                            @else
                                <input type="hidden" name="IsFixedValue" value="0">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="{{ $uid }}_is_fixed_value" name="IsFixedValue" value="1" {{ $isFixedValueChecked ? 'checked' : '' }}>
                                    <label class="form-check-label ms-1" for="{{ $uid }}_is_fixed_value">IsFixedRate</label>
                                </div>
                                @error('IsFixedValue')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-5">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">FixedNotifiedValueOrRetailPrice</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ number_format($product->TPRate ?? 0, 2) }}</div>
                            @else
                                <input type="number" step="0.000001" min="0" name="TPRate" id="{{ $uid }}_fixed_notified_value" class="form-control @error('TPRate') is-invalid @enderror" value="{{ old('TPRate', $product->TPRate ?? '0.00') }}">
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
                                       class="form-control @error('HsCode') is-invalid @enderror"
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
                                        class="form-select @error('UOM') is-invalid @enderror"
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
                                        class="form-select @error('SaleType') is-invalid @enderror"
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
                                        class="form-select @error('SaleTypeToRate') is-invalid @enderror"
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
                                        class="form-select @error('SROSchedule') is-invalid @enderror"
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
                                        class="form-select @error('SROItem') is-invalid @enderror"
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
                                          class="form-control @error('SaleInformation') is-invalid @enderror"
                                          rows="5"
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

{{-- Quick-add: category / unit (moved to body on open for correct backdrop + centering) --}}
<div class="modal fade pfp-quick-lookup-modal" id="{{ $uid }}_category_quick_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold mb-0">Add Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none py-2 mb-2 small" id="{{ $uid }}_category_quick_err"></div>
                <label class="form-label mb-1">Category name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" id="{{ $uid }}_category_quick_name" maxlength="100" placeholder="Category name" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="{{ $uid }}_category_quick_save">Save Category</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade pfp-quick-lookup-modal" id="{{ $uid }}_unit_quick_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold mb-0">Add Unit</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none py-2 mb-2 small" id="{{ $uid }}_unit_quick_err"></div>
                <label class="form-label mb-1">Unit name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" id="{{ $uid }}_unit_quick_name" maxlength="100" placeholder="e.g. PCS, KG" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="{{ $uid }}_unit_quick_save">Save Unit</button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
(function() {
    var pfpUid = @json($uid);
    var form = document.getElementById('product_modal_form');
    if (!form) return;

    function pfpCsrfToken() {
        var inp = form && form.querySelector('input[name="_token"]');
        if (inp && inp.value) return inp.value;
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? (m.getAttribute('content') || '') : '';
    }
    function pfpFetchHeaders() {
        var tok = pfpCsrfToken();
        return {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': tok
        };
    }
    function pfpElevateQuickModal(el) {
        if (!el || el.dataset.pfpElevated === '1') return;
        el._pfpParent = el.parentNode;
        el._pfpNext = el.nextSibling;
        document.body.appendChild(el);
        el.dataset.pfpElevated = '1';
    }
    function pfpRestoreQuickModal(el) {
        if (!el || el.dataset.pfpElevated !== '1') return;
        var p = el._pfpParent;
        if (p) {
            if (el._pfpNext && el._pfpNext.parentNode === p) p.insertBefore(el, el._pfpNext);
            else p.appendChild(el);
        }
        delete el._pfpParent;
        delete el._pfpNext;
        delete el.dataset.pfpElevated;
    }
    function pfpAppendSelectOption(select, id, label) {
        if (!select || id == null || id === '') return;
        var sid = String(id);
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value === sid) {
                select.value = sid;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }
        }
        var o = document.createElement('option');
        o.value = sid;
        o.textContent = label || sid;
        select.appendChild(o);
        select.value = sid;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
    function pfpShowQuickErr(el, msg) {
        if (!el) return;
        if (!msg) {
            el.classList.add('d-none');
            el.textContent = '';
            return;
        }
        el.textContent = msg;
        el.classList.remove('d-none');
    }
    var catSel = document.getElementById(pfpUid + '_Category');
    var unitSel = document.getElementById(pfpUid + '_Unit');
    var catModalEl = document.getElementById(pfpUid + '_category_quick_modal');
    var unitModalEl = document.getElementById(pfpUid + '_unit_quick_modal');
    var catErrEl = document.getElementById(pfpUid + '_category_quick_err');
    var unitErrEl = document.getElementById(pfpUid + '_unit_quick_err');
    var catNameInput = document.getElementById(pfpUid + '_category_quick_name');
    var unitNameInput = document.getElementById(pfpUid + '_unit_quick_name');

    [catModalEl, unitModalEl].forEach(function(el) {
        if (!el) return;
        el.addEventListener('show.bs.modal', function() {
            pfpElevateQuickModal(el);
            setTimeout(function() {
                var bd = document.querySelector('.modal-backdrop:last-of-type');
                if (bd) bd.classList.add('pfp-quick-lookup-backdrop');
            }, 0);
        });
        el.addEventListener('hidden.bs.modal', function() {
            pfpRestoreQuickModal(el);
        });
    });

    document.getElementById(pfpUid + '_newCategoryBtn')?.addEventListener('click', function() {
        pfpShowQuickErr(catErrEl, '');
        if (catNameInput) catNameInput.value = '';
        if (catModalEl) bootstrap.Modal.getOrCreateInstance(catModalEl, { backdrop: true, keyboard: true }).show();
    });
    document.getElementById(pfpUid + '_newUnitBtn')?.addEventListener('click', function() {
        pfpShowQuickErr(unitErrEl, '');
        if (unitNameInput) unitNameInput.value = '';
        if (unitModalEl) bootstrap.Modal.getOrCreateInstance(unitModalEl, { backdrop: true, keyboard: true }).show();
    });

    document.getElementById(pfpUid + '_category_quick_save')?.addEventListener('click', function() {
        var name = (catNameInput && catNameInput.value) ? catNameInput.value.trim() : '';
        if (!name) {
            pfpShowQuickErr(catErrEl, 'Category name is required.');
            return;
        }
        pfpShowQuickErr(catErrEl, '');
        var tok = pfpCsrfToken();
        if (!tok) {
            pfpShowQuickErr(catErrEl, 'Security token missing. Refresh the page and try again.');
            return;
        }
        var fd = new FormData();
        fd.append('CategoryName', name);
        fd.append('_token', tok);
        fetch(@json(route('products.lookups.category-store')), {
            method: 'POST',
            headers: pfpFetchHeaders(),
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(res) {
            return res.text().then(function(text) {
                var data = {};
                try { data = text ? JSON.parse(text) : {}; } catch (e) {}
                return { res: res, data: data };
            });
        })
        .then(function(pair) {
            if (pair.res.status === 419) {
                pfpShowQuickErr(catErrEl, 'Session expired (CSRF). Refresh the page and try again.');
                return;
            }
            if (pair.res.ok && pair.data && pair.data.status === 'success' && pair.data.item) {
                pfpAppendSelectOption(catSel, pair.data.item.id, pair.data.item.name);
                if (catModalEl) bootstrap.Modal.getInstance(catModalEl)?.hide();
                return;
            }
            var msg = (pair.data && pair.data.message) ? pair.data.message : 'Could not save.';
            if (pair.data && pair.data.errors && pair.data.errors.CategoryName && pair.data.errors.CategoryName[0]) {
                msg = pair.data.errors.CategoryName[0];
            }
            pfpShowQuickErr(catErrEl, msg);
        })
        .catch(function() { pfpShowQuickErr(catErrEl, 'Network error.'); });
    });

    document.getElementById(pfpUid + '_unit_quick_save')?.addEventListener('click', function() {
        var name = (unitNameInput && unitNameInput.value) ? unitNameInput.value.trim() : '';
        if (!name) {
            pfpShowQuickErr(unitErrEl, 'Unit name is required.');
            return;
        }
        pfpShowQuickErr(unitErrEl, '');
        var tok = pfpCsrfToken();
        if (!tok) {
            pfpShowQuickErr(unitErrEl, 'Security token missing. Refresh the page and try again.');
            return;
        }
        var fd = new FormData();
        fd.append('UnitName', name);
        fd.append('_token', tok);
        fetch(@json(route('products.lookups.unit-store')), {
            method: 'POST',
            headers: pfpFetchHeaders(),
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(res) {
            return res.text().then(function(text) {
                var data = {};
                try { data = text ? JSON.parse(text) : {}; } catch (e) {}
                return { res: res, data: data };
            });
        })
        .then(function(pair) {
            if (pair.res.status === 419) {
                pfpShowQuickErr(unitErrEl, 'Session expired (CSRF). Refresh the page and try again.');
                return;
            }
            if (pair.res.ok && pair.data && pair.data.status === 'success' && pair.data.item) {
                pfpAppendSelectOption(unitSel, pair.data.item.id, pair.data.item.name);
                if (unitModalEl) bootstrap.Modal.getInstance(unitModalEl)?.hide();
                return;
            }
            var msg = (pair.data && pair.data.message) ? pair.data.message : 'Could not save.';
            if (pair.data && pair.data.errors && pair.data.errors.UnitName && pair.data.errors.UnitName[0]) {
                msg = pair.data.errors.UnitName[0];
            }
            pfpShowQuickErr(unitErrEl, msg);
        })
        .catch(function() { pfpShowQuickErr(unitErrEl, 'Network error.'); });
    });

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

