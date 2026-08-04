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
    $defaultCategoryId = $defaultCategoryId ?? null;
    $defaultUnitId = $defaultUnitId ?? null;
    $catFromProduct = $product ? ($product->CategoryID ?? $product->Category ?? null) : null;
    $catFallback = ($catFromProduct !== null && $catFromProduct !== '' && (string) $catFromProduct !== '0')
        ? $catFromProduct
        : ($defaultCategoryId ?? '');
    $pfpSelectedCategory = old('Category', $catFallback);
    $unitFromProduct = $product ? ($product->Unit ?? null) : null;
    $unitFallback = ($unitFromProduct !== null && $unitFromProduct !== '' && (int) $unitFromProduct !== 0)
        ? $unitFromProduct
        : ($defaultUnitId ?? '');
    $pfpSelectedUnit = old('Unit', $unitFallback);
    $pfpHasLowStockColumn = \Illuminate\Support\Facades\Schema::hasColumn('tbl_product', 'LowStockLevel');
    $pfpStockStatus = ($product && ! $isCreate) ? (int) ($product->StockStatus ?? 1) : 1;
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
    font-size: 12px;
}
.modal.pfp-quick-lookup-modal .modal-footer {
    padding: 0.5rem 1rem;
    border-top: 1px solid var(--bs-border-color, #dee2e6);
}
.modal-backdrop.pfp-quick-lookup-backdrop { z-index: 2055; }

/* Product form modal: left tabs 20%, main pane 80% (md and up) */
@media (min-width: 768px) {
    ._pfp-tabs-row > ._pfp-nav-col {
        flex: 0 0 20%;
        max-width: 20%;
    }
    ._pfp-tabs-row > ._pfp-tab-pane-col {
        flex: 0 0 80%;
        max-width: 80%;
    }
}

/* Tax & SRO: four-column listbox picker (matches app form-control / form-select sizing) */
._pfp-tax-sro-split {
    --_pfp-tax-list-h: 92px; /* ~5 visible rows at 12px / 1.35 line-height */
}
._pfp-tax-sro-fields {
    padding: 0.75rem;
    background-color: var(--bs-tertiary-bg, #f8f9fa);
}
._pfp-tax-sro-listbox-area {
    background-color: var(--bs-body-bg, #fff);
}
._pfp-tax-picker-row {
    min-height: calc(var(--_pfp-tax-list-h) + 3.85rem); /* column header + search + list */
}
._pfp-tax-listbox-search {
    border-radius: 0;
    border-left: none;
    border-right: none;
    border-top: none;
}
._pfp-tax-listbox-col {
    border-right: 1px solid var(--bs-border-color, #dee2e6);
    display: flex;
    flex-direction: column;
    min-height: 0;
}
._pfp-tax-listbox-col:last-child {
    border-right: none;
}
@media (max-width: 991.98px) {
    ._pfp-tax-listbox-col:nth-child(2) {
        border-right: none;
    }
    ._pfp-tax-listbox-col:nth-child(1),
    ._pfp-tax-listbox-col:nth-child(2) {
        border-bottom: 1px solid var(--bs-border-color, #dee2e6);
    }
}
._pfp-tax-column-header {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-align: center;
    padding: 0.5rem 0.35rem;
    background-color: var(--bs-secondary-bg, #f8f9fa);
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
    color: var(--bs-secondary-color, #6c757d);
    flex-shrink: 0;
}
._pfp-tax-listbox {
    width: 100%;
    min-height: var(--_pfp-tax-list-h);
    font-size: 12px;
    line-height: 1.35;
    padding: 0.25rem 0.35rem;
    border: none;
    border-radius: 5px;
    box-shadow: none;
    accent-color: rgb(var(--primary-rgb));
}
._pfp-tax-listbox:focus {
    box-shadow: inset 0 0 0 2px rgba(var(--primary-rgb), 0.35) !important;
    outline: 0;
}
._pfp-tax-listbox-search::selection,
._pfp-tax-listbox-search::-moz-selection {
    background-color: rgb(var(--primary-rgb));
    color: #fff;
}
/* Theme selection (override OS / WebKit blue when listbox is focused) */
._pfp-tax-listbox option:checked,
._pfp-tax-listbox:focus option:checked {
    background-color: rgb(var(--primary-rgb)) !important;
    background-image: none !important;
    color: #fff !important;
    -webkit-text-fill-color: #fff !important;
    font-weight: 500;
}
._pfp-tax-listbox option:hover {
    background-color: var(--bs-secondary-bg, #e9ecef);
}
._pfp-tax-listbox option:checked:hover,
._pfp-tax-listbox:focus option:checked:hover {
    background-color: rgb(var(--primary-rgb)) !important;
    background-image: none !important;
    color: #fff !important;
    -webkit-text-fill-color: #fff !important;
}
._pfp-tax-listbox option:active,
._pfp-tax-listbox:focus option:active {
    background-color: rgb(var(--primary-rgb)) !important;
    background-image: none !important;
    color: #fff !important;
    -webkit-text-fill-color: #fff !important;
}
._pfp-tax-listbox option:focus,
._pfp-tax-listbox:focus option:focus {
    background-color: rgb(var(--primary-rgb)) !important;
    background-image: none !important;
    color: #fff !important;
    -webkit-text-fill-color: #fff !important;
}
/* Empty value: hidden placeholder (not counted as a visible row in supporting UAs) */
._pfp-tax-listbox option[data-pfp-placeholder="1"] {
    display: none;
}
._pfp-inventory-hint {
    font-size: 11px;
    color: var(--bs-secondary-color, #6c757d);
    margin-top: 0.2rem;
    line-height: 1.35;
}
._pfp-readonly-qty.form-control[readonly] {
    background-color: var(--bs-tertiary-bg, #f8f9fa);
    cursor: default;
}
</style>
@endonce
@if(!$isCreate && $product)
    @if(!$inModal)
<div class="_pfp-info-badge {{ $isReadonly ? 'show-mode' : 'edit-mode' }}">
    <div class="row align-items-center g-2">
        <div class="col-auto"><strong><i class="fas fa-id-badge"></i> ID:</strong> #{{ $product->ID }}</div>
        <div class="col-auto"><strong><i class="fas fa-barcode"></i> Code:</strong> {{ $product->Code ?? 'N/A' }}</div>
        <div class="col-auto"><strong><i class="fas fa-tag"></i> Price:</strong> PKR {{ number_format($product->SalePrice ?? 0, 2) }}</div>
        <div class="col-auto"><strong><i class="fas fa-boxes"></i> Qty:</strong> {{ number_format((float) ($product->CurrentQuantity ?? 0), 4) }}</div>
        <div class="col-auto">
            <strong><i class="fas fa-circle"></i> Stock:</strong>
            @if($pfpStockStatus === 1)
                <span class="stock-badge stock-in">In Stock</span>
            @elseif($pfpStockStatus === 0)
                <span class="stock-badge stock-low">Low Stock</span>
            @else
                <span class="stock-badge stock-out">Out of Stock</span>
            @endif
        </div>
    </div>
</div>
    @elseif($inModal)
    <div class="_pfp-info-badge {{ $isReadonly ? 'show-mode' : 'edit-mode' }} mb-2">
        <div class="row align-items-center g-2">
            <div class="col-auto"><strong><i class="fas fa-id-badge"></i> ID:</strong> #{{ $product->ID }}</div>
            <div class="col-auto"><strong><i class="fas fa-barcode"></i> Code:</strong> {{ $product->Code ?? 'N/A' }}</div>
            <div class="col-auto"><strong><i class="fas fa-boxes"></i> Qty:</strong> {{ number_format((float) ($product->CurrentQuantity ?? 0), 4) }}</div>
            <div class="col-auto">
                <strong><i class="fas fa-circle"></i> Stock:</strong>
                @if($pfpStockStatus === 1)
                    <span class="stock-badge stock-in">In Stock</span>
                @elseif($pfpStockStatus === 0)
                    <span class="stock-badge stock-low">Low Stock</span>
                @else
                    <span class="stock-badge stock-out">Out of Stock</span>
                @endif
            </div>
        </div>
    </div>
    @if(!$isCreate)
    <div class="d-flex justify-content-end gap-2 mb-2">
        @if($isReadonly)
            <button type="button" class="btn btn-light btn-sm fw-bold text-primary" onclick="openProductModal('edit', {{ $product->ID }})">
                <i class="fas fa-edit"></i> Edit Product
            </button>
        @endif
        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center" onclick="showAuditHistory('PRODUCT', {{ $product->ID }})">
            <i class="ri-history-line me-1"></i> Audit History
        </button>
    </div>
    @endif
@endif
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
        <div class="col-12 _pfp-nav-col _pfp-nav">
            <nav class="nav flex-column" role="tablist">
                <a class="nav-link active" data-bs-toggle="tab" href="#{{ $uid }}_basic" role="tab"><i class="fas fa-box-open"></i> Basic</a>
                <a class="nav-link" data-bs-toggle="tab" href="#{{ $uid }}_pricing" role="tab"><i class="fas fa-dollar-sign"></i> Pricing</a>
                <a class="nav-link" data-bs-toggle="tab" href="#{{ $uid }}_tax" role="tab"><i class="fas fa-receipt"></i> Tax &amp; SRO</a>
            </nav>
        </div>
        <div class="col-12 _pfp-tab-pane-col">
            <div class="tab-content">
                {{-- Tab: Basic --}}
                <div class="tab-pane fade show active _pfp-tab-pane" id="{{ $uid }}_basic" role="tabpanel">
                    <h6 class="_pfp-section-title"><i class="fas fa-box-open"></i> Basic Information</h6>
                    <div class="_pfp-tab-panel-shell border rounded overflow-hidden">
                        <div class="_pfp-tab-panel-body">
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
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">
                                {{ $isCreate ? 'Opening Qty' : 'Current Qty' }}
                            </label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ number_format((float) ($product->CurrentQuantity ?? 0), 4) }}</div>
                            @elseif($isCreate)
                                <input type="number" step="0.000001" min="0" name="CurrentQuantity"
                                       class="form-control @error('CurrentQuantity') is-invalid @enderror"
                                       value="{{ old('CurrentQuantity', $product->CurrentQuantity ?? '0') }}">
                                <div class="_pfp-inventory-hint">Initial stock on hand (optional).</div>
                                @error('CurrentQuantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @else
                                <input type="number" step="0.000001"
                                       class="form-control _pfp-readonly-qty"
                                       value="{{ number_format((float) ($product->CurrentQuantity ?? 0), 6, '.', '') }}"
                                       readonly tabindex="-1" aria-readonly="true">
                                <input type="hidden" name="CurrentQuantity" value="{{ old('CurrentQuantity', $product->CurrentQuantity ?? '0') }}">
                                <div class="_pfp-inventory-hint">Updated by purchases.</div>
                            @endif
                        </div>
                        @if($pfpHasLowStockColumn)
                        <div class="col-md-3">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Low Stock Level</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ number_format((float) ($product->LowStockLevel ?? 0), 4) }}</div>
                            @else
                                <input type="number" step="0.000001" min="0" name="LowStockLevel"
                                       class="form-control @error('LowStockLevel') is-invalid @enderror"
                                       value="{{ old('LowStockLevel', $product->LowStockLevel ?? '0') }}"
                                       placeholder="0">
                                <div class="_pfp-inventory-hint">Alert when qty falls below this level.</div>
                                @error('LowStockLevel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        @endif
                    </div>
                        </div>
                    </div>
                </div>

                {{-- Tab: Pricing --}}
                <div class="tab-pane fade _pfp-tab-pane" id="{{ $uid }}_pricing" role="tabpanel">
                    <h6 class="_pfp-section-title"><i class="fas fa-dollar-sign"></i> Pricing</h6>
                    <div class="_pfp-tab-panel-shell border rounded overflow-hidden">
                        <div class="_pfp-tab-panel-body">
                    <div class="row g-2">
                        <div class="col-md-4"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label _pfp-required' }}">Sale Price (PKR)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->SalePrice ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="SalePrice" class="form-control @error('SalePrice') is-invalid @enderror" value="{{ old('SalePrice', $product->SalePrice ?? '0.00') }}" required>@error('SalePrice')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                                                <div class="col-md-4">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Last Purchase Cost (PKR)</label>
                            @if($isReadonly)
                                <div class="_pfp-detail-value">{{ number_format($product->Cost ?? 0, 2) }}</div>
                            @elseif($isCreate)
                                <div class="_pfp-detail-value">0.00</div>
                                <input type="hidden" name="Cost" value="0">
                                <div class="_pfp-inventory-hint">Set when you record purchases.</div>
                            @else
                                <input type="number" step="0.000001"
                                       class="form-control _pfp-readonly-qty"
                                       value="{{ number_format((float) ($product->Cost ?? 0), 6, '.', '') }}"
                                       readonly tabindex="-1" aria-readonly="true">
                                <input type="hidden" name="Cost" value="{{ old('Cost', $product->Cost ?? '0') }}">
                                <div class="_pfp-inventory-hint">Updated by purchases.</div>
                            @endif
                        </div>
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
                    </div>
                </div>

                {{-- Tab: Tax & SRO --}}
                <div class="tab-pane fade _pfp-tab-pane" id="{{ $uid }}_tax" role="tabpanel">
                    <h6 class="_pfp-section-title"><i class="fas fa-receipt"></i> Tax &amp; SRO</h6>
                    @if($isReadonly)
                        <div class="_pfp-tab-panel-shell border rounded overflow-hidden">
                            <div class="_pfp-tab-panel-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="_pfp-detail-label">HS Code</label>
                                <div class="_pfp-detail-value">{{ $product->HsCode ?? '-' }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="_pfp-detail-label">HsCode UOM</label>
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['uom'] ?? '—') : ($product->UOM ?? '-') }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="_pfp-detail-label">Custom Unit</label>
                                <div class="_pfp-detail-value">{{ $product->UnitName ?? $product->Unit ?? '-' }}</div>
                            </div>
                            <div class="col-12">
                                <label class="_pfp-detail-label">Description</label>
                                <div class="_pfp-detail-value">{{ $product->SaleInformation ?? '-' }}</div>
                            </div>
                            <div class="col-12"><hr class="my-2"></div>
                            <div class="col-md-3">
                                <label class="_pfp-detail-label">Sale type</label>
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['sale_type'] ?? '—') : ($product->SaleType ?? '-') }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="_pfp-detail-label">Sale type to rate</label>
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['sale_type_to_rate'] ?? '—') : ($product->SaleTypeToRate ?? '-') }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="_pfp-detail-label">SRO schedule</label>
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['sro_schedule'] ?? '—') : ($product->SROSchedule ?? '-') }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="_pfp-detail-label">SRO item</label>
                                <div class="_pfp-detail-value">{{ $taxDisplayLabels ? ($taxDisplayLabels['sro_item'] ?? '—') : ($product->SROItem ?? '-') }}</div>
                            </div>
                        </div>
                            </div>
                        </div>
                    @else
                        <div class="_pfp-tax-sro-split border rounded overflow-hidden">
                            {{-- Top: HS code, UOM, sale information (description) --}}
                            <div class="_pfp-tax-sro-fields border-bottom">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">HS Code</label>
                                        <input type="text"
                                               name="HsCode"
                                               class="form-control @error('HsCode') is-invalid @enderror"
                                               value="{{ old('HsCode', $product->HsCode ?? '') }}"
                                               placeholder="e.g. 8517.12">
                                        @error('HsCode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">HsCode UOM</label>
                                        <select name="UOM"
                                                class="form-select @error('UOM') is-invalid @enderror"
                                                data-selected="{{ old('UOM', $product->UOM ?? '') }}">
                                            <option value="">Select UOM</option>
                                        </select>
                                        @error('UOM')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Custom Unit</label>
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
                                        <div class="text-muted small mt-1">
                                            <i class="ri-information-line"></i> Unit selection affects the HS Code Qty (FBR Qty) column on invoices.
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea name="SaleInformation"
                                                  class="form-control @error('SaleInformation') is-invalid @enderror"
                                                  rows="4"
                                                  placeholder="Sale information / description">{{ old('SaleInformation', $product->SaleInformation ?? '') }}</textarea>
                                        @error('SaleInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            {{-- Bottom: four filterable listboxes (size 5 + scroll) --}}
                            <div class="_pfp-tax-sro-listbox-area">
                                <div class="row g-0 _pfp-tax-picker-row">
                                    <div class="col-6 col-lg-4 _pfp-tax-listbox-col">
                                        <div class="_pfp-tax-column-header">Sale type</div>
                                        <input type="search" class="form-control form-control-sm _pfp-tax-listbox-search" data-pfp-filter-select="SaleType" placeholder="Search…" autocomplete="off" aria-label="Search sale types">
                                        <select name="SaleType"
                                                class="form-select _pfp-tax-listbox @error('SaleType') is-invalid @enderror"
                                                size="5"
                                                aria-label="Sale type — choose from list or search"
                                                data-selected="{{ old('SaleType', $product->SaleType ?? '') }}"
                                                @if((string) old('SaleType', $product->SaleTypeID ?? $product->SaleType ?? '') === '')
                                                data-apply-default-sale-type="1"
                                                @endif>
                                            <option value="" hidden data-pfp-placeholder="1" selected title="Please select sale type">Please select sale type</option>
                                        </select>
                                        @error('SaleType')<div class="invalid-feedback d-block px-1 small">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-6 col-lg-3 _pfp-tax-listbox-col">
                                        <div class="_pfp-tax-column-header">Sale type to rate</div>
                                        <input type="search" class="form-control form-control-sm _pfp-tax-listbox-search" data-pfp-filter-select="SaleTypeToRate" placeholder="Search…" autocomplete="off" aria-label="Search sale type to rate">
                                        <select name="SaleTypeToRate"
                                                class="form-select _pfp-tax-listbox @error('SaleTypeToRate') is-invalid @enderror"
                                                size="5"
                                                aria-label="Sale type to rate — choose from list or search"
                                                data-selected="{{ old('SaleTypeToRate', $product->SaleTypeToRate ?? '') }}">
                                            <option value="" hidden data-pfp-placeholder="1" selected title="Please select sale type to rate">Please select sale type to rate</option>
                                        </select>
                                        @error('SaleTypeToRate')<div class="invalid-feedback d-block px-1 small">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-6 col-lg-3 _pfp-tax-listbox-col">
                                        <div class="_pfp-tax-column-header">SRO schedule</div>
                                        <input type="search" class="form-control form-control-sm _pfp-tax-listbox-search" data-pfp-filter-select="SROSchedule" placeholder="Search…" autocomplete="off" aria-label="Search SRO schedule">
                                        <select name="SROSchedule"
                                                class="form-select _pfp-tax-listbox @error('SROSchedule') is-invalid @enderror"
                                                size="5"
                                                aria-label="SRO schedule — choose from list or search"
                                                data-selected="{{ old('SROSchedule', $product->SROSchedule ?? '') }}">
                                            <option value="" hidden data-pfp-placeholder="1" selected title="Please select SRO schedule">Please select SRO schedule</option>
                                        </select>
                                        @error('SROSchedule')<div class="invalid-feedback d-block px-1 small">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-6 col-lg-2 _pfp-tax-listbox-col">
                                        <div class="_pfp-tax-column-header">SRO item</div>
                                        <input type="search" class="form-control form-control-sm _pfp-tax-listbox-search" data-pfp-filter-select="SROItem" placeholder="Search…" autocomplete="off" aria-label="Search SRO item">
                                        <select name="SROItem"
                                                class="form-select _pfp-tax-listbox @error('SROItem') is-invalid @enderror"
                                                size="5"
                                                aria-label="SRO item — choose from list or search"
                                                data-selected="{{ old('SROItem', $product->SROItem ?? '') }}">
                                            <option value="" hidden data-pfp-placeholder="1" selected title="Please select SRO item">Please select SRO item</option>
                                        </select>
                                        @error('SROItem')<div class="invalid-feedback d-block px-1 small">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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

    function pfpTaxAppendHiddenPlaceholder(selectEl, labelText) {
        if (!selectEl) return null;
        var o = document.createElement('option');
        o.value = '';
        o.textContent = labelText;
        o.setAttribute('data-pfp-placeholder', '1');
        o.setAttribute('hidden', 'hidden');
        if (labelText) o.setAttribute('title', labelText);
        selectEl.appendChild(o);
        return o;
    }
    function pfpTaxSelectHiddenPlaceholder(sel) {
        if (!sel) return;
        for (var i = 0; i < sel.options.length; i++) {
            var o = sel.options[i];
            if (o.getAttribute('data-pfp-placeholder') === '1') {
                o.selected = true;
            } else {
                o.selected = false;
            }
        }
    }
    function pfpTaxDeselectPlaceholder(sel) {
        if (!sel) return;
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].getAttribute('data-pfp-placeholder') === '1') {
                sel.options[i].selected = false;
            }
        }
    }

    /** SRO columns: no rate / no rows from API — show a visible "None" row (not the hidden listbox placeholder). */
    function pfpTaxFillSroListWithNone(sel) {
        if (!sel) return;
        if (sel.name) clearTaxListboxFilter(sel.name);
        sel.innerHTML = '';
        var nopt = document.createElement('option');
        nopt.value = '';
        nopt.textContent = 'None';
        nopt.setAttribute('data-pfp-none-visible', '1');
        sel.appendChild(nopt);
        nopt.selected = true;
        sel.setAttribute('data-selected', '');
        if (sel.classList && sel.classList.contains('_pfp-tax-listbox')) {
            pfpTaxMoveSelectedOptionFirst(sel);
            scrollTaxListboxToSelected(sel);
            pfpTaxPaintSelectedOption(sel);
        }
    }

    function pfpTaxListboxThemeRgbBg() {
        try {
            var raw = getComputedStyle(document.documentElement).getPropertyValue('--primary-rgb').trim();
            if (raw) return 'rgb(' + raw + ')';
        } catch (e) {}
        return 'rgb(111, 66, 193)';
    }

    function pfpTaxOptionValuesMatch(a, b) {
        return String(a != null ? a : '').trim() === String(b != null ? b : '').trim();
    }

    /** Move the chosen row to the top of the list (after hidden placeholder) so it shows first in the listbox. */
    function pfpTaxMoveSelectedOptionFirst(sel) {
        if (!sel || !sel.classList || !sel.classList.contains('_pfp-tax-listbox')) return;
        var selectedOpt = null;
        var si = sel.selectedIndex;
        if (si >= 0 && sel.options[si] && sel.options[si].getAttribute('data-pfp-placeholder') !== '1') {
            selectedOpt = sel.options[si];
        }
        if (!selectedOpt) {
            var selVal = String(sel.value || '').trim();
            for (var i = 0; i < sel.options.length; i++) {
                var opt = sel.options[i];
                if (opt.getAttribute('data-pfp-placeholder') === '1') continue;
                if (selVal !== '' && pfpTaxOptionValuesMatch(selVal, opt.value)) {
                    selectedOpt = opt;
                    break;
                }
                if (!selVal && opt.getAttribute('data-pfp-none-visible') === '1' && opt.selected) {
                    selectedOpt = opt;
                    break;
                }
            }
        }
        if (!selectedOpt) return;
        var insertBefore = null;
        for (var j = 0; j < sel.options.length; j++) {
            var o = sel.options[j];
            if (o.getAttribute('data-pfp-placeholder') === '1') continue;
            if (o !== selectedOpt) {
                insertBefore = o;
                break;
            }
        }
        if (insertBefore) {
            sel.insertBefore(selectedOpt, insertBefore);
        } else {
            var first = sel.options[0];
            if (first && first !== selectedOpt) {
                sel.insertBefore(selectedOpt, first);
            }
        }
        selectedOpt.selected = true;
        sel.value = String(selectedOpt.value);
    }

    /** Native listbox UAs often ignore option:checked CSS; inline + !important matches primary button fill */
    var PFP_TAX_LB_ROW_FG = '#fff';
    function pfpTaxPaintSelectedOption(sel) {
        if (!sel || !sel.classList || !sel.classList.contains('_pfp-tax-listbox')) return;
        var selVal = String(sel.value || '');
        for (var i = 0; i < sel.options.length; i++) {
            var opt = sel.options[i];
            if (opt.getAttribute('data-pfp-placeholder') === '1') {
                opt.style.removeProperty('background-color');
                opt.style.removeProperty('color');
                opt.style.removeProperty('-webkit-text-fill-color');
                continue;
            }
            opt.style.removeProperty('background-color');
            opt.style.removeProperty('color');
            opt.style.removeProperty('-webkit-text-fill-color');
            var isSelected = (selVal !== '' && String(opt.value) === selVal)
                || (selVal === '' && opt.getAttribute('data-pfp-none-visible') === '1'
                    && sel.selectedIndex >= 0 && sel.options[sel.selectedIndex] === opt);
            if (isSelected) {
                opt.style.setProperty('background-color', pfpTaxListboxThemeRgbBg(), 'important');
                opt.style.setProperty('color', PFP_TAX_LB_ROW_FG, 'important');
                opt.style.setProperty('-webkit-text-fill-color', PFP_TAX_LB_ROW_FG, 'important');
            }
        }
    }

    function taxListboxFilterInput(name) {
        return form.querySelector('input._pfp-tax-listbox-search[data-pfp-filter-select="' + name + '"]');
    }
    function clearTaxListboxFilter(name) {
        var inp = taxListboxFilterInput(name);
        if (inp) inp.value = '';
    }
    function applyTaxListboxFilter(sel) {
        if (!sel) return;
        var inp = taxListboxFilterInput(sel.name);
        var q = (inp && inp.value) ? String(inp.value).trim().toLowerCase() : '';
        var selVal = String(sel.value || '');
        for (var i = 0; i < sel.options.length; i++) {
            var opt = sel.options[i];
            if (opt.getAttribute('data-pfp-placeholder') === '1') {
                opt.hidden = true;
                continue;
            }
            var displayLabel = ((opt.textContent || '') + '').trim();
            var label = displayLabel.toLowerCase();
            var ov = String(opt.value || '');
            var show = q === '' || label.indexOf(q) !== -1;
            if (ov !== '' && ov === selVal) show = true;
            if (ov === '') {
                show = q === '' || label.indexOf(q) !== -1;
                if (opt.getAttribute('data-pfp-none-visible') === '1' && sel.selectedIndex >= 0 && sel.options[sel.selectedIndex] === opt) {
                    show = true;
                }
            }
            opt.hidden = !show;
            if (displayLabel) {
                opt.setAttribute('title', displayLabel);
            } else {
                opt.removeAttribute('title');
            }
        }
    }
    function bindTaxListboxSearch(name, sel) {
        var inp = taxListboxFilterInput(name);
        if (!inp || !sel) return;
        function run() {
            applyTaxListboxFilter(sel);
            flashTaxListboxSelection(sel);
        }
        inp.addEventListener('input', run);
        inp.addEventListener('search', run);
    }

    function flashTaxListboxSelection(sel) {
        if (!sel) return;
        applyTaxListboxFilter(sel);
        var wantRaw = sel.getAttribute('data-selected');
        if (wantRaw === null || wantRaw === undefined) wantRaw = sel.value || '';
        var want = String(wantRaw).trim();
        if (want === '') {
            var nv = null;
            for (var ni = 0; ni < sel.options.length; ni++) {
                if (sel.options[ni].getAttribute('data-pfp-none-visible') === '1') {
                    nv = sel.options[ni];
                    break;
                }
            }
            if (nv) {
                nv.selected = true;
                sel.value = nv.value;
                pfpTaxDeselectPlaceholder(sel);
                pfpTaxMoveSelectedOptionFirst(sel);
                applyTaxListboxFilter(sel);
                scrollTaxListboxToSelected(sel);
                pfpTaxPaintSelectedOption(sel);
                return;
            }
            pfpTaxSelectHiddenPlaceholder(sel);
            scrollTaxListboxToSelected(sel);
            pfpTaxPaintSelectedOption(sel);
            return;
        }
        var found = false;
        for (var i = 0; i < sel.options.length; i++) {
            var o = sel.options[i];
            if (o.getAttribute('data-pfp-placeholder') === '1') continue;
            if (pfpTaxOptionValuesMatch(want, o.value)) {
                sel.selectedIndex = i;
                sel.value = String(o.value);
                found = true;
                break;
            }
        }
        if (!found) {
            pfpTaxSelectHiddenPlaceholder(sel);
        } else {
            pfpTaxDeselectPlaceholder(sel);
        }
        pfpTaxMoveSelectedOptionFirst(sel);
        applyTaxListboxFilter(sel);
        scrollTaxListboxToSelected(sel);
        pfpTaxPaintSelectedOption(sel);
    }

    function scrollTaxListboxToSelected(sel) {
        if (!sel) return;
        requestAnimationFrame(function() {
            try {
                var i = sel.selectedIndex;
                if (i >= 0 && sel.options[i]) {
                    var o = sel.options[i];
                    if (o.getAttribute('data-pfp-placeholder') !== '1') {
                        o.scrollIntoView({ block: 'nearest' });
                    }
                }
            } catch (e) {}
            pfpTaxPaintSelectedOption(sel);
        });
    }

    function bindTaxListboxRowPaint(sel) {
        if (!sel || sel.dataset.pfpTaxRowPaint === '1') return;
        sel.dataset.pfpTaxRowPaint = '1';
        function paint() {
            requestAnimationFrame(function() { pfpTaxPaintSelectedOption(sel); });
        }
        ['mouseup', 'keyup', 'blur', 'change'].forEach(function(ev) { sel.addEventListener(ev, paint); });
    }

    bindTaxListboxSearch('SaleType', saleTypeSelect);
    bindTaxListboxSearch('SaleTypeToRate', saleTypeToRateSelect);
    bindTaxListboxSearch('SROSchedule', sroScheduleSelect);
    bindTaxListboxSearch('SROItem', sroItemSelect);
    bindTaxListboxRowPaint(saleTypeSelect);
    bindTaxListboxRowPaint(saleTypeToRateSelect);
    bindTaxListboxRowPaint(sroScheduleSelect);
    bindTaxListboxRowPaint(sroItemSelect);

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

    function fetchHsCodeInfo(updateDescription) {
        if (updateDescription === undefined) updateDescription = true;
        var code = (hsInput && hsInput.value) ? hsInput.value.trim() : '';
        if (uomSelect) clearUomOptions();

        if (!code) {
            if (saleInfo && updateDescription) saleInfo.value = '';
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
                if (saleInfo && data.description && updateDescription) {
                    saleInfo.value = data.description;
                }
            })
            .catch(function () {});
    }

    function pfpFindDefaultSaleTypeOption(sel) {
        if (!sel) return null;
        var want = 'Goods at standard rate (default)';
        var wantLc = want.toLowerCase();
        var opts = sel.querySelectorAll('option');
        for (var i = 0; i < opts.length; i++) {
            var opt = opts[i];
            if (!opt.value || opt.getAttribute('data-pfp-placeholder') === '1') continue;
            var label = (opt.textContent || '').trim();
            var ll = label.toLowerCase();
            if (ll === wantLc || (ll.indexOf('goods at standard rate') !== -1 && ll.indexOf('default') !== -1)) {
                return opt;
            }
        }
        return null;
    }

    function pfpApplyDefaultSaleTypeIfNeeded(sel) {
        if (!sel) return false;
        if (sel.getAttribute('data-apply-default-sale-type') !== '1') return false;
        var saved = String(sel.getAttribute('data-selected') || '').trim();
        if (saved) return false;
        if (String(sel.value || '').trim()) return false;
        var opt = pfpFindDefaultSaleTypeOption(sel);
        if (!opt) return false;
        opt.selected = true;
        sel.value = String(opt.value);
        sel.setAttribute('data-selected', String(opt.value));
        pfpTaxDeselectPlaceholder(sel);
        flashTaxListboxSelection(sel);
        return true;
    }

    // Load Sale Types from FBR table (fbr_trans_type_code)
    function loadSaleTypes() {
        if (!saleTypeSelect) return;

        clearTaxListboxFilter('SaleType');
        var selected = String(saleTypeSelect.getAttribute('data-selected') || '').trim();
        saleTypeSelect.innerHTML = '';
        pfpTaxAppendHiddenPlaceholder(saleTypeSelect, 'Please select sale type');
        pfpTaxSelectHiddenPlaceholder(saleTypeSelect);

        fetch("{{ route('products.sale-types') }}", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    flashTaxListboxSelection(saleTypeSelect);
                    return;
                }

                data.items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && pfpTaxOptionValuesMatch(selected, item.id)) {
                        opt.selected = true;
                    }
                    saleTypeSelect.appendChild(opt);
                });

                if (!selected) {
                    pfpApplyDefaultSaleTypeIfNeeded(saleTypeSelect);
                }

                var selVal = String(saleTypeSelect.value || '').trim();
                if (!selVal && selected) {
                    for (var sti = 0; sti < saleTypeSelect.options.length; sti++) {
                        var sto = saleTypeSelect.options[sti];
                        if (sto.getAttribute('data-pfp-placeholder') === '1') continue;
                        if (pfpTaxOptionValuesMatch(selected, sto.value)) {
                            saleTypeSelect.selectedIndex = sti;
                            saleTypeSelect.value = String(sto.value);
                            selVal = String(sto.value);
                            break;
                        }
                    }
                }

                if (selVal) {
                    saleTypeSelect.setAttribute('data-selected', selVal);
                    loadSaleTypeToRates(selVal);
                }

                if (!saleTypeSelect.value) {
                    pfpTaxSelectHiddenPlaceholder(saleTypeSelect);
                } else {
                    pfpTaxDeselectPlaceholder(saleTypeSelect);
                }
                flashTaxListboxSelection(saleTypeSelect);
            })
            .catch(function () {});
    }

    // Load Sale Type To Rate options from fbr_sale_type_to_rate (filtered by transactioN_TYPE_ID)
    function loadSaleTypeToRates(transactionTypeId) {
        if (!saleTypeToRateSelect) return;

        clearTaxListboxFilter('SaleTypeToRate');
        var selected = String(saleTypeToRateSelect.getAttribute('data-selected') || '').trim();
        saleTypeToRateSelect.innerHTML = '';
        pfpTaxAppendHiddenPlaceholder(saleTypeToRateSelect, 'Please select sale type to rate');

        if (!transactionTypeId) {
            pfpTaxSelectHiddenPlaceholder(saleTypeToRateSelect);
            flashTaxListboxSelection(saleTypeToRateSelect);
            return;
        }

        fetch("{{ route('products.sale-type-to-rates') }}?transaction_type_id=" + encodeURIComponent(transactionTypeId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    flashTaxListboxSelection(saleTypeToRateSelect);
                    return;
                }

                data.items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && pfpTaxOptionValuesMatch(selected, item.id)) {
                        opt.selected = true;
                    }
                    saleTypeToRateSelect.appendChild(opt);
                });

                var rateVal = String(saleTypeToRateSelect.value || '').trim();
                if (!rateVal && selected) {
                    for (var ri = 0; ri < saleTypeToRateSelect.options.length; ri++) {
                        var ro = saleTypeToRateSelect.options[ri];
                        if (ro.getAttribute('data-pfp-placeholder') === '1') continue;
                        if (pfpTaxOptionValuesMatch(selected, ro.value)) {
                            saleTypeToRateSelect.selectedIndex = ri;
                            saleTypeToRateSelect.value = String(ro.value);
                            rateVal = String(ro.value);
                            break;
                        }
                    }
                }

                if (!rateVal && data.items.length === 1) {
                    var onlyId = String(data.items[0].id != null ? data.items[0].id : '').trim();
                    saleTypeToRateSelect.setAttribute('data-selected', onlyId);
                }

                rateVal = String(saleTypeToRateSelect.value || '').trim();
                if (!rateVal && saleTypeToRateSelect.getAttribute('data-selected')) {
                    flashTaxListboxSelection(saleTypeToRateSelect);
                    rateVal = String(saleTypeToRateSelect.value || '').trim();
                }

                if (rateVal && sroScheduleSelect) {
                    loadSroSchedules(rateVal);
                }
                if (saleTypeToRateSelect.value) {
                    saleTypeToRateSelect.setAttribute('data-selected', String(saleTypeToRateSelect.value));
                }
                if (!saleTypeToRateSelect.value) {
                    pfpTaxSelectHiddenPlaceholder(saleTypeToRateSelect);
                } else {
                    pfpTaxDeselectPlaceholder(saleTypeToRateSelect);
                }
                flashTaxListboxSelection(saleTypeToRateSelect);
            })
            .catch(function () {});
    }

    // Load SRO Schedule options from fbr_sro_schedule (filtered by ratE_ID) — mirrors invoice line Tax & SRO
    function loadSroSchedules(rateId) {
        if (!sroScheduleSelect) return;

        clearTaxListboxFilter('SROSchedule');
        var selected = String(sroScheduleSelect.getAttribute('data-selected') || '').trim();
        sroScheduleSelect.innerHTML = '';

        function finishScheduleNone() {
            pfpTaxFillSroListWithNone(sroScheduleSelect);
            loadSroItems('');
        }

        if (!rateId) {
            finishScheduleNone();
            return;
        }

        pfpTaxAppendHiddenPlaceholder(sroScheduleSelect, 'Please select SRO schedule');
        pfpTaxSelectHiddenPlaceholder(sroScheduleSelect);

        fetch("{{ route('products.sro-schedules') }}?rate_id=" + encodeURIComponent(rateId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    finishScheduleNone();
                    return;
                }
                if (!data.items.length) {
                    finishScheduleNone();
                    return;
                }

                data.items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && pfpTaxOptionValuesMatch(selected, item.id)) {
                        opt.selected = true;
                    }
                    sroScheduleSelect.appendChild(opt);
                });
                pfpTaxDeselectPlaceholder(sroScheduleSelect);
                flashTaxListboxSelection(sroScheduleSelect);
                var v = String(sroScheduleSelect.value || '').trim();
                if (!v && selected) {
                    var wantSch = String(selected).trim();
                    for (var si = 0; si < sroScheduleSelect.options.length; si++) {
                        if (sroScheduleSelect.options[si].getAttribute('data-pfp-placeholder') === '1') continue;
                        if (pfpTaxOptionValuesMatch(wantSch, sroScheduleSelect.options[si].value)) {
                            sroScheduleSelect.selectedIndex = si;
                            sroScheduleSelect.value = String(sroScheduleSelect.options[si].value);
                            pfpTaxDeselectPlaceholder(sroScheduleSelect);
                            flashTaxListboxSelection(sroScheduleSelect);
                            v = String(sroScheduleSelect.value || '').trim();
                            break;
                        }
                    }
                }
                if (v) {
                    sroScheduleSelect.setAttribute('data-selected', v);
                    loadSroItems(v);
                } else {
                    loadSroItems('');
                }
            })
            .catch(function () { finishScheduleNone(); });
    }

    // Load SRO Item options from fbr_sro_item (filtered by srO_ID) — mirrors invoice line Tax & SRO
    function loadSroItems(sroId) {
        if (!sroItemSelect) return;

        clearTaxListboxFilter('SROItem');
        var selected = String(sroItemSelect.getAttribute('data-selected') || '').trim();
        sroItemSelect.innerHTML = '';

        if (!sroId) {
            pfpTaxFillSroListWithNone(sroItemSelect);
            return;
        }

        pfpTaxAppendHiddenPlaceholder(sroItemSelect, 'Please select SRO item');
        pfpTaxSelectHiddenPlaceholder(sroItemSelect);

        fetch("{{ route('products.sro-items') }}?sro_id=" + encodeURIComponent(sroId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    pfpTaxFillSroListWithNone(sroItemSelect);
                    return;
                }
                if (!data.items.length) {
                    pfpTaxFillSroListWithNone(sroItemSelect);
                    return;
                }

                data.items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && pfpTaxOptionValuesMatch(selected, item.id)) {
                        opt.selected = true;
                    }
                    sroItemSelect.appendChild(opt);
                });
                pfpTaxDeselectPlaceholder(sroItemSelect);
                flashTaxListboxSelection(sroItemSelect);
                if (!String(sroItemSelect.value || '').trim() && selected) {
                    var wantIt = String(selected).trim();
                    for (var ii = 0; ii < sroItemSelect.options.length; ii++) {
                        if (sroItemSelect.options[ii].getAttribute('data-pfp-placeholder') === '1') continue;
                        if (pfpTaxOptionValuesMatch(wantIt, sroItemSelect.options[ii].value)) {
                            sroItemSelect.selectedIndex = ii;
                            sroItemSelect.value = String(sroItemSelect.options[ii].value);
                            pfpTaxDeselectPlaceholder(sroItemSelect);
                            flashTaxListboxSelection(sroItemSelect);
                            break;
                        }
                    }
                }
                if (sroItemSelect.value) {
                    sroItemSelect.setAttribute('data-selected', String(sroItemSelect.value));
                }
            })
            .catch(function () { pfpTaxFillSroListWithNone(sroItemSelect); });
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
            clearTaxListboxFilter('SaleTypeToRate');
            clearTaxListboxFilter('SROSchedule');
            clearTaxListboxFilter('SROItem');
            saleTypeSelect.setAttribute('data-selected', saleTypeSelect.value || '');
            var transactionTypeId = saleTypeSelect.value || '';
            if (saleTypeToRateSelect) {
                saleTypeToRateSelect.setAttribute('data-selected', '');
            }
            if (sroScheduleSelect) {
                sroScheduleSelect.setAttribute('data-selected', '');
                sroScheduleSelect.innerHTML = '';
                pfpTaxAppendHiddenPlaceholder(sroScheduleSelect, 'Please select sale type to rate');
                pfpTaxSelectHiddenPlaceholder(sroScheduleSelect);
            }
            if (sroItemSelect) {
                sroItemSelect.setAttribute('data-selected', '');
                sroItemSelect.innerHTML = '';
                pfpTaxAppendHiddenPlaceholder(sroItemSelect, 'Please select SRO schedule');
                pfpTaxSelectHiddenPlaceholder(sroItemSelect);
            }
            loadSaleTypeToRates(transactionTypeId);
            flashTaxListboxSelection(saleTypeSelect);
        });
    }

    if (saleTypeToRateSelect) {
        saleTypeToRateSelect.addEventListener('change', function () {
            clearTaxListboxFilter('SROSchedule');
            clearTaxListboxFilter('SROItem');
            saleTypeToRateSelect.setAttribute('data-selected', saleTypeToRateSelect.value || '');
            var rateId = saleTypeToRateSelect.value || '';
            if (sroScheduleSelect) {
                sroScheduleSelect.setAttribute('data-selected', '');
            }
            if (sroItemSelect) {
                sroItemSelect.setAttribute('data-selected', '');
                sroItemSelect.innerHTML = '';
                pfpTaxAppendHiddenPlaceholder(sroItemSelect, 'Please select SRO schedule');
                pfpTaxSelectHiddenPlaceholder(sroItemSelect);
            }
            loadSroSchedules(rateId);
            flashTaxListboxSelection(saleTypeToRateSelect);
        });
    }

    if (sroScheduleSelect) {
        sroScheduleSelect.addEventListener('change', function () {
            clearTaxListboxFilter('SROItem');
            sroScheduleSelect.setAttribute('data-selected', sroScheduleSelect.value || '');
            var sroId = sroScheduleSelect.value || '';
            if (sroItemSelect) {
                sroItemSelect.setAttribute('data-selected', '');
            }
            loadSroItems(sroId);
            flashTaxListboxSelection(sroScheduleSelect);
        });
    }

    if (sroItemSelect) {
        sroItemSelect.addEventListener('change', function () {
            sroItemSelect.setAttribute('data-selected', sroItemSelect.value || '');
            flashTaxListboxSelection(sroItemSelect);
        });
    }

    var taxTabNav = document.querySelector('a[href="#' + pfpUid + '_tax"]');
    if (taxTabNav) {
        taxTabNav.addEventListener('shown.bs.tab', function() {
            flashTaxListboxSelection(saleTypeSelect);
            flashTaxListboxSelection(saleTypeToRateSelect);
            flashTaxListboxSelection(sroScheduleSelect);
            flashTaxListboxSelection(sroItemSelect);
        });
    }

    // When editing, pre-load UOM list from HS code but keep the saved description from the database.
    // Description is only refreshed when the user changes the HS code field.
    if (hsInput && uomSelect && hsInput.value && hsInput.value.trim() !== '') {
        fetchHsCodeInfo(@json($isEdit) ? false : true);
    }

    if (isFixedValueCheckbox && fixedNotifiedValueInput) {
        syncFixedValueUi();
        isFixedValueCheckbox.addEventListener('change', syncFixedValueUi);
    }
})();
</script>

