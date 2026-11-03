@php
    $mode = $mode ?? 'create';
    $isCreate = $mode === 'create';
    $isEdit = $mode === 'edit';
    $isShow = $mode === 'show';
    $isReadonly = $isShow;
    $purchase = $purchase ?? null;
    $items = $items ?? collect();
    $inModal = $inModal ?? false;
    $purchaseId = $purchase->ID ?? 0;
    $formAction = $isCreate
        ? route('purchases.save_transaction')
        : ($isEdit ? route('purchases.edit_transaction', $purchaseId) : '#');
    $uid = 'pfp_' . $mode . '_' . ($purchaseId ?: 'new');
    $selClass = $inModal ? 'form-select' : 'form-select form-select-sm';
    $pfpEmptyColspan = $isReadonly ? ($inModal ? 14 : 16) : ($inModal ? 15 : 17);
    $productsJson = collect($products ?? [])->map(fn ($p) => [
        'id' => (int) $p->ID,
        'name' => $p->ProductName ?? ('Product #' . $p->ID),
        'cost' => (float) ($p->Cost ?? 0),
        'rate' => (float) ($p->Cost ?? 0),
        'gst' => (float) ($p->GstPercentage ?? 0),
        'hscode' => $p->HsCode ?? '',
        'sale_information' => $p->SaleInformation ?? '',
        'uom_id' => $p->UomID ?? '',
        'uom_label' => trim((string) ($p->UomName ?? '')),
        'sale_type_id' => $p->SaleTypeID ?? '',
        'sale_type_to_rate_id' => $p->SaleTypeToRateID ?? '',
        'sro_schedule_id' => $p->SroScheduleID ?? '',
        'sro_item_id' => $p->SroItemID ?? '',
        'tp_rate' => (float) ($p->TPRate ?? 0),
        'is_fixed_rate' => in_array(strtolower(trim((string) ($p->IsFixedRate ?? '0'))), ['1', 'true', 'yes', 'y', 'on'], true) ? 1 : 0,
        'qty' => (float) ($p->CurrentQuantity ?? 0),
    ])->values();
    $calcSubTotal = $items->sum(fn ($r) => (float) ($r->Amount ?? 0));
    $calcDiscount = $items->sum(fn ($r) => (float) ($r->DiscountAmount ?? 0));
    $calcGst = $items->sum(fn ($r) => (float) ($r->GSTAmount ?? 0));
    $calcTotal = $items->sum(fn ($r) => (float) ($r->Total ?? $r->TotalValues ?? 0));
    $viewSubTotal = $purchase ? ($purchase->SubTotal ?? $purchase->ValueSalesExcludingST ?? $calcSubTotal) : $calcSubTotal;
    $viewDiscount = $purchase ? ($purchase->Discount ?? $calcDiscount) : $calcDiscount;
    $viewGst = $purchase ? ($purchase->SalesTaxApplicable ?? $calcGst) : $calcGst;
    $viewGrand = $purchase ? ($purchase->TotalAmount ?? $calcTotal) : $calcTotal;
    $supplierAdvanceOn = $purchase && (int) ($purchase->SupplierIsAdvanceTax ?? 0) === 1;
    $supplierAdvanceRate = $purchase ? (float) ($purchase->SupplierAdvanceTax ?? 0) : 0.0;
    $supplierWht236g = ($supplierAdvanceOn && $supplierAdvanceRate > 0.000001)
        ? round((float) $viewGrand * ($supplierAdvanceRate / 100.0), 2)
        : 0.0;
    $grandTotalAllTaxes = round((float) $viewGrand + $supplierWht236g, 2);
    $showWhtBlock = $supplierAdvanceOn && $supplierAdvanceRate > 0.000001;
@endphp

@if(!$isReadonly)
<form action="{{ $formAction }}" method="POST" id="purchaseForm" novalidate>
    @csrf
    @if($isEdit) @method('PUT') @endif
@endif

@if($inModal)
<div class="ifp-modal-shell">
    <div class="_pfp-tab-panel-shell border rounded overflow-hidden mb-3">
        <div class="_pfp-tab-panel-body">
@endif

<div class="row g-2">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
        @if($isReadonly)
            <div class="form-control form-control-sm bg-light">{{ $purchase->SupplierDisplayName ?? '-' }}</div>
        @else
            <div class="d-flex gap-1 align-items-center ifp-customer-select-wrap">
                <select name="SupplierID" id="{{ $uid }}_SupplierID"
                        class="{{ $selClass }} flex-grow-1 @error('SupplierID') is-invalid @enderror"
                        style="min-width:0;" required>
                    <option value="">— Select Supplier —</option>
                    @foreach($suppliers ?? [] as $s)
                        @php
                            $label = trim($s->BusinessName ?? '') ?: trim($s->DisplayName ?? '') ?: trim(($s->FirstName ?? '').' '.($s->LastName ?? '')) ?: ('Supplier #' . $s->ID);
                        @endphp
                        <option value="{{ $s->ID }}"
                                data-is-filer="{{ (int) ($s->IsFiler ?? 0) ? 1 : 0 }}"
                                data-sales-tax-withheld="{{ $s->SalesTaxWithheldAtSource ?? 0 }}"
                                data-extra-tax-pct="{{ $s->ExtraTax ?? 0 }}"
                                data-further-tax-pct="{{ $s->FurtherTax ?? 0 }}"
                                data-fed-payable-pct="{{ $s->FedPayable ?? 0 }}"
                                data-is-advance-tax="{{ (int) ($s->IsAdvanceTax ?? 0) === 1 ? 1 : 0 }}"
                                data-advance-tax-rate="{{ (float) ($s->AdvanceTax ?? 0) }}"
                                {{ (int) old('SupplierID', $purchase->SupplierID ?? 0) === (int) $s->ID ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-primary ifp-line-add-entity-btn flex-shrink-0"
                        id="{{ $uid }}_newSupplierBtn" title="New supplier" aria-label="New supplier">
                    <i class="ri-add-line"></i>
                </button>
            </div>
            @error('SupplierID')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
        @if($isReadonly)
            <div class="form-control form-control-sm bg-light">{{ $purchase->PurchaseDate ? \Carbon\Carbon::parse($purchase->PurchaseDate)->format('d M Y') : '-' }}</div>
        @else
            <input type="date" name="PurchaseDate" id="{{ $uid }}_PurchaseDate"
                   class="form-control form-control-sm @error('PurchaseDate') is-invalid @enderror"
                   value="{{ old('PurchaseDate', isset($purchase->PurchaseDate) ? \Carbon\Carbon::parse($purchase->PurchaseDate)->format('Y-m-d') : date('Y-m-d')) }}"
                   required>
            @error('PurchaseDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Doc No.</label>
        @if($isReadonly)
            <div class="form-control form-control-sm bg-light">{{ $purchase->DocNo ?? '-' }}</div>
        @else
            <input type="text" name="DocNo" class="form-control form-control-sm @error('DocNo') is-invalid @enderror"
                   value="{{ old('DocNo', $purchase->DocNo ?? '') }}" placeholder="Auto-generated">
            @error('DocNo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-2">
        @if($isEdit)
        <label class="form-label fw-semibold">Status</label>
        <select name="Status" class="{{ $selClass }}">
            <option value="1" {{ old('Status', $purchase->Status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
            <option value="2" {{ old('Status', $purchase->Status ?? 1) == 2 ? 'selected' : '' }}>Archive</option>
        </select>
        @elseif($isCreate)
        <input type="hidden" name="Status" value="1">
        @endif
    </div>
</div>

@if($inModal)
        </div>
    </div>
    <div class="_pfp-tab-panel-shell border rounded overflow-hidden mb-3">
        <div class="_pfp-tab-panel-body">
@endif

<style>
    .table-inv.purchase-lines-table {
        --ifp-inv-cell-fs: 12px;
        --ifp-inv-head-pad: 0.3rem;
        --ifp-inv-head-bg: var(--bs-gray-300, #dee2e6);
        --ifp-inv-head-fg: var(--bs-black, #000);
    }
    .table-inv.purchase-lines-table thead tr th {
        font-size: var(--ifp-inv-cell-fs) !important;
        padding: var(--ifp-inv-head-pad) !important;
        font-weight: 400 !important;
        background: var(--ifp-inv-head-bg) !important;
        color: var(--ifp-inv-head-fg) !important;
        font-family: inherit;
    }
    .table-inv.purchase-lines-table .form-control,
    .table-inv.purchase-lines-table .form-select {
        padding: 0.2rem 0.35rem;
        font-size: var(--ifp-inv-cell-fs) !important;
        font-family: inherit !important;
        line-height: 1.35;
        min-height: calc(1.2em + 0.4rem);
        width: 100%;
        box-sizing: border-box;
    }
    .table-inv.purchase-lines-table input[type="number"] {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .table-inv th.ifp-col-product,
    .table-inv td.ifp-col-product { min-width: 170px; width: 13%; }
    .table-inv th.ifp-col-desc,
    .table-inv td.ifp-col-desc { min-width: 140px; width: 12%; }
    .table-inv th.ifp-col-uom,
    .table-inv td.ifp-col-uom { min-width: 130px; width: 10%; }
    .table-inv th.ifp-col-tax-sro,
    .table-inv td.ifp-col-tax-sro { min-width: 220px; width: 14%; max-width: none; }
    .table-inv th.ifp-col-qty,
    .table-inv td.ifp-col-qty { min-width: 64px; width: 4%; }
    .table-inv th.ifp-col-sale-rate,
    .table-inv td.ifp-col-sale-rate { min-width: 88px; width: 5%; }
    .table-inv th.ifp-col-amount,
    .table-inv td.ifp-col-amount { min-width: 108px; width: 6%; }
    .table-inv th.ifp-col-fixed,
    .table-inv td.ifp-col-fixed { min-width: 132px; width: 7%; }
    .table-inv th.ifp-col-disc-amt,
    .table-inv td.ifp-col-disc-amt { min-width: 108px; width: 6%; }
    .table-inv th.ifp-col-extra-tax,
    .table-inv td.ifp-col-extra-tax { min-width: 96px; width: 5%; }
    .table-inv th.ifp-col-further-tax,
    .table-inv td.ifp-col-further-tax { min-width: 96px; width: 5%; }
    .table-inv th.ifp-col-fed-payable,
    .table-inv td.ifp-col-fed-payable { min-width: 96px; width: 5%; }
    .table-inv th.ifp-col-gst-amt,
    .table-inv td.ifp-col-gst-amt { min-width: 108px; width: 6%; }
    .table-inv th.ifp-col-net,
    .table-inv td.ifp-col-net { min-width: 122px; width: 7%; }
    .table-inv th.ifp-col-actions,
    .table-inv td.ifp-col-actions { min-width: 48px; width: 3%; }
    .table-inv input[type="number"]::-webkit-outer-spin-button,
    .table-inv input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .table-inv input[type="number"] { -moz-appearance: textfield; }
    .table-inv .ifp-numfmt {
        min-width: 0;
        padding-left: 0.25rem;
        padding-right: 0.25rem;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .ifp-inv-line-items-scroll { overflow-x: auto; width: 100%; max-width: 100%; min-width: 0; }
    .ifp-purchase-totals-summary.table {
        margin-left: auto; width: 100%; max-width: 22rem; font-size: 12px;
    }
    .ifp-purchase-totals-summary .form-control,
    .ifp-purchase-totals-summary .invoice-amount-input {
        font-size: 12px !important;
        line-height: 1.2 !important;
        text-align: right !important;
        font-variant-numeric: tabular-nums;
        min-height: 24.2px !important;
        height: 24.2px !important;
        padding: 0.2rem 0.35rem !important;
        box-sizing: border-box !important;
    }
    .ifp-purchase-totals-summary tr.ifp-totals-inc th,
    .ifp-purchase-totals-summary tr.ifp-totals-inc td,
    .ifp-purchase-totals-summary tr.ifp-totals-grand th,
    .ifp-purchase-totals-summary tr.ifp-totals-grand td {
        border: none !important;
        border-top: 1px solid #222 !important;
        border-bottom: 1px solid #222 !important;
        font-size: 12px;
        padding-top: 0.3rem !important;
        padding-bottom: 0.3rem !important;
    }
    .ifp-purchase-totals-summary tr.ifp-totals-grand td { font-weight: 700; }
</style>

<div class="col-xl-12 px-0">
    <div class="ifp-inv-line-items-scroll">
        <table class="table table-inv listing-table purchase-lines-table mt-3 {{ $isReadonly ? '' : 'text-nowrap' }}">
            <thead>
                <tr>
                    <th class="ifp-col-product">Product / Services</th>
                    <th class="ifp-col-desc">Description</th>
                    <th class="ifp-col-uom">UOM</th>
                    @if(!$inModal)
                    <th>Sale Type</th>
                    <th>Sale Type To Rate</th>
                    <th>SRO Schedule</th>
                    <th>SRO Item</th>
                    @else
                    <th class="ifp-col-tax-sro">Tax &amp; SRO</th>
                    @endif
                    <th class="ifp-col-qty">Qty</th>
                    <th class="ifp-col-sale-rate">Unit Rate</th>
                    <th class="ifp-col-amount">Amount</th>
                    @if($inModal)
                    <th class="ifp-col-fixed">Fixed notified value</th>
                    @endif
                    @if(!$inModal)
                    <th>Disc %</th>
                    @endif
                    <th class="ifp-col-disc-amt">Disc Amount</th>
                    @if(!$inModal)
                    <th>GST Rate</th>
                    @endif
                    <th class="ifp-col-gst-amt">GST Amount</th>
                    <th class="ifp-col-net">Net</th>
                    <th class="ifp-col-extra-tax">Extra Tax</th>
                    <th class="ifp-col-further-tax">Further Tax</th>
                    <th class="ifp-col-fed-payable">FED Payable</th>
                    @if(!$isReadonly)<th class="ifp-col-actions"></th>@endif
                </tr>
            </thead>
            <tbody id="{{ $uid }}_body">
                @forelse($items as $idx => $item)
                    @include('pages.dashboards.Purchase._purchase-line-row', [
                        'idx' => $idx,
                        'item' => $item,
                        'readonly' => $isReadonly,
                        'products' => $products,
                        'selClass' => $selClass,
                        'inModal' => $inModal,
                        'uid' => $uid,
                    ])
                @empty
                    @if(!$isReadonly)
                        @include('pages.dashboards.Purchase._purchase-line-row', [
                            'idx' => 0,
                            'item' => null,
                            'readonly' => false,
                            'products' => $products,
                            'selClass' => $selClass,
                            'inModal' => $inModal,
                            'uid' => $uid,
                        ])
                    @else
                        <tr><td colspan="{{ $pfpEmptyColspan }}" class="text-center text-muted py-3"><i>No line items found.</i></td></tr>
                    @endif
                @endforelse
            </tbody>
        </table>
    </div>

    @if(!$isReadonly)
    <div class="my-3">
        <button type="button" class="btn btn-sm btn-primary" id="{{ $uid }}_addRow">
            <i class="bi bi-plus-lg"></i> Add Product
        </button>
    </div>
    @endif
</div>

@if($inModal)
        </div>
    </div>
    <div class="_pfp-tab-panel-shell border rounded overflow-hidden mb-0">
        <div class="_pfp-tab-panel-body">
@endif

<div class="row mt-4">
    <div class="col-md-9">
        <div class="clearfix w-50">
            <label class="form-label fw-semibold">Notes</label>
            @if($isReadonly)
                <div class="form-control bg-light" style="min-height:80px;white-space:pre-line;">{{ $purchase->Notes ?? '-' }}</div>
            @else
                <textarea name="Notes" rows="4" class="form-control form-control-sm @error('Notes') is-invalid @enderror"
                          placeholder="Any additional notes...">{{ old('Notes', $purchase->Notes ?? '') }}</textarea>
                @error('Notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
        </div>
    </div>
    <div class="col text-end">
        <table class="table table-sm text-nowrap mb-0 table-borderless ifp-purchase-totals-summary">
            <tbody>
                <tr>
                    <th scope="row"><div class="fw-medium">Total Amount Excluding ST:</div></th>
                    <td>
                        <input type="text" name="SubTotal" id="{{ $uid }}_SubTotal"
                               class="form-control invoice-amount-input w-100 ifp-numfmt"
                               value="{{ number_format((float) $viewSubTotal, 2, '.', ',') }}" readonly>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><div class="fw-medium">Discount:</div></th>
                    <td>
                        <input type="text" name="TotalDiscount" id="{{ $uid }}_TotalDiscount"
                               class="form-control invoice-amount-input w-100 ifp-numfmt"
                               value="{{ number_format((float) $viewDiscount, 2, '.', ',') }}" readonly>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><div class="fw-medium">Total Sales Tax:</div></th>
                    <td>
                        <input type="text" name="TotalGST" id="{{ $uid }}_TotalGST"
                               class="form-control invoice-amount-input w-100 ifp-numfmt"
                               value="{{ number_format((float) $viewGst, 2, '.', ',') }}" readonly>
                    </td>
                </tr>
                <tr class="ifp-totals-inc">
                    <th scope="row"><div class="fw-medium">Total Inc. All Taxes:</div></th>
                    <td>
                        <input type="text" name="GrandTotal" id="{{ $uid }}_GrandTotal"
                               class="form-control invoice-amount-input w-100 ifp-numfmt fw-bold"
                               value="{{ number_format((float) $viewGrand, 2, '.', ',') }}" readonly>
                    </td>
                </tr>
                @if($isReadonly)
                    @if($showWhtBlock)
                        <tr>
                            <th scope="row"><div class="fw-medium">Supplier WHT 236G ({{ number_format($supplierAdvanceRate, 2) }}%):</div></th>
                            <td>
                                <input type="text" id="{{ $uid }}_SupplierWht236g"
                                       class="form-control invoice-amount-input w-100 ifp-numfmt"
                                       value="{{ number_format($supplierWht236g, 2, '.', ',') }}" readonly>
                            </td>
                        </tr>
                        <tr class="ifp-totals-grand">
                            <th scope="row"><div class="fw-medium">Grand Total:</div></th>
                            <td>
                                <input type="text" id="{{ $uid }}_GrandTotalWithWht"
                                       class="form-control invoice-amount-input w-100 ifp-numfmt fw-bold"
                                       value="{{ number_format($grandTotalAllTaxes, 2, '.', ',') }}" readonly>
                            </td>
                        </tr>
                    @endif
                @else
                    <tr id="{{ $uid }}_tr_supplier_wht" class="@if(!$showWhtBlock) d-none @endif">
                        <th scope="row">
                            <div class="fw-medium">Supplier WHT 236G (<span id="{{ $uid }}_wht_label_pct">{{ number_format($supplierAdvanceRate, 2) }}</span>%):</div>
                        </th>
                        <td>
                            <input type="text" id="{{ $uid }}_SupplierWht236g"
                                   class="form-control invoice-amount-input w-100 ifp-numfmt"
                                   value="{{ number_format($supplierWht236g, 2, '.', ',') }}" readonly>
                        </td>
                    </tr>
                    <tr id="{{ $uid }}_tr_grand_with_wht" class="ifp-totals-grand @if(!$showWhtBlock) d-none @endif">
                        <th scope="row"><div class="fw-medium">Grand Total:</div></th>
                        <td>
                            <input type="text" id="{{ $uid }}_GrandTotalWithWht"
                                   class="form-control invoice-amount-input w-100 ifp-numfmt fw-bold"
                                   value="{{ number_format($grandTotalAllTaxes, 2, '.', ',') }}" readonly>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@if($inModal)
        </div>
    </div>
</div>
@endif

@if(!$isReadonly && $inModal)
<div class="modal fade ifp-tax-sro-nested-modal" id="{{ $uid }}_taxSroModal" tabindex="-1" aria-labelledby="{{ $uid }}_taxSroModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3 border-bottom">
                <h6 class="modal-title fs-14 fw-semibold mb-0" id="{{ $uid }}_taxSroModalLabel">Tax &amp; SRO</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="{{ $uid }}_taxSroModalBody"></div>
            <div class="modal-footer py-2 px-3 border-top bg-light">
                <button type="button" class="btn btn-sm btn-primary px-3" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>
@endif

@if(!$isReadonly && !$inModal)
<div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> {{ $isEdit ? 'Update Purchase' : 'Save Purchase' }}</button>
    <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-light">Cancel</a>
</div>
@endif

@if(!$isReadonly)
</form>
@endif

@if(!$isReadonly)
{{-- Nested supplier modal (same pattern as invoice customer quick-create) --}}
<div class="modal fade ifp-customer-nested" id="supplier_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="supplier_modal_title">Supplier</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="supplier_modal_body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted mb-0">Loading…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                <button type="button" class="btn btn-sm btn-primary d-none" id="supplier_modal_save_btn">
                    <i class="fas fa-save"></i> <span id="supplier_modal_save_label">Save Supplier</span>
                </button>
            </div>
        </div>
    </div>
</div>
{{-- Nested product modal (same pattern as invoice form partial) --}}
<div class="modal fade ifp-product-nested" id="product_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="product_modal_title">Product</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="product_modal_body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted mb-0">Loading…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                <button type="button" class="btn btn-sm btn-primary d-none" id="product_modal_save_btn">
                    <i class="fas fa-save"></i> <span id="product_modal_save_label">Save Product</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if(!$isReadonly)
@vite(['resources/assets/js/line-tax-calculator.js', 'resources/assets/js/ifp-purchase-tax-form.js'])
@endif
<script>
window.PURCHASE_PRODUCTS = @json($productsJson);
window.PURCHASE_FORM_UID = @json($uid);
window.PURCHASE_SEL_CLASS = @json($selClass);
window.PURCHASE_IN_MODAL = @json((bool) $inModal);

window.initPurchaseForm = function(mode, externalSaveBtn, bsModal) {
    const form = document.getElementById('purchaseForm');
    const uid = window.PURCHASE_FORM_UID || 'pfp_create_new';
    const body = document.getElementById(uid + '_body');
    const selCls = window.PURCHASE_SEL_CLASS || 'form-select';
    if (!form || mode === 'show') return;

    if (window.IfpPurchaseTaxForm && window.IfpLineTaxCalculator && body) {
        window._pfpTaxBindings = window.IfpPurchaseTaxForm.init({
            uid: uid,
            body: body,
            selCls: selCls,
            inModal: !!window.PURCHASE_IN_MODAL,
            routes: {
                hscode: @json(route('products.hscode-info')),
                saleTypes: @json(route('products.sale-types')),
                saleTypeRates: @json(route('products.sale-type-to-rates')),
                sroSchedules: @json(route('products.sro-schedules')),
                sroItems: @json(route('products.sro-items')),
            }
        });
    }

    let productQuickTargetRow = null;

    function pfpAppendSupplierOption(sel, c) {
        if (!sel || !c || !c.id) return;
        const id = String(c.id);
        let exists = false;
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === id) { exists = true; break; }
        }
        if (!exists) {
            const o = document.createElement('option');
            o.value = id;
            o.textContent = c.label || ('Supplier #' + id);
            o.setAttribute('data-is-filer', String(c.is_filer != null ? c.is_filer : 0));
            o.setAttribute('data-sales-tax-withheld', String(c.sales_tax_withheld != null ? c.sales_tax_withheld : 0));
            o.setAttribute('data-extra-tax-pct', String(c.extra_tax != null ? c.extra_tax : 0));
            o.setAttribute('data-further-tax-pct', String(c.further_tax != null ? c.further_tax : 0));
            o.setAttribute('data-fed-payable-pct', String(c.fed_payable != null ? c.fed_payable : 0));
            o.setAttribute('data-is-advance-tax', String(c.is_advance_tax != null ? c.is_advance_tax : 0));
            o.setAttribute('data-advance-tax-rate', String(c.advance_tax != null ? c.advance_tax : 0));
            sel.appendChild(o);
        }
        sel.value = id;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function pfpElevateSupplierModalToBody(modalEl) {
        if (!modalEl || modalEl.dataset.pfpElevated === '1') return;
        const purchaseEl = document.getElementById('purchase_modal');
        if (!purchaseEl || !modalEl.closest('#purchase_modal')) return;
        const parent = modalEl.parentNode;
        if (!parent || parent === document.body) return;
        const ph = document.createElement('div');
        ph.id = 'pfp_supplier_modal_ph';
        ph.className = 'd-none';
        parent.insertBefore(ph, modalEl);
        document.body.appendChild(modalEl);
        modalEl.classList.add('ifp-customer-over-invoice');
        modalEl.dataset.pfpElevated = '1';
        document.body.classList.add('ifp-purchase-supplier-open');
    }

    function pfpRestoreSupplierModalFromBody(modalEl) {
        document.body.classList.remove('ifp-purchase-supplier-open');
        if (!modalEl || modalEl.dataset.pfpElevated !== '1') return;
        const ph = document.getElementById('pfp_supplier_modal_ph');
        modalEl.classList.remove('ifp-customer-over-invoice');
        delete modalEl.dataset.pfpElevated;
        if (ph && ph.parentNode) {
            ph.parentNode.insertBefore(modalEl, ph);
            ph.parentNode.removeChild(ph);
        } else {
            const purchaseBody = document.getElementById('purchase_modal_body');
            if (purchaseBody) purchaseBody.appendChild(modalEl);
        }
    }

    function pfpShowSupplierModalErrors(errors) {
        const modalBody = document.getElementById('supplier_modal_body');
        if (!modalBody) return;
        modalBody.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        modalBody.querySelectorAll('.ajax-error-feedback').forEach(el => el.remove());
        let firstField = null;
        Object.keys(errors || {}).forEach(function(field) {
            const messages = errors[field];
            const input = modalBody.querySelector('[name="' + field + '"]');
            if (input && messages && messages.length) {
                input.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback ajax-error-feedback';
                feedback.style.display = 'block';
                feedback.textContent = messages[0];
                input.insertAdjacentElement('afterend', feedback);
                if (!firstField) firstField = input;
            }
        });
        if (firstField) {
            firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstField.focus();
        }
    }

    function openPurchaseSupplierModalCreate() {
        const modalEl = document.getElementById('supplier_modal');
        const modalBody = document.getElementById('supplier_modal_body');
        const modalTitle = document.getElementById('supplier_modal_title');
        const saveBtn = document.getElementById('supplier_modal_save_btn');
        if (!modalEl || !modalBody || !saveBtn) return;
        pfpElevateSupplierModalToBody(modalEl);
        const bsSupplierModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        if (modalTitle) modalTitle.textContent = 'Add Supplier';
        saveBtn.classList.remove('d-none');
        const saveLabelEl = document.getElementById('supplier_modal_save_label');
        if (saveLabelEl) saveLabelEl.textContent = 'Save Supplier';
        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted mb-0">Loading…</p></div>';
        bsSupplierModal.show();
        fetch(@json(route('suppliers.modal')) + '?mode=create', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': pfpCsrfToken() },
            credentials: 'same-origin'
        })
        .then(res => { if (!res.ok) throw new Error('Server error: ' + res.status); return res.text(); })
        .then(html => {
            modalBody.innerHTML = html;
            Array.from(modalBody.getElementsByTagName('script')).forEach(oldScript => {
                const s = document.createElement('script');
                if (oldScript.src) { s.src = oldScript.src; s.async = false; }
                else s.textContent = oldScript.textContent;
                document.body.appendChild(s);
            });
            saveBtn.onclick = function() {
                const supForm = modalBody.querySelector('form');
                if (!supForm) return;
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
                fetch(supForm.action, {
                    method: 'POST',
                    body: new FormData(supForm),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': pfpCsrfToken()
                    },
                    credentials: 'same-origin'
                })
                .then(res => res.json().then(data => ({ res, data })))
                .then(({ data }) => {
                    if (data && data.status === 'success') {
                        bsSupplierModal.hide();
                        const mainSup = document.getElementById(uid + '_SupplierID');
                        if (mainSup && data.customer) {
                            pfpAppendSupplierOption(mainSup, data.customer);
                        }
                        if (typeof showToast === 'function') {
                            showToast('success', data.message || 'Supplier saved.');
                        }
                    } else {
                        pfpShowSupplierModalErrors(data && data.errors ? data.errors : {});
                        if (data && data.message && typeof showToast === 'function') {
                            showToast('error', data.message);
                        }
                    }
                })
                .catch(() => {
                    if (typeof showToast === 'function') {
                        showToast('error', 'Submission failed. Please try again.');
                    }
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> <span id="supplier_modal_save_label">Save Supplier</span>';
                });
            };
        })
        .catch(err => {
            modalBody.innerHTML = '<div class="alert alert-danger m-3">Failed to load supplier form.<br><small class="text-muted">' + String(err.message || err) + '</small></div>';
        });
    }

    document.getElementById(uid + '_newSupplierBtn')?.addEventListener('click', function() {
        openPurchaseSupplierModalCreate();
    });

    const pfpSupplierModalRoot = document.getElementById('supplier_modal');
    if (pfpSupplierModalRoot) {
        pfpSupplierModalRoot.addEventListener('hidden.bs.modal', function() {
            pfpRestoreSupplierModalFromBody(pfpSupplierModalRoot);
        });
    }

    function pfpCsrfToken() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? (m.getAttribute('content') || '') : '';
    }

    function buildProductOptionEl(p) {
        const o = document.createElement('option');
        const id = String(p.id);
        const cost = p.cost != null ? p.cost : (p.rate != null ? p.rate : 0);
        o.value = id;
        o.textContent = p.label || p.name || ('Product #' + id);
        o.setAttribute('data-cost', String(cost));
        o.setAttribute('data-rate', String(cost));
        o.setAttribute('data-gst', String(p.gst != null ? p.gst : 0));
        o.setAttribute('data-hscode', String(p.hscode != null ? p.hscode : ''));
        o.setAttribute('data-sale-information', String(p.sale_information != null ? p.sale_information : ''));
        o.setAttribute('data-uom-id', String(p.uom_id != null ? p.uom_id : ''));
        o.setAttribute('data-uom-label', String(p.uom_label != null ? p.uom_label : ''));
        o.setAttribute('data-sale-type-id', String(p.sale_type_id != null ? p.sale_type_id : ''));
        o.setAttribute('data-sale-type-to-rate-id', String(p.sale_type_to_rate_id != null ? p.sale_type_to_rate_id : ''));
        o.setAttribute('data-sro-schedule-id', String(p.sro_schedule_id != null ? p.sro_schedule_id : ''));
        o.setAttribute('data-sro-item-id', String(p.sro_item_id != null ? p.sro_item_id : ''));
        o.setAttribute('data-tp-rate', String(p.tp_rate != null ? p.tp_rate : 0));
        o.setAttribute('data-is-fixed-rate', String(p.is_fixed_rate != null ? p.is_fixed_rate : 0));
        return o;
    }

    function applyNewProductToRows(p) {
        if (!p || !p.id) return;
        const id = parseInt(p.id, 10);
        const cost = parseFloat(p.cost != null ? p.cost : (p.rate != null ? p.rate : 0)) || 0;
        const name = p.label || ('Product #' + id);
        const list = window.PURCHASE_PRODUCTS || [];
        if (!list.some(x => x.id === id)) {
            list.push({ id: id, name: name, cost: cost, qty: 0 });
            window.PURCHASE_PRODUCTS = list;
        }
        body.querySelectorAll('.ifp-product, .line-product').forEach(function(sel) {
            let exists = false;
            for (let i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === String(id)) { exists = true; break; }
            }
            if (!exists) sel.appendChild(buildProductOptionEl(p));
        });
        const target = productQuickTargetRow && productQuickTargetRow.querySelector('.line-product, .ifp-product');
        productQuickTargetRow = null;
        if (target) {
            target.value = String(id);
            target.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function pfpElevateProductModalToBody(modalEl) {
        if (!modalEl || modalEl.dataset.pfpElevated === '1') return;
        const purchaseEl = document.getElementById('purchase_modal');
        if (!purchaseEl || !modalEl.closest('#purchase_modal')) return;
        const parent = modalEl.parentNode;
        if (!parent || parent === document.body) return;
        const ph = document.createElement('div');
        ph.id = 'pfp_product_modal_ph';
        ph.className = 'd-none';
        parent.insertBefore(ph, modalEl);
        document.body.appendChild(modalEl);
        modalEl.classList.add('ifp-product-over-invoice');
        modalEl.dataset.pfpElevated = '1';
        document.body.classList.add('ifp-purchase-product-open');
    }

    function pfpRestoreProductModalFromBody(modalEl) {
        document.body.classList.remove('ifp-purchase-product-open');
        if (!modalEl || modalEl.dataset.pfpElevated !== '1') return;
        const ph = document.getElementById('pfp_product_modal_ph');
        modalEl.classList.remove('ifp-product-over-invoice');
        delete modalEl.dataset.pfpElevated;
        if (ph && ph.parentNode) {
            ph.parentNode.insertBefore(modalEl, ph);
            ph.parentNode.removeChild(ph);
        } else {
            const purchaseBody = document.getElementById('purchase_modal_body');
            if (purchaseBody) purchaseBody.appendChild(modalEl);
        }
    }

    function pfpShowProductModalErrors(errors) {
        const modalBody = document.getElementById('product_modal_body');
        if (!modalBody) return;
        modalBody.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        modalBody.querySelectorAll('.ajax-error-feedback').forEach(el => el.remove());
        let firstField = null;
        Object.keys(errors || {}).forEach(function(field) {
            const messages = errors[field];
            const input = modalBody.querySelector('[name="' + field + '"]');
            if (input && messages && messages.length) {
                input.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback ajax-error-feedback';
                feedback.style.display = 'block';
                feedback.textContent = messages[0];
                input.insertAdjacentElement('afterend', feedback);
                if (!firstField) firstField = input;
            }
        });
        if (firstField) {
            firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstField.focus();
        }
    }

    function openPurchaseProductModalCreate() {
        const modalEl = document.getElementById('product_modal');
        const modalBody = document.getElementById('product_modal_body');
        const modalTitle = document.getElementById('product_modal_title');
        const saveBtn = document.getElementById('product_modal_save_btn');
        if (!modalEl || !modalBody || !saveBtn) return;
        pfpElevateProductModalToBody(modalEl);
        const bsProductModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        if (modalTitle) modalTitle.textContent = 'Add Product';
        saveBtn.classList.remove('d-none');
        const saveLabelEl = document.getElementById('product_modal_save_label');
        if (saveLabelEl) saveLabelEl.textContent = 'Save Product';
        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted mb-0">Loading…</p></div>';
        bsProductModal.show();
        fetch(@json(route('products.modal')) + '?mode=create', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': pfpCsrfToken() },
            credentials: 'same-origin'
        })
        .then(res => { if (!res.ok) throw new Error('Server error: ' + res.status); return res.text(); })
        .then(html => {
            modalBody.innerHTML = html;
            Array.from(modalBody.getElementsByTagName('script')).forEach(oldScript => {
                const s = document.createElement('script');
                if (oldScript.src) { s.src = oldScript.src; s.async = false; }
                else s.textContent = oldScript.textContent;
                document.body.appendChild(s);
            });
            saveBtn.onclick = function() {
                const pfForm = modalBody.querySelector('form');
                if (!pfForm) return;
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
                fetch(pfForm.action, {
                    method: 'POST',
                    body: new FormData(pfForm),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': pfpCsrfToken()
                    },
                    credentials: 'same-origin'
                })
                .then(res => res.json().then(data => ({ res, data })))
                .then(({ data }) => {
                    if (data && data.status === 'success') {
                        bsProductModal.hide();
                        if (data.product) {
                            const prod = data.product;
                            if (prod.cost == null && data.product.rate != null) {
                                prod.cost = data.product.rate;
                            }
                            applyNewProductToRows(prod);
                        }
                        if (typeof showToast === 'function') {
                            showToast('success', data.message || 'Product saved.');
                        }
                    } else {
                        pfpShowProductModalErrors(data && data.errors ? data.errors : {});
                        if (data && data.message && typeof showToast === 'function') {
                            showToast('error', data.message);
                        }
                    }
                })
                .catch(() => {
                    if (typeof showToast === 'function') {
                        showToast('error', 'Submission failed. Please try again.');
                    }
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> <span id="product_modal_save_label">Save Product</span>';
                });
            };
        })
        .catch(err => {
            modalBody.innerHTML = '<div class="alert alert-danger m-3">Failed to load product form.<br><small class="text-muted">' + String(err.message || err) + '</small></div>';
        });
    }

    if (!body.dataset.pfpProductClickBound) {
        body.dataset.pfpProductClickBound = '1';
        body.addEventListener('click', function(ev) {
            const btn = ev.target.closest('.ifp-new-product-btn');
            if (!btn || !body.contains(btn)) return;
            ev.preventDefault();
            productQuickTargetRow = btn.closest('.ifp-row');
            openPurchaseProductModalCreate();
        });
    }

    const pfpProductModalRoot = document.getElementById('product_modal');
    if (pfpProductModalRoot && !pfpProductModalRoot.dataset.pfpRestoreBound) {
        pfpProductModalRoot.dataset.pfpRestoreBound = '1';
        pfpProductModalRoot.addEventListener('hidden.bs.modal', function() {
            pfpRestoreProductModalFromBody(pfpProductModalRoot);
        });
    }

    function ifpSanitizeNumericText(v) {
        return String(v == null ? '' : v).replace(/,/g, '').trim();
    }
    function ifpFormatNumber(v, decimals) {
        const d = typeof decimals === 'number' ? decimals : 2;
        const num = Number(v);
        const safe = Number.isFinite(num) ? num : 0;
        return safe.toLocaleString('en-US', {
            minimumFractionDigits: d,
            maximumFractionDigits: d
        });
    }
    function ifpNormalizeFormattedInputs(scope) {
        const root = scope || document;
        root.querySelectorAll('.ifp-numfmt').forEach(function(el) {
            el.value = ifpSanitizeNumericText(el.value);
        });
    }
    window.ifpSanitizeNumericText = ifpSanitizeNumericText;
    window.ifpFormatNumber = ifpFormatNumber;
    window.ifpNormalizeFormattedInputs = ifpNormalizeFormattedInputs;

    const submitHandler = function(e) {
        if (e) e.preventDefault();
        const rows = body.querySelectorAll('tr.ifp-row');
        if (!rows.length) { alert('Add at least one line item.'); return; }
        let valid = true;
        rows.forEach(row => {
            if (!row.querySelector('.line-product')?.value || (parseFloat(row.querySelector('.line-qty')?.value) || 0) <= 0) valid = false;
        });
        if (!valid) { alert('Each line needs a product and quantity greater than zero.'); return; }

        if (typeof window.ifpNormalizeFormattedInputs === 'function') {
            window.ifpNormalizeFormattedInputs(form);
        }

        const fd = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                if (bsModal) bsModal.hide();
                window.location.reload();
            } else {
                alert(data.message || 'Save failed.');
            }
        })
        .catch(() => alert('Save failed.'));
    };

    if (externalSaveBtn) externalSaveBtn.onclick = submitHandler;
    else form.addEventListener('submit', submitHandler);
};

@if(!$inModal && !$isReadonly)
document.addEventListener('DOMContentLoaded', () => window.initPurchaseForm('{{ $mode }}', null, null));
@endif
</script>
