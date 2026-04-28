@php
    $mode       = $mode    ?? 'create';
    $invoice    = $invoice ?? null;
    $items      = $items   ?? collect();
    $inModal    = $inModal ?? false;

    $isCreate   = $mode === 'create';
    $isEdit     = $mode === 'edit';
    $isView     = $mode === 'show';
    $isReadonly = $isView;

    $invoiceId  = $invoice->ID ?? 0;

    $formAction = $isCreate
        ? route('invoices.save_transaction')
        : ($isEdit ? route('invoices.edit_transaction', $invoiceId) : '#');

    $uid = 'ifp_' . $mode . '_' . ($invoiceId ?: 'new');
    $selClass = $inModal ? 'form-select' : 'form-select form-select-sm';
    $ifpEmptyColspan = $inModal ? ($isReadonly ? 13 : 14) : ($isReadonly ? 17 : 18);
@endphp

{{-- ── Info Badge (edit / show) ── --}}
@if(!$isCreate && $invoice)
<div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-3 flex-wrap">
    <span><strong>Doc#:</strong> {{ $invoice->DocNo ?? 'N/A' }}</span>
    <span><strong>Date:</strong> {{ $invoice->InvoiceDate ? \Carbon\Carbon::parse($invoice->InvoiceDate)->format('d M Y') : 'N/A' }}</span>
    <span>
        <strong>Status:</strong>
        <span class="badge {{ $invoice->Status == 1 ? 'bg-success' : 'bg-danger' }}">
            {{ $invoice->Status == 1 ? 'Active' : 'Archived' }}
        </span>
    </span>
</div>
@endif

{{-- ── Form ── --}}
@if(!$isReadonly)
<form action="{{ $formAction }}" method="POST" id="{{ $uid }}_form" novalidate>
    @csrf
@endif
@if($inModal)
<div class="ifp-modal-shell">
@endif

<div class="row g-2">
    {{-- Row 1: 4 columns — InvoiceType | Customer | Date | Status --}}
    <div class="col-md-3">
        <label class="form-label fw-semibold">Invoice Type</label>
        @if($isReadonly)
            @php
                $invTypes = $invoiceTypes ?? collect();
                $invTypeVal = $invoice->InvoiceType ?? '';
                $sel = $invTypes->first(function($x) use ($invTypeVal) { return (string)($x->docTypeId ?? '') === (string)$invTypeVal; });
            @endphp
            <div class="form-control form-control-sm bg-light">{{ $sel ? ($sel->docDescription ?? $invoice->InvoiceType) : ($invoice->InvoiceType ?? '-') }}</div>
        @else
            @php
                $invTypeSelected = old('InvoiceType', $invoice->InvoiceType ?? null);
                if ($invTypeSelected === null && $isCreate) {
                    $saleInv = collect($invoiceTypes ?? [])->first(function($x) {
                        return strcasecmp(trim($x->docDescription ?? ''), 'Sale Invoice') === 0;
                    });
                    $invTypeSelected = $saleInv ? $saleInv->docTypeId : '';
                }
                $invTypeSelected = $invTypeSelected ?? '';
            @endphp
            <select name="InvoiceType"
                    id="{{ $uid }}_InvoiceType"
                    class="{{ $selClass }} @error('InvoiceType') is-invalid @enderror">
                <option value="">— Select —</option>
                @foreach($invoiceTypes ?? [] as $it)
                    <option value="{{ $it->docTypeId }}" {{ (string)$invTypeSelected === (string)$it->docTypeId ? 'selected' : '' }}>
                        {{ $it->docDescription ?? $it->docTypeId }}
                    </option>
                @endforeach
            </select>
            @error('InvoiceType')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
        @if($isReadonly)
            <div class="form-control form-control-sm bg-light">{{ $invoice->CustomerDisplayName ?? '-' }}</div>
        @else
            <select name="CustomerID"
                    id="{{ $uid }}_CustomerID"
                    class="{{ $selClass }} @error('CustomerID') is-invalid @enderror"
                    required>
                <option value="">— Select Customer —</option>
                @foreach($customers as $cust)
                    <option value="{{ $cust->ID }}"
                            data-is-filer="{{ (int) ($cust->IsFiler ?? 0) ? 1 : 0 }}"
                            data-sales-tax-withheld="{{ $cust->SalesTaxWithheldAtSource ?? 0 }}"
                            data-extra-tax-pct="{{ $cust->ExtraTax ?? 0 }}"
                            data-further-tax-pct="{{ $cust->FurtherTax ?? 0 }}"
                            data-fed-payable-pct="{{ $cust->FedPayable ?? 0 }}"
                            {{ old('CustomerID', $invoice->CustomerID ?? '') == $cust->ID ? 'selected' : '' }}>
                        {{ trim($cust->BusinessName ?? '') ?: trim($cust->DisplayName ?? '') ?: trim(trim(($cust->FirstName ?? '') . ' ' . ($cust->LastName ?? ''))) ?: ('Customer #' . $cust->ID) }}
                    </option>
                @endforeach
            </select>
            @error('CustomerID')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
        @if($isReadonly)
            <div class="form-control form-control-sm bg-light">{{ $invoice->InvoiceDate ? \Carbon\Carbon::parse($invoice->InvoiceDate)->format('d M Y') : '-' }}</div>
        @else
            <input type="date"
                   name="InvoiceDate"
                   id="{{ $uid }}_InvoiceDate"
                   class="form-control form-control-sm @error('InvoiceDate') is-invalid @enderror"
                   value="{{ old('InvoiceDate', $invoice->InvoiceDate ?? date('Y-m-d')) }}"
                   required>
            @error('InvoiceDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-3">
        @if($isEdit)
        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
        <select name="Status" class="{{ $selClass }} @error('Status') is-invalid @enderror" required>
            <option value="1" {{ old('Status', $invoice->Status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
            <option value="2" {{ old('Status', $invoice->Status ?? 1) == 2 ? 'selected' : '' }}>Archive</option>
        </select>
        @error('Status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @elseif($isCreate)
        <input type="hidden" name="Status" value="1">
        @endif
    </div>
</div>

<div class="row g-2 mt-1">
    {{-- Row 2: 4 columns — Doc No, Doc No2, Doc No3, (empty) --}}
    <div class="col-md-3">
        <label class="form-label fw-semibold">Doc No.</label>
        @if($isReadonly)
            <div class="form-control form-control-sm bg-light">{{ $invoice->DocNo ?? '-' }}</div>
        @else
            <input type="text"
                   name="DocNo"
                   class="form-control form-control-sm @error('DocNo') is-invalid @enderror"
                   value="{{ old('DocNo', $invoice->DocNo ?? '') }}"
                   placeholder="Auto-generated">
            @error('DocNo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Doc No2</label>
        @if($isReadonly)
            <div class="form-control form-control-sm bg-light">{{ $invoice->DocNo2 ?? '-' }}</div>
        @else
            <input type="text"
                   name="DocNo2"
                   class="form-control form-control-sm @error('DocNo2') is-invalid @enderror"
                   value="{{ old('DocNo2', $invoice->DocNo2 ?? '') }}"
                   placeholder="Optional">
            @error('DocNo2')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Doc No3</label>
        @if($isReadonly)
            <div class="form-control form-control-sm bg-light">{{ $invoice->DocNo3 ?? '-' }}</div>
        @else
            <input type="text"
                   name="DocNo3"
                   class="form-control form-control-sm @error('DocNo3') is-invalid @enderror"
                   value="{{ old('DocNo3', $invoice->DocNo3 ?? '') }}"
                   placeholder="Optional">
            @error('DocNo3')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-3">
        {{-- Fourth column (empty) for 4-column layout --}}
    </div>
</div>

    {{-- ══ Line Items Table ══ --}}
    <style>
        /* Compact spacing for invoice detail table only; maximize usable cell width */
        .table-inv { border-collapse: collapse; border-spacing: 0; }
        .table-inv th,
        .table-inv td { padding: 0 !important; vertical-align: middle; }
        .table-inv th.ifp-col-product,
        .table-inv td.ifp-col-product { min-width: 170px; width: 13%; }
        .table-inv th.ifp-col-desc,
        .table-inv td.ifp-col-desc { min-width: 280px; width: 23%; }
        .table-inv th.ifp-col-uom,
        .table-inv td.ifp-col-uom { min-width: 130px; width: 10%; }
        .table-inv th.ifp-col-tax-sro,
        .table-inv td.ifp-col-tax-sro { min-width: 120px; width: 10%; }
        .table-inv th.ifp-col-qty,
        .table-inv td.ifp-col-qty { min-width: 64px; width: 4%; }
        .table-inv th.ifp-col-amount,
        .table-inv td.ifp-col-amount { min-width: 96px; width: 6%; }
        .table-inv th.ifp-col-fixed,
        .table-inv td.ifp-col-fixed { min-width: 120px; width: 7%; }
        .table-inv th.ifp-col-disc-amt,
        .table-inv td.ifp-col-disc-amt { min-width: 96px; width: 6%; }
        .table-inv th.ifp-col-extra-tax,
        .table-inv td.ifp-col-extra-tax { min-width: 96px; width: 5%; }
        .table-inv th.ifp-col-further-tax,
        .table-inv td.ifp-col-further-tax { min-width: 96px; width: 5%; }
        .table-inv th.ifp-col-fed-payable,
        .table-inv td.ifp-col-fed-payable { min-width: 96px; width: 5%; }
        .table-inv th.ifp-col-gst-amt,
        .table-inv td.ifp-col-gst-amt { min-width: 96px; width: 6%; }
        .table-inv th.ifp-col-net,
        .table-inv td.ifp-col-net { min-width: 110px; width: 7%; }
        .table-inv th.ifp-col-actions,
        .table-inv td.ifp-col-actions { min-width: 48px; width: 3%; }
        .table-inv .form-control,
        .table-inv .form-select { padding: 0.12rem 0.18rem; margin: 0; min-height: 1.42rem; font-size: 0.765rem; box-sizing: border-box; min-width: 0; width: 100%; }
        .table-inv .form-select { padding-right: 1rem; background-position: right 0.2rem center; background-size: 10px 8px; }
        .table-inv textarea.form-control { padding: 0.12rem 0.18rem; margin: 0; min-height: 1.42rem; }
        .table-inv input[type="number"] { min-width: 0; padding-right: 0.14rem; text-align: right; font-variant-numeric: tabular-nums; }
        .table-inv input[type="number"]::-webkit-outer-spin-button,
        .table-inv input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .table-inv input[type="number"] { -moz-appearance: textfield; }
        .table-inv td.ifp-col-desc textarea.form-control { min-width: 200px; width: 100%; }
        .table-inv td.ifp-col-desc .ifp-readonly-desc {
            display: block;
            min-width: 320px;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
        }
        .table-inv .btn-icon { padding: 0.16rem 0.26rem; margin: 0; }
    </style>
    <div class="col-xl-12">
        <table class="table nowrap {{ $isReadonly ? '' : 'text-nowrap' }} table-borderless border mt-3 table-inv" cellpadding="0" cellspacing="0">
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
                    <th class="ifp-col-tax-sro ifp-col-tax-sro-header text-nowrap" scope="col" title="Tax classification and SRO — expand per row">
                        <span class="ifp-col-tax-sro-header__inner d-flex flex-column align-items-start gap-0 lh-sm">
                            <span class="ifp-col-tax-sro-header__title">Tax &amp; SRO</span>
                            <span class="ifp-col-tax-sro-header__hint">Per line</span>
                        </span>
                    </th>
                    @endif
                    <th class="ifp-col-qty">Qty</th>
                    @if(!$inModal)
                    <th>Rate</th>
                    @endif
                    <th class="ifp-col-amount">Amount</th>
                    @if($inModal)
                    <th class="ifp-col-fixed text-nowrap">Fixed notified value</th>
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

            @if($items->isNotEmpty())
                @foreach($items as $idx => $item)
                <tr class="ifp-row">
                    <td class="form-select-sm ifp-col-product">
                        @if($isReadonly)
                            <span class="small">{{ $item->ProductName ?? '-' }}</span>
                        @else
                            <select name="items[{{ $idx }}][ProductID]" class="{{ $selClass }} ifp-product">
                                <option value="">— Select —</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->ID }}"
                                            data-rate="{{ $prod->SalePrice ?? 0 }}"
                                            data-gst="{{ $prod->GstPercentage ?? 0 }}"
                                            data-hscode="{{ $prod->HsCode ?? '' }}"
                                            data-sale-information="{{ $prod->SaleInformation ?? '' }}"
                                            data-uom-id="{{ $prod->UomID ?? '' }}"
                                            data-sale-type-id="{{ $prod->SaleTypeID ?? '' }}"
                                            data-sale-type-to-rate-id="{{ $prod->SaleTypeToRateID ?? '' }}"
                                            data-sro-schedule-id="{{ $prod->SroScheduleID ?? '' }}"
                                            data-sro-item-id="{{ $prod->SroItemID ?? '' }}"
                                            data-tp-rate="{{ $prod->TPRate ?? 0 }}"
                                            data-is-fixed-rate="{{ in_array(strtolower(trim((string) ($prod->IsFixedRate ?? '0'))), ['1', 'true', 'yes', 'y', 'on'], true) ? 1 : 0 }}"
                                            {{ ($item->ProductID ?? 0) == $prod->ID ? 'selected' : '' }}>
                                        {{ $prod->ProductName ?? $prod->Name ?? $prod->Code ?? ('Product #' . $prod->ID) }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </td>
                    <td class="ifp-col-desc">
                        @if($isReadonly)
                            <span class="small ifp-readonly-desc">{{ $item->Details ?? $item->ProductDescription ?? '-' }}</span>
                        @else
                            <input type="hidden" name="items[{{ $idx }}][HsCode]" class="ifp-hscode" value="{{ $item->HsCode ?? '' }}">
                            <textarea name="items[{{ $idx }}][Details]" rows="1" class="form-control form-control-sm ifp-details" placeholder="Enter Description">{{ $item->Details ?? '' }}</textarea>
                        @endif
                    </td>
                    <td class="ifp-col-uom">
                        @if($isReadonly)
                            <span class="small">{{ $item->Uom_Desc ?? $item->UomName ?? $item->UomID ?? '-' }}</span>
                        @else
                            <select name="items[{{ $idx }}][UomID]" class="{{ $selClass }} ifp-uom" data-selected="{{ $item->UomID ?? '' }}">
                                <option value="">Select UOM</option>
                            </select>
                        @endif
                    </td>
                    @include('pages.dashboards.Invoice._invoice-line-tax-sro', [
                        'idx' => $idx,
                        'item' => $item,
                        'uid' => $uid,
                        'selClass' => $selClass,
                        'inModal' => $inModal,
                        'isReadonly' => $isReadonly,
                    ])
                    <td class="ifp-col-qty">
                        @if($isReadonly)
                            <span class="small">{{ $item->Quantity ?? 0 }}</span>
                        @else
                            <input type="number" name="items[{{ $idx }}][Quantity]" class="form-control form-control-sm ifp-qty" value="{{ $item->Quantity ?? 1 }}" min="0" step="0.000001">
                        @endif
                    </td>
                    @if(!$inModal)
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->Rate ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.000001" name="items[{{ $idx }}][Rate]" class="form-control form-control-sm ifp-rate" value="{{ $item->Rate ?? 0 }}" min="0">
                        @endif
                    </td>
                    @endif
                    <td class="ifp-col-amount">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->Amount ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][Amount]" class="form-control form-control-sm ifp-amount" value="{{ $item->Amount ?? 0 }}" readonly>
                        @endif
                    </td>
                    @if($inModal)
                    <td class="ifp-col-fixed">
                        @if($isReadonly)
                            <span class="small">{{ number_format((float) ($item->TPRate ?? 0) * (float) ($item->Quantity ?? 0), 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][FixedNotifiedValueOrRetailPrice]" class="form-control form-control-sm ifp-fixed-notified-value" value="{{ number_format((float) ($item->TPRate ?? 0) * (float) ($item->Quantity ?? 0), 2, '.', '') }}" readonly>
                        @endif
                    </td>
                    @endif
                    @if(!$inModal)
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->DiscountPercentage ?? 0 }}%</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][DiscountPercentage]" class="form-control form-control-sm ifp-disc-pct" value="{{ $item->DiscountPercentage ?? 0 }}" min="0" max="100">
                        @endif
                    </td>
                    @endif
                    <td class="ifp-col-disc-amt">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->DiscountAmount ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][DiscountAmount]" class="form-control form-control-sm ifp-disc-amt" value="{{ $item->DiscountAmount ?? 0 }}" readonly>
                        @endif
                    </td>
                    @if(!$inModal)
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->GstPercentage ?? 0 }}%</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][GstPercentage]" class="form-control form-control-sm ifp-gst-pct" value="{{ $item->GstPercentage ?? 0 }}" min="0">
                        @endif
                    </td>
                    @endif
                    <td class="ifp-col-gst-amt">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->GSTAmount ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][GSTAmount]" class="form-control form-control-sm ifp-gst-amt" value="{{ $item->GSTAmount ?? 0 }}" readonly>
                        @endif
                    </td>
                    <td class="ifp-col-net">
                        @if($isReadonly)
                            <span class="small fw-bold">{{ number_format($item->Total ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][Total]" class="form-control form-control-sm ifp-total fw-bold" value="{{ $item->Total ?? 0 }}" readonly>
                        @endif
                    </td>
                    <td class="ifp-col-extra-tax">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->ExtraTax ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][ExtraTax]" class="form-control form-control-sm ifp-extra-tax" value="{{ $item->ExtraTax ?? 0 }}" min="0">
                        @endif
                    </td>
                    <td class="ifp-col-further-tax">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->FurtherTax ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][FurtherTax]" class="form-control form-control-sm ifp-further-tax" value="{{ $item->FurtherTax ?? 0 }}" min="0">
                        @endif
                    </td>
                    <td class="ifp-col-fed-payable">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->FedPayable ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][FedPayable]" class="form-control form-control-sm ifp-fed-payable" value="{{ $item->FedPayable ?? 0 }}" min="0">
                        @endif
                    </td>
                    @if(!$isReadonly)
                    <td class="ifp-col-actions">
                        <button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button>
                    </td>
                    @endif
                </tr>
                @endforeach

            @elseif(!$isReadonly)
                {{-- Default empty row --}}
                <tr class="ifp-row">
                    <td class="form-select-sm ifp-col-product">
                        <select name="items[0][ProductID]" class="{{ $selClass }} ifp-product">
                            <option value="">— Select —</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->ID }}"
                                        data-rate="{{ $prod->SalePrice ?? 0 }}"
                                        data-gst="{{ $prod->GstPercentage ?? 0 }}"
                                        data-hscode="{{ $prod->HsCode ?? '' }}"
                                        data-sale-information="{{ $prod->SaleInformation ?? '' }}"
                                        data-uom-id="{{ $prod->UomID ?? '' }}"
                                        data-sale-type-id="{{ $prod->SaleTypeID ?? '' }}"
                                        data-sale-type-to-rate-id="{{ $prod->SaleTypeToRateID ?? '' }}"
                                        data-sro-schedule-id="{{ $prod->SroScheduleID ?? '' }}"
                                        data-sro-item-id="{{ $prod->SroItemID ?? '' }}"
                                        data-tp-rate="{{ $prod->TPRate ?? 0 }}"
                                        data-is-fixed-rate="{{ in_array(strtolower(trim((string) ($prod->IsFixedRate ?? '0'))), ['1', 'true', 'yes', 'y', 'on'], true) ? 1 : 0 }}">
                                    {{ $prod->ProductName ?? $prod->Name ?? $prod->Code ?? ('Product #' . $prod->ID) }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="ifp-col-desc">
                        <input type="hidden" name="items[0][HsCode]" class="ifp-hscode" value=""><textarea name="items[0][Details]" rows="1" class="form-control form-control-sm ifp-details" placeholder="Enter Description"></textarea>
                    </td>
                    <td class="ifp-col-uom"><select name="items[0][UomID]" class="{{ $selClass }} ifp-uom" data-selected=""><option value="">Select UOM</option></select></td>
                    @include('pages.dashboards.Invoice._invoice-line-tax-sro', [
                        'idx' => 0,
                        'item' => null,
                        'uid' => $uid,
                        'selClass' => $selClass,
                        'inModal' => $inModal,
                        'isReadonly' => false,
                    ])
                    <td class="ifp-col-qty"><input type="number" name="items[0][Quantity]" class="form-control form-control-sm ifp-qty" value="1" min="0" step="0.000001"></td>
                    @if(!$inModal)
                    <td><input type="number" step="0.000001" name="items[0][Rate]"               class="form-control form-control-sm ifp-rate"     value="0" min="0"></td>
                    @endif
                    <td class="ifp-col-amount"><input type="number" step="0.01"     name="items[0][Amount]"             class="form-control form-control-sm ifp-amount"   value="0" readonly></td>
                    @if($inModal)
                    <td class="ifp-col-fixed"><input type="number" step="0.01"     name="items[0][FixedNotifiedValueOrRetailPrice]" class="form-control form-control-sm ifp-fixed-notified-value" value="0" readonly></td>
                    @endif
                    @if(!$inModal)
                    <td><input type="number" step="0.01"     name="items[0][DiscountPercentage]" class="form-control form-control-sm ifp-disc-pct" value="0" min="0" max="100"></td>
                    @endif
                    <td class="ifp-col-disc-amt"><input type="number" step="0.01"     name="items[0][DiscountAmount]"     class="form-control form-control-sm ifp-disc-amt" value="0" readonly></td>
                    @if(!$inModal)
                    <td><input type="number" step="0.01"     name="items[0][GstPercentage]"      class="form-control form-control-sm ifp-gst-pct"  value="0" min="0"></td>
                    @endif
                    <td class="ifp-col-gst-amt"><input type="number" step="0.01"     name="items[0][GSTAmount]"          class="form-control form-control-sm ifp-gst-amt"  value="0" readonly></td>
                    <td class="ifp-col-net"><input type="number" step="0.01"     name="items[0][Total]"              class="form-control form-control-sm ifp-total fw-bold" value="0" readonly></td>
                    <td class="ifp-col-extra-tax"><input type="number" step="0.01"     name="items[0][ExtraTax]"           class="form-control form-control-sm ifp-extra-tax" value="0" min="0"></td>
                    <td class="ifp-col-further-tax"><input type="number" step="0.01"     name="items[0][FurtherTax]"         class="form-control form-control-sm ifp-further-tax" value="0" min="0"></td>
                    <td class="ifp-col-fed-payable"><input type="number" step="0.01"     name="items[0][FedPayable]"         class="form-control form-control-sm ifp-fed-payable" value="0" min="0"></td>
                    <td class="ifp-col-actions"><button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button></td>
                </tr>
            @else
                <tr><td colspan="{{ $ifpEmptyColspan }}" class="text-center text-muted py-3"><i>No line items found.</i></td></tr>
            @endif

            </tbody>
        </table>

        @if(!$isReadonly)
        <div class="my-3">
            <button type="button" class="btn btn-primary-light" id="{{ $uid }}_addRow">
                <i class="bi bi-plus-lg"></i> Add Product
            </button>
        </div>
        @endif

        {{-- ── Notes + Summary ── --}}
        @php
            $calcSubTotal = collect($items ?? [])->sum(fn($r) => (float) ($r->Amount ?? 0));
            $calcDiscount = collect($items ?? [])->sum(fn($r) => (float) ($r->DiscountAmount ?? 0));
            $calcGst      = collect($items ?? [])->sum(fn($r) => (float) ($r->GSTAmount ?? 0));
            $calcTotal    = collect($items ?? [])->sum(function($r) {
                $rowTotal = (float) ($r->Total ?? 0);
                if ($rowTotal > 0) {
                    return $rowTotal;
                }
                $amt  = (float) ($r->Amount ?? 0);
                $disc = (float) ($r->DiscountAmount ?? 0);
                $gst  = (float) ($r->GSTAmount ?? 0);
                return max(0, ($amt - $disc) + $gst);
            });

            $viewSubTotal = $invoice->SubTotal ?? $calcSubTotal;
            $viewDiscount = $invoice->TotalDiscount ?? $invoice->DiscountAmount ?? $calcDiscount;
            $viewGst      = $invoice->TotalGST ?? $invoice->GSTAmount ?? $calcGst;
            $viewGrand    = $invoice->TotalAmount ?? $invoice->Total ?? $calcTotal;
        @endphp
        <div class="row mt-4">
            <div class="col-md-9">
                <div class="clearfix w-50">
                    <label class="form-label fw-semibold">Notes</label>
                    @if($isReadonly)
                        <div class="form-control bg-light" style="min-height:80px;white-space:pre-line;">{{ $invoice->Notes ?? '-' }}</div>
                    @else
                        <textarea name="Notes"
                                  rows="4"
                                  class="form-control @error('Notes') is-invalid @enderror"
                                  placeholder="Any additional notes...">{{ old('Notes', $invoice->Notes ?? '') }}</textarea>
                        @error('Notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>
            </div>

            <div class="col text-end">
                <table class="table table-sm text-nowrap mb-0 table-borderless">
                    <tbody>
                        <tr>
                            <th scope="row"><div class="fw-medium">Sub Total :</div></th>
                            <td>
                                <input type="number" step="0.01" name="SubTotal" id="{{ $uid }}_SubTotal"
                                       class="form-control invoice-amount-input w-100"
                                       value="{{ $isReadonly ? number_format((float) $viewSubTotal, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><div class="fw-medium">Avail Discount :</div></th>
                            <td>
                                <input type="number" step="0.01" name="TotalDiscount" id="{{ $uid }}_TotalDiscount"
                                       class="form-control invoice-amount-input w-100"
                                       value="{{ $isReadonly ? number_format((float) $viewDiscount, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><div class="fw-medium">GST :</div></th>
                            <td>
                                <input type="number" step="0.01" name="TotalGST" id="{{ $uid }}_TotalGST"
                                       class="form-control invoice-amount-input w-100"
                                       value="{{ $isReadonly ? number_format((float) $viewGst, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><div class="fs-14 fw-medium">Total :</div></th>
                            <td>
                                <input type="number" step="0.01" name="GrandTotal" id="{{ $uid }}_GrandTotal"
                                       class="form-control invoice-amount-input w-100 fw-bold"
                                       value="{{ $isReadonly ? number_format((float) $viewGrand, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- end col-xl-12 --}}

</div>{{-- end row --}}

@if(!$isReadonly && $inModal)
    {{-- Nested modal: row Tax/SRO selects are moved here while open (stay inside <form> for submit) --}}
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

@if(!$isReadonly)
    <button type="submit" id="{{ $uid }}_submit" class="d-none">Submit</button>
@endif
@if($inModal)
</div>{{-- end ifp-modal-shell --}}
@endif
@if(!$isReadonly)
</form>
@endif


{{-- ════ JavaScript (create / edit only) ════ --}}
@if(!$isReadonly)
<script>
(function () {
    const uid  = '{{ $uid }}';
    const selCls = @json($selClass);
    const IFP_TAX_COMPACT = @json((bool) $inModal);
    const body = document.getElementById(uid + '_body');
    if (!body) return;

    var taxSroModalEl = null;
    var taxSroModalBody = null;
    var taxSroSlotHost = null;
    var taxSroSlotNode = null;

    function restoreTaxSroSlotToRow() {
        if (taxSroModalBody && taxSroSlotHost && taxSroSlotNode && taxSroModalBody.contains(taxSroSlotNode)) {
            taxSroSlotHost.appendChild(taxSroSlotNode);
            taxSroSlotNode.classList.add('d-none');
        }
        taxSroSlotHost = null;
        taxSroSlotNode = null;
    }

    function openTaxSroModalForRow(row) {
        if (!IFP_TAX_COMPACT || !taxSroModalEl || !taxSroModalBody) return;
        var td = row.querySelector('.ifp-col-tax-sro');
        var slot = td && td.querySelector('.ifp-tax-sro-slot');
        if (!td || !slot) return;
        restoreTaxSroSlotToRow();
        taxSroSlotHost = td;
        taxSroSlotNode = slot;
        taxSroModalBody.appendChild(slot);
        slot.classList.remove('d-none');
        bootstrap.Modal.getOrCreateInstance(taxSroModalEl).show();
    }

    if (IFP_TAX_COMPACT) {
        taxSroModalEl = document.getElementById(uid + '_taxSroModal');
        taxSroModalBody = document.getElementById(uid + '_taxSroModalBody');
        if (taxSroModalEl && taxSroModalBody) {
            body.addEventListener('click', function(ev) {
                var btn = ev.target.closest('.ifp-tax-sro-open-btn');
                if (!btn || !body.contains(btn)) return;
                var row = btn.closest('.ifp-row');
                if (row) openTaxSroModalForRow(row);
            });
            taxSroModalEl.addEventListener('hidden.bs.modal', function() {
                var slot = taxSroModalBody.querySelector('.ifp-tax-sro-slot');
                var host = taxSroSlotHost;
                if (slot && host) {
                    host.appendChild(slot);
                    slot.classList.add('d-none');
                }
                taxSroSlotHost = null;
                taxSroSlotNode = null;
            });
        }
    }

    const INV_HSCODE_URL       = "{{ route('products.hscode-info') }}";
    const INV_SALE_TYPES_URL   = "{{ route('products.sale-types') }}";
    const INV_SALE_TYPE_RATES  = "{{ route('products.sale-type-to-rates') }}";
    const INV_SRO_SCHEDULES    = "{{ route('products.sro-schedules') }}";
    const INV_SRO_ITEMS        = "{{ route('products.sro-items') }}";

    function n(v) { return parseFloat(v) || 0; }
    function rowEl(row, selector) {
        var fromRow = row ? row.querySelector(selector) : null;
        if (fromRow) return fromRow;
        if (!IFP_TAX_COMPACT || !taxSroSlotNode || !taxSroSlotHost) return null;
        var rowTd = row ? row.querySelector('.ifp-col-tax-sro') : null;
        if (rowTd && rowTd === taxSroSlotHost) {
            return taxSroSlotNode.querySelector(selector);
        }
        return null;
    }

    // ── Row calculation ──
    function calcRow(row) {
        const qty     = n(rowEl(row, '.ifp-qty')?.value);
        const rate    = n(rowEl(row, '.ifp-rate')?.value);
        const discPct = n(rowEl(row, '.ifp-disc-pct')?.value);
        const gstPct  = n(rowEl(row, '.ifp-gst-pct')?.value);
        let extraTax = n(rowEl(row, '.ifp-extra-tax')?.value);
        let furtherTax = n(rowEl(row, '.ifp-further-tax')?.value);
        let fedPayable = n(rowEl(row, '.ifp-fed-payable')?.value);
        const fixedRateEl = rowEl(row, '.ifp-fixed-rate');
        const fixedNotifiedEl = rowEl(row, '.ifp-fixed-notified-value');
        const fixedRate = n(fixedRateEl?.value);
        const isFixedHidden = rowEl(row, '.ifp-is-fixed-rate');
        const isFixed = (parseInt(isFixedHidden ? String(isFixedHidden.value) : '0', 10) || 0) === 1;
        const isFilerHidden = rowEl(row, '.ifp-is-filer');
        const isFiler = (parseInt(isFilerHidden ? String(isFilerHidden.value) : '1', 10) || 0) === 1;
        const extraTaxPct = n(rowEl(row, '.ifp-extra-tax-pct')?.value);
        const furtherTaxPct = n(rowEl(row, '.ifp-further-tax-pct')?.value);
        const fedPayablePct = n(rowEl(row, '.ifp-fed-payable-pct')?.value);
        const rateTypeRaw = (rowEl(row, '.ifp-rate-type')?.value || '');
        const rateType = String(rateTypeRaw).trim().toUpperCase();
        const saleTypeToRateSel = rowEl(row, '.ifp-sale-type-to-rate');
        const selectedRateOpt = saleTypeToRateSel && saleTypeToRateSel.selectedIndex >= 0
            ? saleTypeToRateSel.options[saleTypeToRateSel.selectedIndex]
            : null;
        const rateValue = n(selectedRateOpt && selectedRateOpt.getAttribute('data-rate-value'));

        const amount  = qty * rate;
        const discAmt = amount * discPct / 100;
        const taxable = amount - discAmt;
        const fixedNotified = fixedRate * qty;
        extraTax = amount * extraTaxPct / 100;
        furtherTax = amount * furtherTaxPct / 100;
        fedPayable = amount * fedPayablePct / 100;
        let gstAmt;
        if (rateType === 'Z') {
            gstAmt = 0;
            extraTax = 0;
            furtherTax = 0;
            fedPayable = 0;
            const extraTaxEl = rowEl(row, '.ifp-extra-tax');
            const furtherTaxEl = rowEl(row, '.ifp-further-tax');
            const fedPayableEl = rowEl(row, '.ifp-fed-payable');
            if (extraTaxEl) extraTaxEl.value = '0.00';
            if (furtherTaxEl) furtherTaxEl.value = '0.00';
            if (fedPayableEl) fedPayableEl.value = '0.00';
        } else if (rateType === 'R') {
            gstAmt = rateValue * qty;
        } else if (rateType === 'H') {
            const gstBase = isFixed ? fixedNotified : taxable;
            gstAmt = (60 * qty) + (gstBase * gstPct / 100);
        } else {
            const gstBase = isFixed ? fixedNotified : taxable;
            gstAmt = gstBase * gstPct / 100;
        }
        const total   = !isFiler
            ? (taxable + extraTax + furtherTax + fedPayable)
            : (taxable + gstAmt + extraTax + furtherTax + fedPayable);

        function set(cls, val) {
            const el = rowEl(row, '.' + cls);
            if (el) el.value = val.toFixed(2);
        }
        set('ifp-amount',   amount);
        set('ifp-disc-amt', discAmt);
        set('ifp-extra-tax', extraTax);
        set('ifp-further-tax', furtherTax);
        set('ifp-fed-payable', fedPayable);
        set('ifp-gst-amt',  gstAmt);
        set('ifp-total',    total);
        if (fixedNotifiedEl) fixedNotifiedEl.value = fixedNotified.toFixed(2);

        return { amount, discAmt, gstAmt, total };
    }

    // ── Summary totals ──
    function calcSummary() {
        let sub = 0, disc = 0, gst = 0, grand = 0;
        body.querySelectorAll('.ifp-row').forEach(function(row) {
            const r = calcRow(row);
            sub   += r.amount;
            disc  += r.discAmt;
            gst   += r.gstAmt;
            grand += r.total;
        });
        function s(id, v) {
            const el = document.getElementById(uid + '_' + id);
            if (el) el.value = v.toFixed(2);
        }
        s('SubTotal',      sub);
        s('TotalDiscount', disc);
        s('TotalGST',      gst);
        s('GrandTotal',    grand);
    }

    // ── Bind row events ──
    function bindRow(row) {
        // Recalculate summary on input change for any relevant field
        row.querySelectorAll('.ifp-qty, .ifp-rate, .ifp-disc-pct, .ifp-gst-pct, .ifp-extra-tax, .ifp-further-tax, .ifp-fed-payable').forEach(function(inp) {
            inp.addEventListener('input', function() {
                calcRow(row);
                calcSummary();
            });
        });

        // Product select → auto-fill rate, GST, and product fields (HsCode, Description, UOM, Sale Type cascade)
        const prodSel = row.querySelector('.ifp-product');
        const detailsTaInline = row.querySelector('textarea.ifp-details');
        if (detailsTaInline) {
            detailsTaInline.addEventListener('focus', function() {
                detailsTaInline.rows = 3;
                detailsTaInline.setAttribute('title', detailsTaInline.value || '');
            });
            detailsTaInline.addEventListener('input', function() {
                detailsTaInline.setAttribute('title', detailsTaInline.value || '');
            });
            detailsTaInline.addEventListener('blur', function() {
                detailsTaInline.rows = 1;
            });
        }
        if (prodSel) {
            const applySelectedProductToRow = function(forceDetailsFill) {
                const opt = (prodSel.selectedOptions && prodSel.selectedOptions.length)
                    ? prodSel.selectedOptions[0]
                    : prodSel.options[prodSel.selectedIndex];
                if (!opt) return;
                const rateEl = rowEl(row, '.ifp-rate');
                const gstEl  = rowEl(row, '.ifp-gst-pct');
                const ds = opt.dataset || {};
                if (forceDetailsFill) {
                    if (rateEl) rateEl.value = ds.rate || opt.getAttribute('data-rate') || 0;
                    if (gstEl)  gstEl.value  = ds.gst  || opt.getAttribute('data-gst')  || 0;
                }

                const hsCode = ds.hscode || opt.getAttribute('data-hscode') || '';
                const saleInfo = ds.saleInformation || opt.getAttribute('data-sale-information') || '';
                const uomId = ds.uomId || opt.getAttribute('data-uom-id') || '';
                const saleTypeId = ds.saleTypeId || opt.getAttribute('data-sale-type-id') || '';
                const saleTypeToRateId = ds.saleTypeToRateId || opt.getAttribute('data-sale-type-to-rate-id') || '';
                const sroScheduleId = ds.sroScheduleId || opt.getAttribute('data-sro-schedule-id') || '';
                const sroItemId = ds.sroItemId || opt.getAttribute('data-sro-item-id') || '';
                const tpRate = ds.tpRate || opt.getAttribute('data-tp-rate') || 0;
                const isFixedRaw = ds.isFixedRate != null && ds.isFixedRate !== ''
                    ? ds.isFixedRate
                    : (opt.getAttribute('data-is-fixed-rate') || '0');
                const isFixedVal = (parseInt(String(isFixedRaw), 10) || 0) ? 1 : 0;

                const hsInp = rowEl(row, '.ifp-hscode');
                const detailsTa = rowEl(row, 'textarea[name*="[Details]"]');
                const uomSel = rowEl(row, '.ifp-uom');
                const saleTypeSel = rowEl(row, '.ifp-sale-type');
                const saleTypeToRateSel = rowEl(row, '.ifp-sale-type-to-rate');
                const sroScheduleSel = rowEl(row, '.ifp-sro-schedule');
                const sroItemSel = rowEl(row, '.ifp-sro-item');
                const fixedRateEl = rowEl(row, '.ifp-fixed-rate');
                const isFixedHidden = rowEl(row, '.ifp-is-fixed-rate');
                const isFixedCb = rowEl(row, '.ifp-is-fixed-rate-cb');

                if (hsInp) hsInp.value = hsCode;
                if (detailsTa) {
                    const isBlank = String(detailsTa.value || '').trim() === '';
                    if (forceDetailsFill || isBlank) {
                        detailsTa.value = saleInfo;
                        // Trigger listeners bound by autosize/validators in some modal contexts.
                        detailsTa.dispatchEvent(new Event('input', { bubbles: true }));
                        detailsTa.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
                if (uomSel) {
                    if (forceDetailsFill || !uomSel.getAttribute('data-selected')) {
                        uomSel.setAttribute('data-selected', uomId);
                    }
                    fetchHsCodeInfoForRow(row);
                }
                if (saleTypeSel && (forceDetailsFill || !saleTypeSel.getAttribute('data-selected'))) saleTypeSel.setAttribute('data-selected', saleTypeId);
                if (saleTypeToRateSel && (forceDetailsFill || !saleTypeToRateSel.getAttribute('data-selected'))) saleTypeToRateSel.setAttribute('data-selected', saleTypeToRateId);
                if (sroScheduleSel && (forceDetailsFill || !sroScheduleSel.getAttribute('data-selected'))) sroScheduleSel.setAttribute('data-selected', sroScheduleId);
                if (sroItemSel && (forceDetailsFill || !sroItemSel.getAttribute('data-selected'))) sroItemSel.setAttribute('data-selected', sroItemId);
                if (fixedRateEl) fixedRateEl.value = n(tpRate).toFixed(2);
                if (isFixedHidden) isFixedHidden.value = String(isFixedVal);
                if (isFixedCb) isFixedCb.checked = !!isFixedVal;
                if (forceDetailsFill) {
                    loadSaleTypesForRow(row);
                }

                calcRow(row);
                calcSummary();
            };

            prodSel.addEventListener('change', function() {
                applySelectedProductToRow(true);
            });

            // On Edit modal open, pre-fill row using current selected product (without overriding typed description).
            if (prodSel.value) {
                applySelectedProductToRow(false);
            }
        }

        applyCustomerMetaToRow(row);

        // Delete row
        const delBtn = row.querySelector('.ifp-del');
        if (delBtn) {
            delBtn.addEventListener('click', function() {
                if (body.querySelectorAll('.ifp-row').length <= 1) {
                    alert('At least one line item is required.');
                    return;
                }
                row.remove();
                // reIndex(); // Optional if you handle indices on server-side differently, but good practice
                calcSummary();
            });
        }

    }

    // ── Re-index row names after delete ──
    function reIndex() {
        body.querySelectorAll('.ifp-row').forEach(function(row, i) {
            row.querySelectorAll('[name]').forEach(function(el) {
                el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
            });
        });
    }

    function getCustomerMeta() {
        const customerSel = document.getElementById(uid + '_CustomerID');
        if (!customerSel || customerSel.selectedIndex < 0) {
            return { isFiler: 0, salesTaxWithheldAtSource: 0, extraTaxPct: 0, furtherTaxPct: 0, fedPayablePct: 0 };
        }
        const opt = customerSel.options[customerSel.selectedIndex];
        const ds = opt ? (opt.dataset || {}) : {};
        const isFilerRaw = ds.isFiler != null && ds.isFiler !== '' ? ds.isFiler : (opt?.getAttribute('data-is-filer') || '0');
        const withheldRaw = ds.salesTaxWithheld != null && ds.salesTaxWithheld !== '' ? ds.salesTaxWithheld : (opt?.getAttribute('data-sales-tax-withheld') || '0');
        const extraTaxPctRaw = ds.extraTaxPct != null && ds.extraTaxPct !== '' ? ds.extraTaxPct : (opt?.getAttribute('data-extra-tax-pct') || '0');
        const furtherTaxPctRaw = ds.furtherTaxPct != null && ds.furtherTaxPct !== '' ? ds.furtherTaxPct : (opt?.getAttribute('data-further-tax-pct') || '0');
        const fedPayablePctRaw = ds.fedPayablePct != null && ds.fedPayablePct !== '' ? ds.fedPayablePct : (opt?.getAttribute('data-fed-payable-pct') || '0');
        return {
            isFiler: (parseInt(String(isFilerRaw), 10) || 0) ? 1 : 0,
            salesTaxWithheldAtSource: n(withheldRaw),
            extraTaxPct: n(extraTaxPctRaw),
            furtherTaxPct: n(furtherTaxPctRaw),
            fedPayablePct: n(fedPayablePctRaw),
        };
    }

    function applyCustomerMetaToRow(row) {
        const meta = getCustomerMeta();
        const isFilerHidden = rowEl(row, '.ifp-is-filer');
        const isFilerCb = rowEl(row, '.ifp-is-filer-cb');
        const withheldInput = rowEl(row, '.ifp-sales-tax-withheld');
        const extraTaxPctInput = rowEl(row, '.ifp-extra-tax-pct');
        const furtherTaxPctInput = rowEl(row, '.ifp-further-tax-pct');
        const fedPayablePctInput = rowEl(row, '.ifp-fed-payable-pct');
        if (isFilerHidden) isFilerHidden.value = String(meta.isFiler);
        if (isFilerCb) isFilerCb.checked = !!meta.isFiler;
        if (withheldInput) withheldInput.value = meta.salesTaxWithheldAtSource.toFixed(2);
        if (extraTaxPctInput) extraTaxPctInput.value = meta.extraTaxPct.toFixed(2);
        if (furtherTaxPctInput) furtherTaxPctInput.value = meta.furtherTaxPct.toFixed(2);
        if (fedPayablePctInput) fedPayablePctInput.value = meta.fedPayablePct.toFixed(2);
    }

    // ── Tax dropdowns (per row): HS Code → UOM; Sale Type → Sale Type To Rate → SRO Schedule → SRO Item ──
    function clearUomForRow(row) {
        var sel = rowEl(row, '.ifp-uom');
        if (!sel) return;
        sel.innerHTML = '';
        var opt = document.createElement('option');
        opt.value = ''; opt.textContent = 'Select UOM';
        sel.appendChild(opt);
    }

    function fetchHsCodeInfoForRow(row) {
        var hsInp = rowEl(row, '.ifp-hscode');
        var uomSel = rowEl(row, '.ifp-uom');
        var code = (hsInp && hsInp.value) ? hsInp.value.trim() : '';
        if (uomSel) clearUomForRow(row);
        if (!code) return;
        fetch(INV_HSCODE_URL + '?code=' + encodeURIComponent(code), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !uomSel) return;
                var selected = uomSel.getAttribute('data-selected') || '';
                clearUomForRow(row);
                if (Array.isArray(data.uoms) && data.uoms.length) {
                    data.uoms.forEach(function(item) {
                        var opt = document.createElement('option');
                        opt.value = item.id; opt.textContent = item.text;
                        if (selected && String(selected) === String(item.id)) opt.selected = true;
                        uomSel.appendChild(opt);
                    });
                }
            })
            .catch(function() {});
    }

    function loadSaleTypesForRow(row) {
        var sel = rowEl(row, '.ifp-sale-type');
        if (!sel) return;
        var selected = sel.getAttribute('data-selected') || '';
        sel.innerHTML = '';
        var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select Sale Type'; sel.appendChild(ph);
        fetch(INV_SALE_TYPES_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) return;
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.id; opt.textContent = item.text;
                    if (selected && String(selected) === String(item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                var v = sel.value;
                if (v) loadSaleTypeToRatesForRow(row, v);
            })
            .catch(function() {});
    }

    function loadSaleTypeToRatesForRow(row, transactionTypeId) {
        var sel = rowEl(row, '.ifp-sale-type-to-rate');
        if (!sel) return;
        var selected = sel.getAttribute('data-selected') || '';
        sel.innerHTML = '';
        var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select Sale Type To Rate'; sel.appendChild(ph);
        if (!transactionTypeId) return;
        fetch(INV_SALE_TYPE_RATES + '?transaction_type_id=' + encodeURIComponent(transactionTypeId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) return;
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.id; opt.textContent = item.text;
                    var rp = (item.rate_pct !== undefined && item.rate_pct !== null && item.rate_pct !== '') ? String(item.rate_pct) : '';
                    if (rp) opt.setAttribute('data-rate-pct', rp);
                    var rv = (item.rate_value !== undefined && item.rate_value !== null && item.rate_value !== '') ? String(item.rate_value) : '';
                    if (rv) opt.setAttribute('data-rate-value', rv);
                    var rt = (item.rate_type !== undefined && item.rate_type !== null && item.rate_type !== '') ? String(item.rate_type) : '';
                    if (rt) opt.setAttribute('data-rate-type', rt);
                    if (selected && String(selected) === String(item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                var v = sel.value;
                if (v) {
                    var opt = sel.options[sel.selectedIndex];
                    if (opt && opt.getAttribute('data-rate-pct')) {
                        var gstEl = rowEl(row, '.ifp-gst-pct');
                        if (gstEl) gstEl.value = opt.getAttribute('data-rate-pct');
                    }
                    var rtInp = rowEl(row, '.ifp-rate-type');
                    if (rtInp) rtInp.value = (opt && opt.getAttribute('data-rate-type')) ? opt.getAttribute('data-rate-type') : '';
                    calcRow(row);
                    calcSummary();
                    loadSroSchedulesForRow(row, v);
                }
            })
            .catch(function() {});
    }

    function loadSroSchedulesForRow(row, rateId) {
        var sel = rowEl(row, '.ifp-sro-schedule');
        if (!sel) return;
        var selected = sel.getAttribute('data-selected') || '';
        sel.innerHTML = '';
        var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select SRO Schedule'; sel.appendChild(ph);
        if (!rateId) return;
        fetch(INV_SRO_SCHEDULES + '?rate_id=' + encodeURIComponent(rateId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) return;
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.id; opt.textContent = item.text;
                    if (selected && String(selected) === String(item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                var v = sel.value;
                if (v) loadSroItemsForRow(row, v);
            })
            .catch(function() {});
    }

    function loadSroItemsForRow(row, sroId) {
        var sel = rowEl(row, '.ifp-sro-item');
        if (!sel) return;
        var selected = sel.getAttribute('data-selected') || '';
        sel.innerHTML = '';
        var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select SRO Item'; sel.appendChild(ph);
        if (!sroId) return;
        fetch(INV_SRO_ITEMS + '?sro_id=' + encodeURIComponent(sroId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) return;
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.id; opt.textContent = item.text;
                    if (selected && String(selected) === String(item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
            })
            .catch(function() {});
    }

    function bindRowTaxDropdowns(row) {
        var hsInp = rowEl(row, '.ifp-hscode') || row.querySelector('.ifp-hscode');
        var uomSel = row.querySelector('.ifp-uom');
        var saleTypeSel = rowEl(row, '.ifp-sale-type');
        var saleTypeToRateSel = rowEl(row, '.ifp-sale-type-to-rate');
        var sroScheduleSel = rowEl(row, '.ifp-sro-schedule');
        var sroItemSel = rowEl(row, '.ifp-sro-item');

        if (hsInp && uomSel && hsInp.value && String(hsInp.value).trim() !== '') {
            fetchHsCodeInfoForRow(row);
        }

        if (saleTypeSel) {
            saleTypeSel.addEventListener('change', function() {
                var v = saleTypeSel.value || '';
                var rtInp = rowEl(row, '.ifp-rate-type');
                if (saleTypeToRateSel) {
                    saleTypeToRateSel.setAttribute('data-selected', '');
                    saleTypeToRateSel.innerHTML = '';
                    var o = document.createElement('option'); o.value = ''; o.textContent = 'Select Sale Type first'; saleTypeToRateSel.appendChild(o);
                }
                if (rtInp) rtInp.value = '';
                if (sroScheduleSel) {
                    sroScheduleSel.innerHTML = '';
                    var o = document.createElement('option'); o.value = ''; o.textContent = 'Select Sale Type To Rate first'; sroScheduleSel.appendChild(o);
                }
                if (sroItemSel) {
                    sroItemSel.innerHTML = '';
                    var o = document.createElement('option'); o.value = ''; o.textContent = 'Select SRO Schedule first'; sroItemSel.appendChild(o);
                }
                loadSaleTypeToRatesForRow(row, v);
            });
        }
        if (saleTypeToRateSel) {
            saleTypeToRateSel.addEventListener('change', function() {
                var v = saleTypeToRateSel.value || '';
                var gstEl = rowEl(row, '.ifp-gst-pct');
                var rtInp = rowEl(row, '.ifp-rate-type');
                if (v) {
                    var opt = saleTypeToRateSel.options[saleTypeToRateSel.selectedIndex];
                    if (opt && opt.getAttribute('data-rate-pct') && gstEl) {
                        gstEl.value = opt.getAttribute('data-rate-pct');
                    }
                    if (rtInp) rtInp.value = (opt && opt.getAttribute('data-rate-type')) ? opt.getAttribute('data-rate-type') : '';
                    calcRow(row);
                    calcSummary();
                } else {
                    if (gstEl) gstEl.value = '0';
                    if (rtInp) rtInp.value = '';
                }
                if (sroScheduleSel) {
                    sroScheduleSel.setAttribute('data-selected', '');
                    sroScheduleSel.innerHTML = '';
                    var o = document.createElement('option'); o.value = ''; o.textContent = 'Select Sale Type To Rate first'; sroScheduleSel.appendChild(o);
                }
                if (sroItemSel) {
                    sroItemSel.innerHTML = '';
                    var o = document.createElement('option'); o.value = ''; o.textContent = 'Select SRO Schedule first'; sroItemSel.appendChild(o);
                }
                loadSroSchedulesForRow(row, v);
            });
        }
        if (sroScheduleSel) {
            sroScheduleSel.addEventListener('change', function() {
                var v = sroScheduleSel.value || '';
                if (sroItemSel) sroItemSel.setAttribute('data-selected', '');
                loadSroItemsForRow(row, v);
            });
        }
    }

    // ── Add Row ──
    const addBtn = document.getElementById(uid + '_addRow');
    if (addBtn) addBtn.addEventListener('click', function() {
        const idx         = body.querySelectorAll('.ifp-row').length;
        const firstSel    = body.querySelector('.ifp-product');
        let optionsHTML   = firstSel ? firstSel.innerHTML : '';
        // Clear any 'selected' attribute from the cloned options so the new row starts empty/default
        optionsHTML = optionsHTML.replace(/selected/gi, '');

        const tr = document.createElement('tr');
        tr.className = 'ifp-row';
        var taxSroCells = IFP_TAX_COMPACT
            ? (`<td class="ifp-col-tax-sro align-middle">
                    <button type="button" class="ifp-tax-sro-open-btn" title="Open Tax &amp; SRO in a dialog" aria-haspopup="dialog">
                        <span class="ifp-tax-sro-trigger__icon" aria-hidden="true"><i class="ri-equalizer-line"></i></span>
                        <span class="ifp-tax-sro-trigger__text"><span class="ifp-tax-sro-trigger__title">Tax &amp; SRO</span><span class="ifp-tax-sro-trigger__sub">Configure</span></span>
                        <i class="ri-arrow-right-s-line ifp-tax-sro-trigger__chev" aria-hidden="true"></i>
                    </button>
                    <div class="ifp-tax-sro-slot d-none">
                        <div class="ifp-tax-sro-fields">
                            <div class="ifp-tax-sro-fields__head">Classification &amp; SRO</div>
                            <div class="ifp-tax-sro-fields__body">
                                <div class="row g-2">
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_st_${idx}">Sale type</label><select id="${uid}_st_${idx}" name="items[${idx}][SaleTypeID]" class="${selCls} ifp-sale-type" data-selected=""><option value="">Loading…</option></select></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_str_${idx}">Sale type to rate</label><select id="${uid}_str_${idx}" name="items[${idx}][SaleTypeToRateID]" class="${selCls} ifp-sale-type-to-rate" data-selected=""><option value="">Select sale type first</option></select><input type="hidden" name="items[${idx}][RateType]" class="ifp-rate-type" value=""></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_sros_${idx}">SRO schedule</label><select id="${uid}_sros_${idx}" name="items[${idx}][SroScheduleID]" class="${selCls} ifp-sro-schedule" data-selected=""><option value="">Select rate profile first</option></select></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_sroi_${idx}">SRO item</label><select id="${uid}_sroi_${idx}" name="items[${idx}][SroItemID]" class="${selCls} ifp-sro-item" data-selected=""><option value="">Select SRO schedule first</option></select></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_ifiler_${idx}">Is filer</label><input type="hidden" name="items[${idx}][IsFiler]" class="ifp-is-filer" value="0"><div class="form-check ps-0 pt-1"><input type="checkbox" id="${uid}_ifiler_${idx}" class="form-check-input ifp-is-filer-cb m-0" disabled></div></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_ifr_${idx}">Is fixed</label><input type="hidden" name="items[${idx}][IsFixedRate]" class="ifp-is-fixed-rate" value="0"><div class="form-check ps-0 pt-1"><input type="checkbox" id="${uid}_ifr_${idx}" class="form-check-input ifp-is-fixed-rate-cb m-0" disabled></div></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_lr_${idx}">Rate</label><input type="number" step="0.000001" id="${uid}_lr_${idx}" name="items[${idx}][Rate]" class="form-control ifp-rate" value="0" min="0"></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_fr_${idx}">Fixed rate</label><input type="number" step="0.01" id="${uid}_fr_${idx}" name="items[${idx}][TPRate]" class="form-control ifp-fixed-rate" value="0" readonly></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_dp_${idx}">Discount %</label><input type="number" step="0.01" id="${uid}_dp_${idx}" name="items[${idx}][DiscountPercentage]" class="form-control ifp-disc-pct" value="0" min="0" max="100"></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_gst_${idx}">GST rate %</label><input type="number" step="0.01" id="${uid}_gst_${idx}" name="items[${idx}][GstPercentage]" class="form-control ifp-gst-pct" value="0" min="0"></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_stw_${idx}">Sales tax withheld at source</label><input type="number" step="0.01" id="${uid}_stw_${idx}" name="items[${idx}][SalesTaxWithheldAtSource]" class="form-control ifp-sales-tax-withheld" value="0" readonly></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_etp_${idx}">Extra tax %</label><input type="number" step="0.01" id="${uid}_etp_${idx}" class="form-control ifp-extra-tax-pct" value="0" readonly></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_ftp_${idx}">Further tax %</label><input type="number" step="0.01" id="${uid}_ftp_${idx}" class="form-control ifp-further-tax-pct" value="0" readonly></div></div>
                                    <div class="col-6"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_fpp_${idx}">FED payable %</label><input type="number" step="0.01" id="${uid}_fpp_${idx}" class="form-control ifp-fed-payable-pct" value="0" readonly></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>`)
            : ('<td><select name="items[' + idx + '][SaleTypeID]" class="' + selCls + ' ifp-sale-type" data-selected=""><option value="">Loading...</option></select></td>' +
                '<td><select name="items[' + idx + '][SaleTypeToRateID]" class="' + selCls + ' ifp-sale-type-to-rate" data-selected=""><option value="">Select Sale Type first</option></select><input type="hidden" name="items[' + idx + '][RateType]" class="ifp-rate-type" value=""></td>' +
                '<td><select name="items[' + idx + '][SroScheduleID]" class="' + selCls + ' ifp-sro-schedule" data-selected=""><option value="">Select Sale Type To Rate first</option></select></td>' +
                '<td><select name="items[' + idx + '][SroItemID]" class="' + selCls + ' ifp-sro-item" data-selected=""><option value="">Select SRO Schedule first</option></select></td>');
        var rowNumericTail = IFP_TAX_COMPACT
            ? (
                '<td class="ifp-col-qty"><input type="number" name="items[' + idx + '][Quantity]" class="form-control form-control-sm ifp-qty" value="1" min="0" step="0.000001"></td>' +
                '<td class="ifp-col-amount"><input type="number" step="0.01"     name="items[' + idx + '][Amount]"             class="form-control form-control-sm ifp-amount"   value="0" readonly></td>' +
                '<td class="ifp-col-fixed"><input type="number" step="0.01"     name="items[' + idx + '][FixedNotifiedValueOrRetailPrice]" class="form-control form-control-sm ifp-fixed-notified-value" value="0" readonly></td>' +
                '<td class="ifp-col-disc-amt"><input type="number" step="0.01"     name="items[' + idx + '][DiscountAmount]"     class="form-control form-control-sm ifp-disc-amt" value="0" readonly></td>' +
                '<td class="ifp-col-gst-amt"><input type="number" step="0.01"     name="items[' + idx + '][GSTAmount]"          class="form-control form-control-sm ifp-gst-amt"  value="0" readonly></td>' +
                '<td class="ifp-col-net"><input type="number" step="0.01"     name="items[' + idx + '][Total]"              class="form-control form-control-sm ifp-total fw-bold" value="0" readonly></td>' +
                '<td class="ifp-col-extra-tax"><input type="number" step="0.01"     name="items[' + idx + '][ExtraTax]"           class="form-control form-control-sm ifp-extra-tax" value="0" min="0"></td>' +
                '<td class="ifp-col-further-tax"><input type="number" step="0.01"     name="items[' + idx + '][FurtherTax]"         class="form-control form-control-sm ifp-further-tax" value="0" min="0"></td>' +
                '<td class="ifp-col-fed-payable"><input type="number" step="0.01"     name="items[' + idx + '][FedPayable]"         class="form-control form-control-sm ifp-fed-payable" value="0" min="0"></td>' +
                '<td class="ifp-col-actions"><button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button></td>'
            )
            : (
                '<td class="ifp-col-qty"><input type="number" name="items[' + idx + '][Quantity]" class="form-control form-control-sm ifp-qty" value="1" min="0" step="0.000001"></td>' +
                '<td><input type="number" step="0.000001" name="items[' + idx + '][Rate]"               class="form-control form-control-sm ifp-rate"     value="0" min="0"></td>' +
                '<td class="ifp-col-amount"><input type="number" step="0.01"     name="items[' + idx + '][Amount]"             class="form-control form-control-sm ifp-amount"   value="0" readonly></td>' +
                '<td><input type="number" step="0.01"     name="items[' + idx + '][DiscountPercentage]" class="form-control form-control-sm ifp-disc-pct" value="0" min="0" max="100"></td>' +
                '<td class="ifp-col-disc-amt"><input type="number" step="0.01"     name="items[' + idx + '][DiscountAmount]"     class="form-control form-control-sm ifp-disc-amt" value="0" readonly></td>' +
                '<td><input type="number" step="0.01"     name="items[' + idx + '][GstPercentage]"      class="form-control form-control-sm ifp-gst-pct"  value="0" min="0"></td>' +
                '<td class="ifp-col-gst-amt"><input type="number" step="0.01"     name="items[' + idx + '][GSTAmount]"          class="form-control form-control-sm ifp-gst-amt"  value="0" readonly></td>' +
                '<td class="ifp-col-net"><input type="number" step="0.01"     name="items[' + idx + '][Total]"              class="form-control form-control-sm ifp-total fw-bold" value="0" readonly></td>' +
                '<td class="ifp-col-extra-tax"><input type="number" step="0.01"     name="items[' + idx + '][ExtraTax]"           class="form-control form-control-sm ifp-extra-tax" value="0" min="0"></td>' +
                '<td class="ifp-col-further-tax"><input type="number" step="0.01"     name="items[' + idx + '][FurtherTax]"         class="form-control form-control-sm ifp-further-tax" value="0" min="0"></td>' +
                '<td class="ifp-col-fed-payable"><input type="number" step="0.01"     name="items[' + idx + '][FedPayable]"         class="form-control form-control-sm ifp-fed-payable" value="0" min="0"></td>' +
                '<td class="ifp-col-actions"><button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button></td>'
            );
        var descCell = '<td class="ifp-col-desc"><input type="hidden" name="items[' + idx + '][HsCode]" class="ifp-hscode" value=""><textarea name="items[' + idx + '][Details]" rows="1" class="form-control form-control-sm ifp-details" placeholder="Enter Description"></textarea></td>';
        tr.innerHTML =
            '<td class="ifp-col-product"><select name="items[' + idx + '][ProductID]" class="' + selCls + ' ifp-product">' + optionsHTML + '</select></td>' +
            descCell +
            '<td class="ifp-col-uom"><select name="items[' + idx + '][UomID]" class="' + selCls + ' ifp-uom" data-selected=""><option value="">Select UOM</option></select></td>' +
            taxSroCells +
            rowNumericTail;

        body.appendChild(tr);
        bindRow(tr);
        bindRowTaxDropdowns(tr);
        loadSaleTypesForRow(tr);
    });

    // ── Modal: intercept form submit → trigger save btn ──
    @if($inModal)
    const form = document.getElementById(uid + '_form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('invoice_modal_save_btn');
            if (btn) btn.click();
        });
    }
    @endif

    // ── Bind existing rows + initial calc ──
    const customerSel = document.getElementById(uid + '_CustomerID');
    if (customerSel) {
        customerSel.addEventListener('change', function() {
            body.querySelectorAll('.ifp-row').forEach(function(row) {
                applyCustomerMetaToRow(row);
                calcRow(row);
            });
            calcSummary();
        });
    }

    body.querySelectorAll('.ifp-row').forEach(function(row) {
        bindRow(row);
        bindRowTaxDropdowns(row);
        loadSaleTypesForRow(row);
    });
    calcSummary();

})();
</script>
@endif