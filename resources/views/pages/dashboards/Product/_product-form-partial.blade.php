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
@endphp

@once
<style>
    ._pfp-tabs-row { min-height: 380px; }
    ._pfp-nav { border-right: 1px solid var(--cm-border-color, #dee2e6); padding-right: 0; }
    ._pfp-nav .nav-link {
        border: none; border-radius: 6px; margin-bottom: 4px;
        color: var(--cm-text-main); font-weight: 500; padding: 10px 14px;
        display: flex; align-items: center; gap: 8px;
    }
    ._pfp-nav .nav-link:hover { background: var(--cm-bg-body, #f8f9fa); color: #6f42c1; }
    ._pfp-nav .nav-link.active { background: #6f42c1; color: #fff; }
    ._pfp-tab-pane { padding: 16px 0; }
    ._pfp-section-title { font-size: 14px; font-weight: 600; color: #6f42c1; margin-bottom: 12px; }
    .show-mode ._pfp-section-title { color: #17a2b8; }
    ._pfp-info-badge { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; color: #fff; font-size: 13px; }
    ._pfp-info-badge.edit-mode { background: linear-gradient(135deg, #6f42c1, #9b59b6); }
    ._pfp-info-badge.show-mode { background: linear-gradient(135deg, #17a2b8, #36b9cc); }
    ._pfp-detail-label { font-weight: 600; font-size: 12px; color: #6c757d; margin-bottom: 4px; }
    ._pfp-detail-value { font-size: 14px; padding: 7px 11px; background: var(--cm-input-bg); border: 1px solid var(--cm-border-color); border-radius: 4px; min-height: 36px; }
    ._pfp-required::after { content: " *"; color: #dc3545; }
    ._pfp-checkbox { display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 8px; }
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
    {{-- Hidden defaults for removed Account/Optional tabs --}}
    <input type="hidden" name="Status" value="{{ old('Status', $product->Status ?? '1') }}">
    <input type="hidden" name="IncomeAccount" value="{{ old('IncomeAccount', $product->IncomeAccount ?? '0') }}">
    <input type="hidden" name="StockAccount" value="{{ old('StockAccount', $product->StockAccount ?? '0') }}">
    <input type="hidden" name="ExpenseAccount" value="{{ old('ExpenseAccount', $product->ExpenseAccount ?? '0') }}">
    <input type="hidden" name="PreferredSupplier" value="{{ old('PreferredSupplier', $product->PreferredSupplier ?? '0') }}">
    <input type="hidden" name="OptionalField1" value="{{ old('OptionalField1', $product->OptionalField1 ?? '') }}">
    <input type="hidden" name="OptionalField2" value="{{ old('OptionalField2', $product->OptionalField2 ?? '') }}">
    <input type="hidden" name="OptionalField3" value="{{ old('OptionalField3', $product->OptionalField3 ?? '0') }}">
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
                        <div class="col-12">
                            <label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Sale Information</label>
                            @if($isReadonly)<div class="_pfp-detail-value">{{ $product->SaleInformation ?? '-' }}</div>
                            @else<textarea name="SaleInformation" class="form-control form-control-sm @error('SaleInformation') is-invalid @enderror" rows="2" placeholder="Sale info">{{ old('SaleInformation', $product->SaleInformation ?? '') }}</textarea>@error('SaleInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                    </div>
                </div>

                {{-- Tab: Pricing --}}
                <div class="tab-pane fade _pfp-tab-pane" id="{{ $uid }}_pricing" role="tabpanel">
                    <h6 class="_pfp-section-title"><i class="fas fa-dollar-sign"></i> Pricing</h6>
                    <div class="row g-2">
                        <div class="col-md-3"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label _pfp-required' }}">Sale Price (PKR)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->SalePrice ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="SalePrice" class="form-control form-control-sm @error('SalePrice') is-invalid @enderror" value="{{ old('SalePrice', $product->SalePrice ?? '0.00') }}" required>@error('SalePrice')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-3"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Cost (PKR)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->Cost ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="Cost" class="form-control form-control-sm @error('Cost') is-invalid @enderror" value="{{ old('Cost', $product->Cost ?? '0.00') }}">@error('Cost')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-3"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">TP Rate</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->TPRate ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="TPRate" class="form-control form-control-sm @error('TPRate') is-invalid @enderror" value="{{ old('TPRate', $product->TPRate ?? '0.00') }}">@error('TPRate')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-3"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Weight (kg)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ number_format($product->Weight ?? 0, 2) }}</div>@else<input type="number" step="0.000001" name="Weight" class="form-control form-control-sm @error('Weight') is-invalid @enderror" value="{{ old('Weight', $product->Weight ?? '0.00') }}">@error('Weight')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                    </div>
                </div>

                {{-- Tab: Tax --}}
                <div class="tab-pane fade _pfp-tab-pane" id="{{ $uid }}_tax" role="tabpanel">
                    <h6 class="_pfp-section-title"><i class="fas fa-receipt"></i> Tax</h6>
                    <div class="row g-2">
                        <div class="col-md-3"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Tax Code</label>@if($isReadonly)<div class="_pfp-detail-value">{{ $product->TaxCode ?? '-' }}</div>@else<input type="text" name="TaxCode" class="form-control form-control-sm @error('TaxCode') is-invalid @enderror" value="{{ old('TaxCode', $product->TaxCode ?? '') }}" placeholder="e.g. GST17">@error('TaxCode')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-3"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label _pfp-required' }}">Tax Rate (%)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ $product->TaxRate ?? 0 }}%</div>@else<input type="number" name="TaxRate" class="form-control form-control-sm @error('TaxRate') is-invalid @enderror" value="{{ old('TaxRate', $product->TaxRate ?? '0') }}" required>@error('TaxRate')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-3"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">NF Tax Rate (%)</label>@if($isReadonly)<div class="_pfp-detail-value">{{ $product->NFTaxRate ?? '-' }}</div>@else<input type="number" name="NFTaxRate" class="form-control form-control-sm @error('NFTaxRate') is-invalid @enderror" value="{{ old('NFTaxRate', $product->NFTaxRate ?? '') }}">@error('NFTaxRate')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-3"><label class="{{ $isReadonly ? '_pfp-detail-label' : 'form-label' }}">Tax Scheme</label>@if($isReadonly)<div class="_pfp-detail-value">{{ $product->TaxSchID ?? 4 }}</div>@else<input type="number" name="TaxSchID" class="form-control form-control-sm @error('TaxSchID') is-invalid @enderror" value="{{ old('TaxSchID', $product->TaxSchID ?? '4') }}">@error('TaxSchID')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
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
    var firstInvalid = document.querySelector('#product_modal_form .is-invalid');
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
})();
</script>
