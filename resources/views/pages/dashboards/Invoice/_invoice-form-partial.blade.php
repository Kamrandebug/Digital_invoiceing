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
    $ifpEmptyColspan = $inModal ? ($isReadonly ? 14 : 15) : ($isReadonly ? 17 : 18);

    $hasFbrQty = false;
    foreach ($items as $item) {
        if (($item->Unit ?? 0) > 1 || ($item->FBRQty ?? 0) > 0) {
            $hasFbrQty = true;
            break;
        }
    }
@endphp

{{-- ── Info Badge (edit / show) — full page only; hidden in AJAX View/Edit modal --}}
@if(!$isCreate && $invoice && !$inModal)
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

{{-- Status Banner for Cancelled Invoices --}}
@if($invoice && (int)($invoice->Statusi ?? 0) === 8)
<div class="alert alert-warning border border-warning-subtle shadow-sm py-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background-color: #fffbeb; border-color: #fde68a !important; color: #92400e; border-radius: 6px;">
    <div class="d-flex align-items-center gap-2">
        <i class="fas fa-ban fs-16 text-warning"></i>
        <div>
            <strong class="fs-13">Invoice Status: FBR Cancelled</strong>
            <span class="fs-12 text-muted ms-1">— This invoice has been marked as cancelled and is not valid for fiscal transactions.</span>
        </div>
    </div>
    @if(!empty(trim((string)($invoice->FbrTransactionID ?? ''))))
    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 fs-11">
        FBR TxID: {{ $invoice->FbrTransactionID }}
    </span>
    @endif
</div>
@endif

@if($inModal)
<div class="ifp-modal-shell">
    <div class="_pfp-tab-panel-shell border rounded overflow-hidden mb-3">
        <div class="_pfp-tab-panel-body">
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
            @php
                $cName = trim($invoice->CustomerDisplayName ?? '');
                $cNtn = trim((string)($invoice->CustomerNTN ?? ($invoice->BuyerNTNCNIC ?? '')));
                $cDisplay = $cName . ($cNtn !== '' ? ' - ' . $cNtn : '');
            @endphp
            <div class="form-control form-control-sm bg-light">{{ $cDisplay ?: '-' }}</div>
        @else
            <div class="d-flex gap-1 align-items-center ifp-customer-select-wrap">
            <select name="CustomerID"
                    id="{{ $uid }}_CustomerID"
                    class="{{ $selClass }} @error('CustomerID') is-invalid @enderror flex-grow-1"
                    style="min-width:0;"
                    required>
                <option value="">— Select Customer —</option>
                @foreach($customers as $cust)
                    <option value="{{ $cust->ID }}"
                            data-is-filer="{{ (int) ($cust->IsFiler ?? 0) ? 1 : 0 }}"
                            data-sales-tax-withheld="{{ $cust->SalesTaxWithheldAtSource ?? 0 }}"
                            data-extra-tax-pct="{{ $cust->ExtraTax ?? 0 }}"
                            data-further-tax-pct="{{ $cust->FurtherTax ?? 0 }}"
                            data-fed-payable-pct="{{ $cust->FedPayable ?? 0 }}"
                            data-is-advance-tax="{{ (int) ($cust->IsAdvanceTax ?? 0) === 1 ? 1 : 0 }}"
                            data-advance-tax-rate="{{ (float) ($cust->AdvanceTax ?? 0) }}"
                            {{ old('CustomerID', $invoice->CustomerID ?? '') == $cust->ID ? 'selected' : '' }}>
                        {{ trim($cust->DisplayName ?? '') ?: trim($cust->BusinessName ?? '') ?: ('Customer #' . $cust->ID) }}
                    </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-primary ifp-line-add-entity-btn flex-shrink-0" id="{{ $uid }}_newCustomerBtn" title="New customer" aria-label="New customer"><i class="ri-add-line"></i></button>
            </div>
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
        <label class="form-label fw-semibold">Sale of Term</label>
        @if($isReadonly)
            @php
                $selectedTerm = collect($saleOfTerms ?? [])->first(function($term) use ($invoice) {
                    return (string)$term->RetrunValue === (string)($invoice->SaleOfTerm ?? '');
                });
            @endphp
            <div class="form-control form-control-sm bg-light">{{ $selectedTerm ? $selectedTerm->TermName : ($invoice->SaleOfTerm ?? '-') }}</div>
        @else
            <select name="SaleOfTerm"
                    id="{{ $uid }}_SaleOfTerm"
                    class="{{ $selClass }} @error('SaleOfTerm') is-invalid @enderror">
                <option value="">— Select Term —</option>
                @foreach($saleOfTerms ?? [] as $term)
                    <option value="{{ $term->RetrunValue }}" {{ old('SaleOfTerm', $invoice->SaleOfTerm ?? '') === $term->RetrunValue ? 'selected' : '' }}>
                        {{ $term->TermName }}
                    </option>
                @endforeach
            </select>
            @error('SaleOfTerm')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
</div>

<div class="row g-2 mt-1">
    {{-- Row 2: 4 columns — Doc No, Doc No2, Doc No3, Status --}}
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

@if($inModal)
        </div>{{-- ._pfp-tab-panel-body header --}}
    </div>{{-- ._pfp-tab-panel-shell header --}}
    <div class="_pfp-tab-panel-shell border rounded overflow-hidden mb-3">
        <div class="_pfp-tab-panel-body">
@endif

    {{-- ══ Line Items Table ══ (match Invoice listing-table: font, header, row dividers) --}}
    <style>
        /* Tbody cells: 0.1rem pad + white bg (match .listing-table); thead keeps 0.3rem */
        .table-inv {
            border-collapse: collapse;
            border-spacing: 0;
            --ifp-inv-cell-fs: 12px;
            --ifp-inv-head-pad: 0.3rem;
            --ifp-inv-head-bg: var(--bs-gray-300, #dee2e6);
            --ifp-inv-head-fg: var(--bs-black, #000);
            --ifp-inv-row-border: rgba(0, 0, 0, 0.08);
        }
        .table-inv thead th {
            font-size: var(--ifp-inv-cell-fs) !important;
            font-weight: 400 !important;
            font-family: inherit;
            padding: var(--ifp-inv-head-pad) !important;
            vertical-align: middle;
            background: var(--ifp-inv-head-bg) !important;
            color: var(--ifp-inv-head-fg) !important;
            border: none !important;
            border-bottom: 1px solid var(--bs-border-color, #dee2e6) !important;
        }
        .table-inv thead th:first-child {
            padding-left: 15px !important;
        }
        .table-inv tbody td {
            font-size: 12px !important;
            font-weight: normal;
            font-family: inherit;
            padding: 0.1rem !important;
            vertical-align: middle;
            background-color: rgb(255, 255, 255) !important;
            border: none !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
        }
        .table-inv tbody td:first-child {
            padding-left: 5px !important;
        }
        .table-inv th.ifp-col-product,
        .table-inv td.ifp-col-product { min-width: 170px; width: 13%; }
        .table-inv th.ifp-col-desc,
        .table-inv td.ifp-col-desc { min-width: 280px; width: 23%; }
        .table-inv th.ifp-col-uom,
        .table-inv td.ifp-col-uom { min-width: 130px; width: 10%; }
        .table-inv th.ifp-col-tax-sro,
        .table-inv td.ifp-col-tax-sro { min-width: 220px; width: 14%; max-width: none; }
        .table-inv th.ifp-col-qty,
        .table-inv td.ifp-col-qty { min-width: 64px; width: 4%; }
        .table-inv th.ifp-col-sale-rate,
        .table-inv td.ifp-col-sale-rate { min-width: 88px; width: 5%; }
        .table-inv th.ifp-col-fbr-qty,
        .table-inv td.ifp-col-fbr-qty {
            min-width: 88px;
            width: 5%;
            display: none !important;
        }
        .table-inv.show-fbr-qty th.ifp-col-fbr-qty,
        .table-inv.show-fbr-qty td.ifp-col-fbr-qty {
            display: table-cell !important;
        }
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
        .table-inv .form-control,
        .table-inv .form-select {
            padding: 0.2rem 0.35rem;
            margin: 0;
            min-height: calc(1.2em + 0.4rem);
            font-size: var(--ifp-inv-cell-fs) !important;
            font-family: inherit !important;
            line-height: 1.35;
            box-sizing: border-box;
            min-width: 0;
            width: 100%;
        }
        .table-inv .form-control-sm,
        .table-inv .form-select-sm {
            font-size: var(--ifp-inv-cell-fs) !important;
            min-height: calc(1.2em + 0.35rem);
        }
        .table-inv .form-select { padding-right: 1.15rem; background-position: right 0.35rem center; background-size: 10px 8px; }
        .table-inv textarea.form-control {
            padding: 0.2rem 0.35rem;
            margin: 0;
            min-height: 1.5rem;
        }
        .table-inv input[type="number"] { min-width: 0; padding-right: 0.14rem; text-align: right; font-variant-numeric: tabular-nums; }
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
        .table-inv td.ifp-col-desc textarea.form-control { min-width: 200px; width: 100%; }
        .table-inv td.ifp-col-desc .ifp-readonly-desc {
            display: block;
            min-width: 320px;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
        }
        .table-inv .ifp-tax-sro-readonly-pill {
            border: 1px solid #d6daf0;
            background: #f8f9ff;
            border-radius: 0.35rem;
            padding: 0.2rem 0.35rem;
            line-height: 1.1;
        }
        /* Invoice line Tax & SRO nested modal: four listboxes (compact mode) — align with product modal */
        .ifp-tax-sro-fields .ifp-tax-sro-listbox-area {
            --ifp-inv-tax-list-h: 92px;
            background: var(--bs-body-bg, #fff);
        }
        .ifp-tax-sro-fields .ifp-tax-picker-row {
            min-height: calc(var(--ifp-inv-tax-list-h) + 3.85rem);
        }
        .ifp-tax-sro-fields .ifp-tax-listbox-search {
            border-radius: 0;
            border-left: none;
            border-right: none;
            border-top: none;
            font-size: 12px !important;
        }
        .ifp-tax-sro-fields .ifp-tax-listbox-col {
            border-right: 1px solid var(--bs-border-color, #dee2e6);
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .ifp-tax-sro-fields .ifp-tax-listbox-col:last-child {
            border-right: none;
        }
        @media (max-width: 991.98px) {
            .ifp-tax-sro-fields .ifp-tax-listbox-col:nth-child(2) {
                border-right: none;
            }
            .ifp-tax-sro-fields .ifp-tax-listbox-col:nth-child(1),
            .ifp-tax-sro-fields .ifp-tax-listbox-col:nth-child(2) {
                border-bottom: 1px solid var(--bs-border-color, #dee2e6);
            }
        }
        .ifp-tax-sro-nested-modal .ifp-tax-sro-fields .ifp-tax-column-header,
        .ifp-tax-sro-fields .ifp-tax-column-header {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-align: center;
            padding: 0.5rem 0.35rem;
            background-color: var(--bs-secondary-bg, #f8f9fa);
            border-bottom: 1px solid var(--bs-border-color, #dee2e6);
            color: var(--bs-secondary-color, #6c757d);
            flex-shrink: 0;
        }
        .ifp-tax-sro-fields .ifp-inv-tax-listbox {
            width: 100%;
            min-height: var(--ifp-inv-tax-list-h);
            font-size: 12px !important;
            line-height: 1.35;
            padding: 0.25rem 0.35rem;
            border: none;
            border-radius: 5px;
            box-shadow: none;
            accent-color: rgb(var(--primary-rgb));
        }
        .ifp-tax-sro-fields .ifp-inv-tax-listbox:focus {
            box-shadow: inset 0 0 0 2px rgba(var(--primary-rgb), 0.35) !important;
            outline: 0;
        }
        .ifp-tax-sro-fields .ifp-tax-listbox-search::selection,
        .ifp-tax-sro-fields .ifp-tax-listbox-search::-moz-selection {
            background-color: rgb(var(--primary-rgb));
            color: #fff;
        }
        .ifp-tax-sro-fields .ifp-inv-tax-listbox option:checked,
        .ifp-tax-sro-fields .ifp-inv-tax-listbox:focus option:checked {
            background-color: rgb(var(--primary-rgb)) !important;
            background-image: none !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
            font-weight: 500;
        }
        .ifp-tax-sro-fields .ifp-inv-tax-listbox option:hover {
            background-color: var(--bs-secondary-bg, #e9ecef);
        }
        .ifp-tax-sro-fields .ifp-inv-tax-listbox option:checked:hover,
        .ifp-tax-sro-fields .ifp-inv-tax-listbox:focus option:checked:hover {
            background-color: rgb(var(--primary-rgb)) !important;
            background-image: none !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
        }
        .ifp-tax-sro-fields .ifp-inv-tax-listbox option:active,
        .ifp-tax-sro-fields .ifp-inv-tax-listbox:focus option:active {
            background-color: rgb(var(--primary-rgb)) !important;
            background-image: none !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
        }
        .ifp-tax-sro-fields .ifp-inv-tax-listbox option:focus,
        .ifp-tax-sro-fields .ifp-inv-tax-listbox:focus option:focus {
            background-color: rgb(var(--primary-rgb)) !important;
            background-image: none !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
        }
        .ifp-tax-sro-fields .ifp-inv-tax-listbox option[data-ifp-placeholder="1"] {
            display: none;
        }
        .ifp-tax-sro-fields-lower .ifp-tax-sro-lbl {
            font-size: 12px;
            font-weight: 500;
        }
        /* Nested Tax & SRO modal: title-case labels (override custom.css uppercase on #invoice_modal_body .ifp-tax-sro-lbl) */
        #invoice_modal_body .ifp-tax-sro-nested-modal .ifp-tax-sro-lbl,
        .ifp-modal-shell .ifp-tax-sro-nested-modal .ifp-tax-sro-lbl {
            text-transform: none;
            letter-spacing: 0.01em;
            font-weight: 500;
        }
        /* Nested Tax & SRO modal: uniform 12px on all controls */
        .ifp-tax-sro-nested-modal .modal-title,
        .ifp-tax-sro-nested-modal .modal-footer .btn,
        .ifp-tax-sro-nested-modal .ifp-tax-sro-fields__head,
        .ifp-tax-sro-nested-modal .ifp-tax-column-header,
        .ifp-tax-sro-nested-modal .ifp-tax-listbox-search,
        .ifp-tax-sro-nested-modal .ifp-inv-tax-listbox,
        .ifp-tax-sro-nested-modal .ifp-inv-tax-listbox option,
        .ifp-tax-sro-nested-modal .ifp-tax-sro-lbl,
        .ifp-tax-sro-nested-modal .form-control,
        .ifp-tax-sro-nested-modal .form-select {
            font-size: 12px !important;
        }
        .ifp-tax-sro-fields-lower .form-control {
            font-size: 12px !important;
        }
        .table-inv .btn-icon { padding: 0.16rem 0.26rem; margin: 0; }
        /* Line items: allow horizontal scroll inside modal / narrow layouts (parent may use overflow-hidden). */
        .ifp-inv-line-items-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        /* Invoice totals summary (align with invoice-print.blade.php) */
        .ifp-invoice-totals-summary.table {
            margin-left: auto;
            width: 100%;
            max-width: 22rem;
            font-size: 12px;
            border-collapse: separate !important;
            border-spacing: 0;
        }
        /* Tighter row rhythm than default .table-sm (0.25rem) */
        .ifp-invoice-totals-summary.table > :not(caption) > * > * {
            padding-top: 0.12rem !important;
            padding-bottom: 0.12rem !important;
        }
        .ifp-invoice-totals-summary tbody th,
        .ifp-invoice-totals-summary tbody th .fw-medium,
        .ifp-invoice-totals-summary tbody th span {
            text-align: right;
            vertical-align: middle;
            font-weight: 600 !important;
            font-size: 12px;
            padding: 0.12rem 0.5rem 0.12rem 0 !important;
            border: none;
        }
        .ifp-invoice-totals-summary td {
            text-align: right;
            vertical-align: middle;
            padding: 0.1rem 0 !important;
            border: none;
            font-size: 12px;
        }
        .ifp-invoice-totals-summary .form-control,
        .ifp-invoice-totals-summary .invoice-amount-input {
            font-size: 12px !important;
            line-height: 1.2 !important;
            text-align: right !important;
            font-variant-numeric: tabular-nums;
            /* Match invoice line grid (.table-inv) control height (~24.2px); default .form-control is taller */
            min-height: 24.2px !important;
            height: 24.2px !important;
            padding: 0.2rem 0.35rem !important;
            box-sizing: border-box !important;
        }
        .ifp-invoice-totals-summary tr.ifp-totals-inc td .form-control,
        .ifp-invoice-totals-summary tr.ifp-totals-grand td .form-control {
            font-weight: 700 !important;
        }
        /* Plain single lines above + below Total Inc. All Taxes and Grand Total */
        .ifp-invoice-totals-summary tr.ifp-totals-inc th,
        .ifp-invoice-totals-summary tr.ifp-totals-inc td,
        .ifp-invoice-totals-summary tr.ifp-totals-grand th,
        .ifp-invoice-totals-summary tr.ifp-totals-grand td {
            border: none !important;
            border-top: 1px solid #222 !important;
            border-bottom: 1px solid #222 !important;
            font-size: 12px;
            padding-top: 0.3rem !important;
            padding-bottom: 0.3rem !important;
        }
        .ifp-invoice-totals-summary tr.ifp-totals-inc td {
            font-weight: 700;
        }
        .ifp-invoice-totals-summary tr.ifp-totals-grand td {
            font-weight: 700;
        }
    </style>
    <div class="col-xl-12">
        <div class="ifp-inv-line-items-scroll">
        <table class="table table-inv listing-table invoices-table mt-3 {{ $isReadonly ? '' : 'text-nowrap' }} {{ $hasFbrQty ? 'show-fbr-qty' : '' }}">
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
                    <th class="ifp-col-sale-rate">Unit Rate</th>
                    <th class="ifp-col-fbr-qty">HSCode Qty</th>
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
                            <div class="d-flex gap-1 align-items-start ifp-product-select-wrap">
                            <select name="items[{{ $idx }}][ProductID]" class="{{ $selClass }} ifp-product flex-grow-1" style="min-width:0;">
                                <option value="">— Select —</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->ID }}"
                                            data-rate="{{ $prod->SalePrice ?? 0 }}"
                                            data-gst="{{ $prod->GstPercentage ?? 0 }}"
                                            data-hscode="{{ $prod->HsCode ?? '' }}"
                                            data-sale-information="{{ $prod->SaleInformation ?? '' }}"
                                            data-uom-id="{{ $prod->UomID ?? '' }}"
                                            data-uom-label="{{ trim((string) ($prod->UnitName ?? '')) }}"
                                            data-unit="{{ $prod->Unit ?? 0 }}"
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
                            <button type="button" class="btn btn-sm btn-primary ifp-line-add-entity-btn flex-shrink-0 ifp-new-product-btn" title="New product" aria-label="New product"><i class="ri-add-line"></i></button>
                            </div>
                        @endif
                    </td>
                    <td class="ifp-col-desc">
                        @if($isReadonly)
                            <span class="small ifp-readonly-desc">{{ $item->Details ?? $item->ProductDescription ?? '-' }}</span>
                        @else
                            <input type="hidden" name="items[{{ $idx }}][HsCode]" class="ifp-hscode" value="{{ $item->HsCode ?? '' }}">
                            <textarea name="items[{{ $idx }}][Details]" rows="1" class="form-control form-control-sm ifp-details" placeholder="Enter Description">{{ $item->Details ?? $item->ProductDescription ?? '' }}</textarea>
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
                            @php
                                $qtyNum = (float)($item->Quantity ?? 0);
                                $qtyDec = ($qtyNum - floor($qtyNum) > 0.000001) ? 2 : 0;
                            @endphp
                            <span class="small">{{ number_format($qtyNum, $qtyDec, '.', ',') }}</span>
                        @else
                            @php
                                $qtyNum = (float)($item->Quantity ?? 1);
                                $qtyDec = ($qtyNum - floor($qtyNum) > 0.000001) ? 2 : 0;
                            @endphp
                            <input type="text" inputmode="decimal" name="items[{{ $idx }}][Quantity]" class="form-control form-control-sm ifp-qty ifp-numfmt" value="{{ number_format($qtyNum, $qtyDec, '.', ',') }}">
                        @endif
                    </td>
                    <td class="ifp-col-sale-rate">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->Rate ?? 0, 2) }}</span>
                        @else
                            <input type="text" inputmode="decimal" name="items[{{ $idx }}][Rate]" class="form-control form-control-sm ifp-rate ifp-numfmt" value="{{ number_format((float) ($item->Rate ?? 0), 2, '.', ',') }}">
                        @endif
                    </td>
                    <td class="ifp-col-fbr-qty">
                        @if($isReadonly)
                            <span class="small">{{ ($item->Unit ?? 0) > 1 ? (isset($item->FBRQty) ? number_format((float)$item->FBRQty, 2) : '—') : '—' }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][FBRQty]" class="form-control form-control-sm ifp-fbr-qty" value="{{ ($item->Unit ?? 0) > 1 && isset($item->FBRQty) ? number_format((float)$item->FBRQty, 2, '.', '') : '' }}" {{ ($item->Unit ?? 0) <= 1 ? 'disabled' : '' }}>
                            <input type="hidden" name="items[{{ $idx }}][Unit]" class="ifp-product-unit" value="{{ $item->Unit ?? 0 }}">
                        @endif
                    </td>
                    <td class="ifp-col-amount">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->Amount ?? 0, 2) }}</span>
                        @else
                            <input type="text" name="items[{{ $idx }}][Amount]" class="form-control form-control-sm ifp-amount ifp-numfmt" value="{{ number_format((float) ($item->Amount ?? 0), 2, '.', ',') }}" readonly>
                        @endif
                    </td>
                    @if($inModal)
                    <td class="ifp-col-fixed">
                        @php
                            $fixedNotifiedVal = (float)($item->FixedNotifiedValueOrRetailPrice ?? 0);
                            if ($fixedNotifiedVal <= 0 && (float)($item->TPRate ?? 0) > 0) {
                                $fixedNotifiedVal = (float)$item->TPRate * (float)($item->Quantity ?? 1);
                            }
                        @endphp
                        @if($isReadonly)
                            <span class="small">{{ number_format($fixedNotifiedVal, 2) }}</span>
                        @else
                            <input type="text" name="items[{{ $idx }}][FixedNotifiedValueOrRetailPrice]" class="form-control form-control-sm ifp-fixed-notified-value ifp-numfmt" value="{{ number_format($fixedNotifiedVal, 2, '.', ',') }}" readonly>
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
                            <span class="small">{{ number_format((float) ($item->DiscountAmount ?? $item->Discount ?? 0), 2) }}</span>
                        @else
                            <input type="text" name="items[{{ $idx }}][DiscountAmount]" class="form-control form-control-sm ifp-disc-amt ifp-numfmt" value="{{ number_format((float) ($item->DiscountAmount ?? $item->Discount ?? 0), 2, '.', ',') }}" readonly>
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
                            <input type="text" name="items[{{ $idx }}][GSTAmount]" class="form-control form-control-sm ifp-gst-amt ifp-numfmt" value="{{ number_format((float) ($item->GSTAmount ?? 0), 2, '.', ',') }}" readonly>
                        @endif
                    </td>
                    <td class="ifp-col-net">
                        @if($isReadonly)
                            <span class="small fw-bold">{{ number_format($item->TotalValues ?? 0, 2) }}</span>
                        @else
                            <input type="text" name="items[{{ $idx }}][Total]" class="form-control form-control-sm ifp-total ifp-numfmt fw-bold" value="{{ number_format((float) ($item->TotalValues ?? 0), 2, '.', ',') }}" readonly>
                        @endif
                    </td>
                    <td class="ifp-col-extra-tax">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->ExtraTax ?? 0, 2) }}</span>
                        @else
                            <input type="text" name="items[{{ $idx }}][ExtraTax]" class="form-control form-control-sm ifp-extra-tax ifp-numfmt" value="{{ number_format((float) ($item->ExtraTax ?? 0), 2, '.', ',') }}" readonly>
                        @endif
                    </td>
                    <td class="ifp-col-further-tax">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->FurtherTax ?? 0, 2) }}</span>
                        @else
                            <input type="text" name="items[{{ $idx }}][FurtherTax]" class="form-control form-control-sm ifp-further-tax ifp-numfmt" value="{{ number_format((float) ($item->FurtherTax ?? 0), 2, '.', ',') }}" readonly>
                        @endif
                    </td>
                    <td class="ifp-col-fed-payable">
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->FedPayable ?? 0, 2) }}</span>
                        @else
                            <input type="text" name="items[{{ $idx }}][FedPayable]" class="form-control form-control-sm ifp-fed-payable ifp-numfmt" value="{{ number_format((float) ($item->FedPayable ?? 0), 2, '.', ',') }}" readonly>
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
                        <div class="d-flex gap-1 align-items-start ifp-product-select-wrap">
                        <select name="items[0][ProductID]" class="{{ $selClass }} ifp-product flex-grow-1" style="min-width:0;">
                            <option value="">— Select —</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->ID }}"
                                        data-rate="{{ $prod->SalePrice ?? 0 }}"
                                        data-gst="{{ $prod->GstPercentage ?? 0 }}"
                                        data-hscode="{{ $prod->HsCode ?? '' }}"
                                        data-sale-information="{{ $prod->SaleInformation ?? '' }}"
                                        data-uom-id="{{ $prod->UomID ?? '' }}"
                                        data-uom-label="{{ trim((string) ($prod->UnitName ?? '')) }}"
                                        data-unit="{{ $prod->Unit ?? 0 }}"
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
                        <button type="button" class="btn btn-sm btn-primary ifp-line-add-entity-btn flex-shrink-0 ifp-new-product-btn" title="New product" aria-label="New product"><i class="ri-add-line"></i></button>
                        </div>
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
                    <td class="ifp-col-qty"><input type="text" inputmode="decimal" name="items[0][Quantity]" class="form-control form-control-sm ifp-qty ifp-numfmt" value="1"></td>
                    <td class="ifp-col-sale-rate"><input type="text" inputmode="decimal" name="items[0][Rate]" class="form-control form-control-sm ifp-rate ifp-numfmt" value="0.00"></td>
                    <td class="ifp-col-fbr-qty">
                        <input type="number" step="0.01" name="items[0][FBRQty]" class="form-control form-control-sm ifp-fbr-qty" value="" disabled>
                        <input type="hidden" name="items[0][Unit]" class="ifp-product-unit" value="0">
                    </td>
                    <td class="ifp-col-amount"><input type="text"     name="items[0][Amount]"             class="form-control form-control-sm ifp-amount ifp-numfmt"   value="0.00" readonly></td>
                    @if($inModal)
                    <td class="ifp-col-fixed"><input type="text"     name="items[0][FixedNotifiedValueOrRetailPrice]" class="form-control form-control-sm ifp-fixed-notified-value ifp-numfmt" value="0.00" readonly></td>
                    @endif
                    @if(!$inModal)
                    <td><input type="number" step="0.01"     name="items[0][DiscountPercentage]" class="form-control form-control-sm ifp-disc-pct" value="0" min="0" max="100"></td>
                    @endif
                    <td class="ifp-col-disc-amt"><input type="text" name="items[0][DiscountAmount]" class="form-control form-control-sm ifp-disc-amt ifp-numfmt" value="0.00" readonly></td>
                    @if(!$inModal)
                    <td><input type="number" step="0.01"     name="items[0][GstPercentage]"      class="form-control form-control-sm ifp-gst-pct"  value="0" min="0"></td>
                    @endif
                    <td class="ifp-col-gst-amt"><input type="text"     name="items[0][GSTAmount]"          class="form-control form-control-sm ifp-gst-amt ifp-numfmt"  value="0.00" readonly></td>
                    <td class="ifp-col-net"><input type="text"     name="items[0][Total]"              class="form-control form-control-sm ifp-total ifp-numfmt fw-bold" value="0.00" readonly></td>
                    <td class="ifp-col-extra-tax"><input type="text" name="items[0][ExtraTax]" class="form-control form-control-sm ifp-extra-tax ifp-numfmt" value="0.00" readonly></td>
                    <td class="ifp-col-further-tax"><input type="text" name="items[0][FurtherTax]" class="form-control form-control-sm ifp-further-tax ifp-numfmt" value="0.00" readonly></td>
                    <td class="ifp-col-fed-payable"><input type="text" name="items[0][FedPayable]" class="form-control form-control-sm ifp-fed-payable ifp-numfmt" value="0.00" readonly></td>
                    <td class="ifp-col-actions"><button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button></td>
                </tr>
            @else
                <tr><td colspan="{{ $ifpEmptyColspan }}" class="text-center text-muted py-3"><i>No line items found.</i></td></tr>
            @endif

            </tbody>
        </table>
        </div>{{-- .ifp-inv-line-items-scroll --}}

        @if(!$isReadonly)
        <div class="my-3 d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-sm btn-primary" id="{{ $uid }}_addRow">
                <i class="bi bi-plus-lg"></i> Add Product
            </button>
            @if($isEdit)
            <button type="button" class="btn btn-sm btn-light border text-dark shadow-sm" id="{{ $uid }}_recalcUnits" title="Sync Units from product master without changing Quantity or Rate">
                <i class="ri-refresh-line me-1 text-primary"></i> Sync Units
            </button>
            @endif
        </div>
        @endif

        @if($inModal)
    </div>{{-- end col-xl-12 (line items only in modal) --}}
        </div>{{-- ._pfp-tab-panel-body lines --}}
    </div>{{-- ._pfp-tab-panel-shell lines --}}
    <div class="_pfp-tab-panel-shell border rounded overflow-hidden mb-0">
        <div class="_pfp-tab-panel-body">
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

            $viewSubTotal = $invoice ? ($invoice->SubTotal ?? $calcSubTotal) : $calcSubTotal;
            $viewDiscount = $invoice ? ($invoice->TotalDiscount ?? $invoice->DiscountAmount ?? $calcDiscount) : $calcDiscount;
            $viewGst      = $invoice ? ($invoice->TotalGST ?? $invoice->GSTAmount ?? $calcGst) : $calcGst;
            $viewGrand    = $invoice ? ($invoice->TotalAmount ?? $invoice->Total ?? $calcTotal) : $calcTotal;

            $buyerAdvanceOn = $invoice && (int) ($invoice->CustomerIsAdvanceTax ?? 0) === 1;
            $buyerAdvanceRate = $invoice ? (float) ($invoice->CustomerAdvanceTax ?? 0) : 0.0;
            $headerTotalInc = (float) $viewGrand;
            $customerWht236g = ($buyerAdvanceOn && $buyerAdvanceRate > 0.000001)
                ? round($headerTotalInc * ($buyerAdvanceRate / 100.0), 2)
                : 0.0;
            $grandTotalAllTaxes = round($headerTotalInc + $customerWht236g, 2);
            $showWhtBlock = $buyerAdvanceOn && $buyerAdvanceRate > 0.000001;
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
                <table class="table table-sm text-nowrap mb-0 table-borderless ifp-invoice-totals-summary">
                    <tbody>
                        <tr>
                            <th scope="row"><div class="fw-medium">Total Amount Excluding ST:</div></th>
                            <td>
                                <input type="text" name="SubTotal" id="{{ $uid }}_SubTotal"
                                       class="form-control invoice-amount-input w-100 ifp-numfmt"
                                       value="{{ $isReadonly ? number_format((float) $viewSubTotal, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><div class="fw-medium">Discount:</div></th>
                            <td>
                                <input type="text" name="TotalDiscount" id="{{ $uid }}_TotalDiscount"
                                       class="form-control invoice-amount-input w-100 ifp-numfmt"
                                       value="{{ $isReadonly ? number_format((float) $viewDiscount, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><div class="fw-medium">Total Sales Tax:</div></th>
                            <td>
                                <input type="text" name="TotalGST" id="{{ $uid }}_TotalGST"
                                       class="form-control invoice-amount-input w-100 ifp-numfmt"
                                       value="{{ $isReadonly ? number_format((float) $viewGst, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        <tr class="ifp-totals-inc">
                            <th scope="row"><div class="fw-medium">Total Inc. All Taxes:</div></th>
                            <td>
                                <input type="text" name="GrandTotal" id="{{ $uid }}_GrandTotal"
                                       class="form-control invoice-amount-input w-100 ifp-numfmt fw-bold"
                                       value="{{ $isReadonly ? number_format((float) $viewGrand, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        @if($isReadonly)
                            @if($showWhtBlock)
                                <tr>
                                    <th scope="row"><div class="fw-medium">Customer WHT 236G ({{ number_format($buyerAdvanceRate, 2) }}%):</div></th>
                                    <td>
                                        <input type="text" id="{{ $uid }}_CustomerWht236g"
                                               class="form-control invoice-amount-input w-100 ifp-numfmt"
                                               value="{{ number_format($customerWht236g, 2) }}" readonly>
                                    </td>
                                </tr>
                                <tr class="ifp-totals-grand">
                                    <th scope="row"><div class="fw-medium">Grand Total:</div></th>
                                    <td>
                                        <input type="text" id="{{ $uid }}_GrandTotalWithWht"
                                               class="form-control invoice-amount-input w-100 ifp-numfmt fw-bold"
                                               value="{{ number_format($grandTotalAllTaxes, 2) }}" readonly>
                                    </td>
                                </tr>
                            @endif
                        @else
                            <tr id="{{ $uid }}_tr_customer_wht" class="@if(!$showWhtBlock) d-none @endif">
                                <th scope="row">
                                    <div class="fw-medium">Customer WHT 236G (<span id="{{ $uid }}_wht_label_pct">{{ number_format($buyerAdvanceRate, 2) }}</span>%):</div>
                                </th>
                                <td>
                                    <input type="text" id="{{ $uid }}_CustomerWht236g"
                                           class="form-control invoice-amount-input w-100 ifp-numfmt"
                                           value="{{ $showWhtBlock ? number_format($customerWht236g, 2) : '0.00' }}" readonly>
                                </td>
                            </tr>
                            <tr id="{{ $uid }}_tr_grand_with_wht" class="ifp-totals-grand @if(!$showWhtBlock) d-none @endif">
                                <th scope="row"><div class="fw-medium">Grand Total:</div></th>
                                <td>
                                    <input type="text" id="{{ $uid }}_GrandTotalWithWht"
                                           class="form-control invoice-amount-input w-100 ifp-numfmt fw-bold"
                                           value="{{ $showWhtBlock ? number_format($grandTotalAllTaxes, 2) : '0.00' }}" readonly>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    @if(!$inModal)
    </div>{{-- end col-xl-12 --}}

</div>{{-- end row --}}
    @else
        </div>{{-- ._pfp-tab-panel-body totals --}}
    </div>{{-- ._pfp-tab-panel-shell totals --}}
    @endif

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

{{-- Full customer modal: same #customer_modal shell/ids as customers list (see _customer-form-partial submit → customer_modal_save_btn). --}}
@if(!$isReadonly)
<div class="modal fade ifp-customer-nested" id="customer_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="customer_modal_title">Customer</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="customer_modal_body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted mb-0">Loading…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                <button type="button" class="btn btn-sm btn-primary d-none" id="customer_modal_save_btn">
                    <i class="fas fa-save"></i> <span id="customer_modal_save_label">Save Customer</span>
                </button>
            </div>
        </div>
    </div>
</div>
{{-- Full product modal: same #product_modal shell/ids as products list (footer Save runs AJAX). --}}
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
            taxSroModalEl.addEventListener('shown.bs.modal', function() {
                var host = taxSroSlotHost;
                var row = host ? host.closest('.ifp-row') : null;
                ifpTaxFlashAllListboxes(row);
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

    function ifpSanitizeNumericText(v) {
        return String(v == null ? '' : v).replace(/,/g, '').trim();
    }
    function n(v) {
        const raw = ifpSanitizeNumericText(v);
        return parseFloat(raw) || 0;
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
    function ifpSetFormattedValue(el, v, decimals) {
        if (!el) return;
        el.value = ifpFormatNumber(v, decimals);
    }
    function ifpNormalizeFormattedInputs(scope) {
        const root = scope || document;
        root.querySelectorAll('.ifp-numfmt').forEach(function(el) {
            el.value = ifpSanitizeNumericText(el.value);
        });
    }
    function ifpRateInput(row) {
        return rowEl(row, '.ifp-col-sale-rate .ifp-rate') || rowEl(row, '.ifp-rate');
    }
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

    function ifpTaxUseListboxUi(sel) {
        return !!(IFP_TAX_COMPACT && sel && sel.classList && sel.classList.contains('ifp-inv-tax-listbox'));
    }
    function ifpTaxSlotFromSelect(sel) {
        return sel ? sel.closest('.ifp-tax-sro-slot') : null;
    }
    function ifpTaxFilterInputFor(sel) {
        var slot = ifpTaxSlotFromSelect(sel);
        if (!slot || !sel.dataset || !sel.dataset.ifpPair) return null;
        return slot.querySelector('input.ifp-tax-listbox-search[data-ifp-pair="' + sel.dataset.ifpPair + '"]');
    }
    function ifpTaxClearFilter(sel) {
        var inp = ifpTaxFilterInputFor(sel);
        if (inp) inp.value = '';
    }
    function ifpTaxAppendHiddenPlaceholder(sel, labelText) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return null;
        var o = document.createElement('option');
        o.value = '';
        o.textContent = labelText;
        o.setAttribute('data-ifp-placeholder', '1');
        o.setAttribute('hidden', 'hidden');
        if (labelText) o.setAttribute('title', labelText);
        sel.appendChild(o);
        return o;
    }
    function ifpTaxSelectHiddenPlaceholder(sel) {
        if (!sel) return;
        for (var i = 0; i < sel.options.length; i++) {
            var o = sel.options[i];
            if (o.getAttribute('data-ifp-placeholder') === '1') {
                o.selected = true;
            } else {
                o.selected = false;
            }
        }
    }
    function ifpTaxDeselectPlaceholder(sel) {
        if (!sel) return;
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].getAttribute('data-ifp-placeholder') === '1') {
                sel.options[i].selected = false;
            }
        }
    }
    function ifpTaxListboxThemeRgbBg() {
        try {
            var raw = getComputedStyle(document.documentElement).getPropertyValue('--primary-rgb').trim();
            if (raw) return 'rgb(' + raw + ')';
        } catch (e) {}
        return 'rgb(111, 66, 193)';
    }
    /** Native listbox: paint chosen row (same solid as .btn-primary / save buttons) */
    var IFP_TAX_LB_ROW_FG = '#fff';
    /** Loose match for FBR IDs (string vs number, trim). */
    function ifpTaxOptionValuesMatch(a, b) {
        var sa = String(a != null ? a : '').trim();
        var sb = String(b != null ? b : '').trim();
        if (sa === sb) return true;
        if (sa !== '' && sb !== '' && !isNaN(Number(sa)) && !isNaN(Number(sb)) && Number(sa) === Number(sb)) return true;
        return false;
    }
    function ifpTaxPaintSelectedOption(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        var selVal = String(sel.value || '');
        var si = sel.selectedIndex;
        for (var i = 0; i < sel.options.length; i++) {
            var opt = sel.options[i];
            if (opt.getAttribute('data-ifp-placeholder') === '1') {
                opt.style.removeProperty('background-color');
                opt.style.removeProperty('color');
                opt.style.removeProperty('-webkit-text-fill-color');
                continue;
            }
            opt.style.removeProperty('background-color');
            opt.style.removeProperty('color');
            opt.style.removeProperty('-webkit-text-fill-color');
            var byIndex = (i === si && si >= 0 && opt.getAttribute('data-ifp-placeholder') !== '1');
            var byValue = selVal !== '' && ifpTaxOptionValuesMatch(selVal, opt.value);
            if (byValue || byIndex) {
                opt.style.setProperty('background-color', ifpTaxListboxThemeRgbBg(), 'important');
                opt.style.setProperty('color', IFP_TAX_LB_ROW_FG, 'important');
                opt.style.setProperty('-webkit-text-fill-color', IFP_TAX_LB_ROW_FG, 'important');
            }
        }
    }
    function ifpTaxApplyListboxFilter(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        var inp = ifpTaxFilterInputFor(sel);
        var q = (inp && inp.value) ? String(inp.value).trim().toLowerCase() : '';
        var selVal = String(sel.value || '');
        for (var i = 0; i < sel.options.length; i++) {
            var opt = sel.options[i];
            if (opt.getAttribute('data-ifp-placeholder') === '1') {
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
                if (opt.getAttribute('data-ifp-none-visible') === '1' && sel.selectedIndex >= 0 && sel.options[sel.selectedIndex] === opt) {
                    show = true;
                }
            }
            opt.hidden = !show;
            if (displayLabel) opt.setAttribute('title', displayLabel);
            else opt.removeAttribute('title');
        }
    }
    /** Move the chosen row to the top of the list (after hidden placeholder) so it is visible first. */
    function ifpTaxMoveSelectedOptionFirst(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        var selectedOpt = null;
        var si = sel.selectedIndex;
        if (si >= 0 && sel.options[si] && sel.options[si].getAttribute('data-ifp-placeholder') !== '1') {
            selectedOpt = sel.options[si];
        }
        if (!selectedOpt) {
            var selVal = String(sel.value || '').trim();
            for (var i = 0; i < sel.options.length; i++) {
                var opt = sel.options[i];
                if (opt.getAttribute('data-ifp-placeholder') === '1') continue;
                if (selVal !== '' && ifpTaxOptionValuesMatch(selVal, opt.value)) {
                    selectedOpt = opt;
                    break;
                }
                if (!selVal && opt.getAttribute('data-ifp-none-visible') === '1' && opt.selected) {
                    selectedOpt = opt;
                    break;
                }
            }
        }
        if (!selectedOpt) return;
        var insertBefore = null;
        for (var j = 0; j < sel.options.length; j++) {
            var o = sel.options[j];
            if (o.getAttribute('data-ifp-placeholder') === '1') continue;
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

    function ifpTaxFlashListboxSelection(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        ifpTaxApplyListboxFilter(sel);
        var wantRaw = sel.getAttribute('data-selected');
        if (wantRaw === null || wantRaw === undefined) wantRaw = sel.value || '';
        var want = String(wantRaw).trim();
        if (want === '') {
            var nv = null;
            for (var ni = 0; ni < sel.options.length; ni++) {
                if (sel.options[ni].getAttribute('data-ifp-none-visible') === '1') {
                    nv = sel.options[ni];
                    break;
                }
            }
            if (nv) {
                nv.selected = true;
                sel.value = nv.value;
                ifpTaxDeselectPlaceholder(sel);
                ifpTaxMoveSelectedOptionFirst(sel);
                ifpTaxApplyListboxFilter(sel);
                ifpTaxScrollListboxToSelected(sel);
                return;
            }
            ifpTaxSelectHiddenPlaceholder(sel);
            ifpTaxScrollListboxToSelected(sel);
            ifpTaxPaintSelectedOption(sel);
            return;
        }
        var sv = want;
        var found = false;
        for (var i = 0; i < sel.options.length; i++) {
            var o = sel.options[i];
            if (o.getAttribute('data-ifp-placeholder') === '1') continue;
            if (ifpTaxOptionValuesMatch(sv, o.value)) {
                sel.selectedIndex = i;
                sel.value = String(o.value);
                found = true;
                break;
            }
        }
        if (!found) {
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            ifpTaxDeselectPlaceholder(sel);
        }
        ifpTaxMoveSelectedOptionFirst(sel);
        ifpTaxApplyListboxFilter(sel);
        ifpTaxScrollListboxToSelected(sel);
    }
    /** SRO columns: no rate / no rows from API — show a visible "None" row (not the hidden listbox placeholder). */
    function ifpTaxFillSroListWithNone(sel) {
        if (!sel) return;
        ifpTaxClearFilter(sel);
        sel.innerHTML = '';
        var nopt = document.createElement('option');
        nopt.value = '';
        nopt.textContent = 'None';
        nopt.setAttribute('data-ifp-none-visible', '1');
        sel.appendChild(nopt);
        nopt.selected = true;
        sel.setAttribute('data-selected', '');
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxMoveSelectedOptionFirst(sel);
            ifpTaxScrollListboxToSelected(sel);
        }
    }
    function ifpTaxScrollListboxToSelected(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        requestAnimationFrame(function() {
            try {
                var i = sel.selectedIndex;
                if (i >= 0 && sel.options[i]) {
                    var o = sel.options[i];
                    if (o.getAttribute('data-ifp-placeholder') !== '1') {
                        o.scrollIntoView({ block: 'start' });
                    }
                }
            } catch (e) {}
            ifpTaxPaintSelectedOption(sel);
        });
    }
    function ifpTaxBindListboxRowPaint(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel) || sel.dataset.ifpTaxRowPaint === '1') return;
        sel.dataset.ifpTaxRowPaint = '1';
        function paint() {
            requestAnimationFrame(function() {
                ifpTaxMoveSelectedOptionFirst(sel);
                ifpTaxApplyListboxFilter(sel);
                ifpTaxPaintSelectedOption(sel);
            });
        }
        ['mouseup', 'keyup', 'blur', 'change'].forEach(function(ev) { sel.addEventListener(ev, paint); });
    }
    function ifpTaxBindListboxSearch(sel) {
        var inp = ifpTaxFilterInputFor(sel);
        if (!inp || !sel || !ifpTaxUseListboxUi(sel)) return;
        if (inp.dataset.ifpTaxSearchBound === '1') return;
        inp.dataset.ifpTaxSearchBound = '1';
        function run() {
            ifpTaxApplyListboxFilter(sel);
            ifpTaxFlashListboxSelection(sel);
        }
        inp.addEventListener('input', run);
        inp.addEventListener('search', run);
    }
    function ifpTaxBindAllListboxSearches(row) {
        if (!IFP_TAX_COMPACT) return;
        ['.ifp-sale-type', '.ifp-sale-type-to-rate', '.ifp-sro-schedule', '.ifp-sro-item'].forEach(function(sel) {
            var el = rowEl(row, sel);
            if (el && ifpTaxUseListboxUi(el)) {
                ifpTaxBindListboxSearch(el);
                ifpTaxBindListboxRowPaint(el);
            }
        });
    }
    function ifpTaxFlashAllListboxes(row) {
        if (!IFP_TAX_COMPACT || !row) return;
        ['.ifp-sale-type', '.ifp-sale-type-to-rate', '.ifp-sro-schedule', '.ifp-sro-item'].forEach(function(sel) {
            var el = rowEl(row, sel);
            if (el && ifpTaxUseListboxUi(el)) {
                ifpTaxApplyListboxFilter(el);
                ifpTaxFlashListboxSelection(el);
            }
        });
    }

    function updateTaxSroTriggerSummary(row) {
        if (!row) return;
        var saleLine = row.querySelector('.ifp-tax-sro-trigger__sale');
        var rateLine = row.querySelector('.ifp-tax-sro-trigger__rate');
        if (!saleLine || !rateLine) return;
        var saleTypeSel = rowEl(row, '.ifp-sale-type');
        var saleRateSel = rowEl(row, '.ifp-sale-type-to-rate');
        var saleTypeText = '';
        var saleRateText = '';
        if (saleTypeSel && saleTypeSel.selectedIndex >= 0) {
            saleTypeText = String(saleTypeSel.options[saleTypeSel.selectedIndex].textContent || '').trim();
        }
        if (saleRateSel && saleRateSel.selectedIndex >= 0) {
            saleRateText = String(saleRateSel.options[saleRateSel.selectedIndex].textContent || '').trim();
        }
        saleLine.textContent = (saleTypeText && saleTypeText.toLowerCase().indexOf('select') !== 0) ? saleTypeText : 'Sale type —';
        rateLine.textContent = (saleRateText && saleRateText.toLowerCase().indexOf('select') !== 0) ? saleRateText : 'Rate profile —';
        saleLine.setAttribute('title', saleLine.textContent);
        rateLine.setAttribute('title', rateLine.textContent);
        var openBtn = row.querySelector('.ifp-tax-sro-open-btn');
        if (openBtn) {
            openBtn.setAttribute('title', saleLine.textContent + ' · ' + rateLine.textContent + ' — Open Tax & SRO');
        }
    }

    // ── Row calculation ──
    function calcRow(row) {
        const qty     = n(rowEl(row, '.ifp-qty')?.value);
        const rate    = n(ifpRateInput(row)?.value);
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
            if (extraTaxEl) extraTaxEl.value = ifpFormatNumber(0, 2);
            if (furtherTaxEl) furtherTaxEl.value = ifpFormatNumber(0, 2);
            if (fedPayableEl) fedPayableEl.value = ifpFormatNumber(0, 2);
        } else if (rateType === 'R') {
            gstAmt = rateValue * qty;
        } else if (rateType === 'H') {
            const gstBase = isFixed ? fixedNotified : taxable;
            gstAmt = (60 * qty) + (gstBase * gstPct / 100);
        } else {
            const gstBase = isFixed ? fixedNotified : taxable;
            gstAmt = gstBase * gstPct / 100;
        }
        const total   = taxable + gstAmt + extraTax + furtherTax + fedPayable;

        function set(cls, val) {
            const el = rowEl(row, '.' + cls);
            if (el) ifpSetFormattedValue(el, val, 2);
        }
        set('ifp-amount',   amount);
        set('ifp-disc-amt', discAmt);
        set('ifp-extra-tax', extraTax);
        set('ifp-further-tax', furtherTax);
        set('ifp-fed-payable', fedPayable);
        set('ifp-gst-amt',  gstAmt);
        set('ifp-total',    total);
        if (fixedNotifiedEl) fixedNotifiedEl.value = ifpFormatNumber(fixedNotified, 2);

        // FBR Qty Input Enablement & Fallback:
        const unitEl = rowEl(row, '.ifp-product-unit');
        const unit = n(unitEl ? unitEl.value : '0');
        const fbrQtyEl = rowEl(row, '.ifp-fbr-qty');
        if (fbrQtyEl) {
            if (unit > 1) {
                fbrQtyEl.removeAttribute('disabled');
            } else {
                fbrQtyEl.value = '';
                fbrQtyEl.setAttribute('disabled', 'true');
            }
        }

        return { amount, discAmt, gstAmt, total };
    }

    function toggleFbrQtyColumn() {
        if (@json($isReadonly)) return;
        let hasFbrQty = false;
        body.querySelectorAll('.ifp-row').forEach(function(row) {
            const unitEl = row.querySelector('.ifp-product-unit');
            const unit = parseInt(unitEl ? unitEl.value : '0', 10) || 0;
            if (unit > 1) {
                hasFbrQty = true;
            }
        });
        const table = body.closest('.table-inv');
        if (table) {
            if (hasFbrQty) {
                table.classList.add('show-fbr-qty');
            } else {
                table.classList.remove('show-fbr-qty');
            }
        }
    }

    function ifpCustomerAdvanceMeta() {
        const customerSel = document.getElementById(uid + '_CustomerID');
        if (!customerSel || customerSel.selectedIndex < 0) {
            return { on: false, rate: 0 };
        }
        const opt = customerSel.options[customerSel.selectedIndex];
        if (!opt || !opt.value) {
            return { on: false, rate: 0 };
        }
        const advOn = (opt.getAttribute('data-is-advance-tax') || '0') === '1';
        const rate = parseFloat(opt.getAttribute('data-advance-tax-rate') || '0') || 0;
        return { on: advOn && rate > 0.000001, rate: rate };
    }

    function ifpSyncWhtSummary(grandIncTaxes) {
        const meta = ifpCustomerAdvanceMeta();
        const wht = meta.on ? Math.round(grandIncTaxes * (meta.rate / 100) * 100) / 100 : 0;
        const grandWithWht = Math.round((grandIncTaxes + wht) * 100) / 100;
        const pctEl = document.getElementById(uid + '_wht_label_pct');
        if (pctEl) {
            pctEl.textContent = meta.rate.toFixed(2);
        }
        const whtEl = document.getElementById(uid + '_CustomerWht236g');
        if (whtEl) {
            whtEl.value = ifpFormatNumber(wht, 2);
        }
        const gtwEl = document.getElementById(uid + '_GrandTotalWithWht');
        if (gtwEl) {
            gtwEl.value = ifpFormatNumber(grandWithWht, 2);
        }
        const trWht = document.getElementById(uid + '_tr_customer_wht');
        const trGrand = document.getElementById(uid + '_tr_grand_with_wht');
        if (trWht && trGrand) {
            if (meta.on) {
                trWht.classList.remove('d-none');
                trGrand.classList.remove('d-none');
            } else {
                trWht.classList.add('d-none');
                trGrand.classList.add('d-none');
            }
        }
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
            if (el) el.value = ifpFormatNumber(v, 2);
        }
        s('SubTotal',      sub);
        s('TotalDiscount', disc);
        s('TotalGST',      gst);
        s('GrandTotal',    grand);
        ifpSyncWhtSummary(grand);
    }

    // ── Bind row events ──
    function bindRow(row) {
        // Recalculate summary on input change for any relevant field
        row.querySelectorAll('.ifp-qty, .ifp-rate, .ifp-disc-pct, .ifp-gst-pct, .ifp-extra-tax, .ifp-further-tax, .ifp-fed-payable, .ifp-fixed-rate').forEach(function(inp) {
            inp.addEventListener('input', function() {
                calcRow(row);
                calcSummary();
            });
        });
        const isFixedCb = rowEl(row, '.ifp-is-fixed-rate-cb');
        const isFixedHidden = rowEl(row, '.ifp-is-fixed-rate');
        const fixedRateInp = rowEl(row, '.ifp-fixed-rate');

        function syncFixedRateState() {
            const isFixed = isFixedCb ? isFixedCb.checked : ((parseInt(isFixedHidden ? isFixedHidden.value : '0', 10) || 0) === 1);
            if (isFixedHidden) isFixedHidden.value = isFixed ? '1' : '0';
            if (fixedRateInp) {
                if (isFixed) {
                    fixedRateInp.removeAttribute('readonly');
                } else {
                    fixedRateInp.setAttribute('readonly', 'true');
                    fixedRateInp.value = '0.00';
                }
            }
        }

        if (isFixedCb) {
            isFixedCb.addEventListener('change', function() {
                syncFixedRateState();
                calcRow(row);
                calcSummary();
            });
        }

        if (fixedRateInp) {
            fixedRateInp.addEventListener('blur', function() {
                if (!fixedRateInp.readOnly) {
                    fixedRateInp.value = ifpFormatNumber(n(fixedRateInp.value), 2);
                    calcRow(row);
                    calcSummary();
                }
            });
        }
        syncFixedRateState();
        const fbrInp = row.querySelector('.ifp-fbr-qty');
        if (fbrInp) {
            fbrInp.addEventListener('blur', function() {
                if (fbrInp.value !== '') {
                    const parsed = parseFloat(fbrInp.value);
                    fbrInp.value = isNaN(parsed) ? '' : parsed.toFixed(2);
                }
            });
        }
        const qtyInp = row.querySelector('.ifp-qty');
        if (qtyInp) {
            qtyInp.addEventListener('focus', function() {
                qtyInp.value = ifpSanitizeNumericText(qtyInp.value);
            });
            qtyInp.addEventListener('blur', function() {
                const val = n(qtyInp.value);
                const decimals = (val % 1 !== 0) ? 2 : 0;
                qtyInp.value = ifpFormatNumber(val, decimals);
            });
        }
        const rateInp = ifpRateInput(row);
        if (rateInp) {
            rateInp.addEventListener('focus', function() {
                rateInp.value = ifpSanitizeNumericText(rateInp.value);
            });
            rateInp.addEventListener('blur', function() {
                rateInp.value = ifpFormatNumber(n(rateInp.value), 2);
            });
        }

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
                const rateEl = ifpRateInput(row);
                const gstEl  = rowEl(row, '.ifp-gst-pct');
                const ds = opt.dataset || {};
                const qty = n(rowEl(row, '.ifp-qty')?.value || '1');
                if (forceDetailsFill) {
                    if (rateEl) rateEl.value = ifpFormatNumber(ds.rate || opt.getAttribute('data-rate') || 0, 2);
                    if (gstEl)  gstEl.value  = ds.gst  || opt.getAttribute('data-gst')  || 0;
                }

                const hsCode = ds.hscode || opt.getAttribute('data-hscode') || '';
                const saleInfo = ds.saleInformation || opt.getAttribute('data-sale-information') || '';
                const uomId = ds.uomId || opt.getAttribute('data-uom-id') || '';
                const unit = parseInt(ds.unit || opt.getAttribute('data-unit') || '0', 10) || 0;
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
                if (isFixedHidden) isFixedHidden.value = String(isFixedVal);
                if (isFixedCb) isFixedCb.checked = !!isFixedVal;
                if (fixedRateEl) {
                    if (isFixedVal) {
                        fixedRateEl.value = n(tpRate).toFixed(2);
                        fixedRateEl.removeAttribute('readonly');
                    } else {
                        fixedRateEl.value = '0.00';
                        fixedRateEl.setAttribute('readonly', 'true');
                    }
                }

                const unitEl = rowEl(row, '.ifp-product-unit');
                if (unitEl) {
                    unitEl.value = String(unit);
                }
                const fbrQtyEl = rowEl(row, '.ifp-fbr-qty');
                if (fbrQtyEl) {
                    if (unit > 1) {
                        fbrQtyEl.removeAttribute('disabled');
                        // Empty by default for manual entry on selection
                        if (forceDetailsFill) {
                            fbrQtyEl.value = '';
                        }
                    } else {
                        fbrQtyEl.value = '';
                        fbrQtyEl.setAttribute('disabled', 'true');
                    }
                }

                if (forceDetailsFill) {
                    loadSaleTypesForRow(row);
                }

                calcRow(row);
                toggleFbrQtyColumn();
                calcSummary();
            };

            prodSel.addEventListener('change', function() {
                applySelectedProductToRow(true);
            });
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
                toggleFbrQtyColumn();
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

    /** When HS code is missing or returns no UOM list, use the product option's default UOM (data-uom-id / data-uom-label). */
    function applyUomFromProductFallback(row) {
        var uomSel = rowEl(row, '.ifp-uom');
        var prodSel = row.querySelector('.ifp-product');
        if (!uomSel || !prodSel || !prodSel.value) return;
        var opt = (prodSel.selectedOptions && prodSel.selectedOptions.length)
            ? prodSel.selectedOptions[0]
            : prodSel.options[prodSel.selectedIndex];
        if (!opt) return;
        var id = String(opt.getAttribute('data-uom-id') || '').trim();
        if (!id) return;
        var lbl = String(opt.getAttribute('data-uom-label') || '').trim();
        var label = lbl || ('Unit #' + id);
        clearUomForRow(row);
        var o = document.createElement('option');
        o.value = id;
        o.textContent = label;
        uomSel.appendChild(o);
        var want = String(uomSel.getAttribute('data-selected') || id).trim();
        uomSel.value = want;
        if (uomSel.value !== want) {
            uomSel.value = id;
            uomSel.setAttribute('data-selected', id);
        }
    }

    function fetchHsCodeInfoForRow(row) {
        var hsInp = rowEl(row, '.ifp-hscode');
        var uomSel = rowEl(row, '.ifp-uom');
        var code = (hsInp && hsInp.value) ? String(hsInp.value).trim() : '';
        if (uomSel) clearUomForRow(row);
        if (!code) {
            applyUomFromProductFallback(row);
            return;
        }
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
                } else {
                    applyUomFromProductFallback(row);
                }
            })
            .catch(function() {
                if (uomSel) applyUomFromProductFallback(row);
            });
    }

    function ifpFindDefaultSaleTypeOption(sel) {
        if (!sel) return null;
        var want = 'Goods at standard rate (default)';
        var wantLc = want.toLowerCase();
        var opts = sel.querySelectorAll('option');
        for (var i = 0; i < opts.length; i++) {
            var opt = opts[i];
            if (!opt.value || opt.getAttribute('data-ifp-placeholder') === '1') continue;
            var label = (opt.textContent || '').trim();
            var ll = label.toLowerCase();
            if (ll === wantLc || (ll.indexOf('goods at standard rate') !== -1 && ll.indexOf('default') !== -1)) {
                return opt;
            }
        }
        return null;
    }

    function ifpApplyDefaultSaleTypeIfNeeded(sel) {
        if (!sel) return false;
        var saved = String(sel.getAttribute('data-selected') || '').trim();
        if (saved) return false;
        if (String(sel.value || '').trim()) return false;
        var opt = ifpFindDefaultSaleTypeOption(sel);
        if (!opt) return false;
        opt.selected = true;
        sel.value = String(opt.value);
        sel.setAttribute('data-selected', String(opt.value));
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxDeselectPlaceholder(sel);
            ifpTaxFlashListboxSelection(sel);
        }
        return true;
    }

    function loadSaleTypesForRow(row) {
        var sel = rowEl(row, '.ifp-sale-type');
        if (!sel) return;
        var selected = String(sel.getAttribute('data-selected') || '').trim();
        ifpTaxClearFilter(sel);
        if (IFP_TAX_COMPACT) {
            ifpTaxClearFilter(rowEl(row, '.ifp-sale-type-to-rate'));
            ifpTaxClearFilter(rowEl(row, '.ifp-sro-schedule'));
            ifpTaxClearFilter(rowEl(row, '.ifp-sro-item'));
        }
        sel.innerHTML = '';
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxAppendHiddenPlaceholder(sel, 'Please select sale type');
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            var ph0 = document.createElement('option');
            ph0.value = '';
            ph0.textContent = 'Select Sale Type';
            sel.appendChild(ph0);
        }
        fetch(INV_SALE_TYPES_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    if (ifpTaxUseListboxUi(sel)) {
                        if (!sel.value) ifpTaxSelectHiddenPlaceholder(sel);
                        else ifpTaxDeselectPlaceholder(sel);
                        ifpTaxFlashListboxSelection(sel);
                    }
                    updateTaxSroTriggerSummary(row);
                    return;
                }
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && ifpTaxOptionValuesMatch(selected, item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                if (ifpTaxUseListboxUi(sel)) {
                    ifpTaxDeselectPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
                if (!selected) {
                    ifpApplyDefaultSaleTypeIfNeeded(sel);
                }
                var v = String(sel.value || '').trim();
                if (!v && selected) {
                    var wantSt = String(selected).trim();
                    for (var ii = 0; ii < sel.options.length; ii++) {
                        if (sel.options[ii].getAttribute('data-ifp-placeholder') === '1') continue;
                        if (ifpTaxOptionValuesMatch(wantSt, sel.options[ii].value)) {
                            sel.selectedIndex = ii;
                            sel.value = String(sel.options[ii].value);
                            if (ifpTaxUseListboxUi(sel)) {
                                ifpTaxDeselectPlaceholder(sel);
                                ifpTaxFlashListboxSelection(sel);
                            }
                            v = String(sel.value || '').trim();
                            break;
                        }
                    }
                }
                if (v) {
                    sel.setAttribute('data-selected', v);
                }
                updateTaxSroTriggerSummary(row);
                if (v) loadSaleTypeToRatesForRow(row, v);
            })
            .catch(function() {});
    }

    function loadSaleTypeToRatesForRow(row, transactionTypeId) {
        var sel = rowEl(row, '.ifp-sale-type-to-rate');
        if (!sel) return;
        var selected = String(sel.getAttribute('data-selected') || '').trim();
        ifpTaxClearFilter(sel);
        sel.innerHTML = '';
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxAppendHiddenPlaceholder(sel, 'Please select sale type to rate');
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select Sale Type To Rate'; sel.appendChild(ph);
        }
        if (!transactionTypeId) {
            if (ifpTaxUseListboxUi(sel)) ifpTaxFlashListboxSelection(sel);
            updateTaxSroTriggerSummary(row);
            return;
        }
        fetch(INV_SALE_TYPE_RATES + '?transaction_type_id=' + encodeURIComponent(transactionTypeId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    if (ifpTaxUseListboxUi(sel)) ifpTaxFlashListboxSelection(sel);
                    updateTaxSroTriggerSummary(row);
                    return;
                }
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    var rp = (item.rate_pct !== undefined && item.rate_pct !== null && item.rate_pct !== '') ? String(item.rate_pct) : '';
                    if (rp) opt.setAttribute('data-rate-pct', rp);
                    var rv = (item.rate_value !== undefined && item.rate_value !== null && item.rate_value !== '') ? String(item.rate_value) : '';
                    if (rv) opt.setAttribute('data-rate-value', rv);
                    var rt = (item.rate_type !== undefined && item.rate_type !== null && item.rate_type !== '') ? String(item.rate_type) : '';
                    if (rt) opt.setAttribute('data-rate-type', rt);
                    if (selected && ifpTaxOptionValuesMatch(selected, item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                if (ifpTaxUseListboxUi(sel)) {
                    ifpTaxDeselectPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
                var v = String(sel.value || '').trim();
                if (!v && selected) {
                    var wantR = String(selected).trim();
                    for (var ri = 0; ri < sel.options.length; ri++) {
                        if (sel.options[ri].getAttribute('data-ifp-placeholder') === '1') continue;
                        if (ifpTaxOptionValuesMatch(wantR, sel.options[ri].value)) {
                            sel.selectedIndex = ri;
                            sel.value = String(sel.options[ri].value);
                            if (ifpTaxUseListboxUi(sel)) {
                                ifpTaxDeselectPlaceholder(sel);
                                ifpTaxFlashListboxSelection(sel);
                            }
                            v = String(sel.value || '').trim();
                            break;
                        }
                    }
                }
                if (!v && data.items.length === 1) {
                    var onlyId = String(data.items[0].id != null ? data.items[0].id : '').trim();
                    sel.setAttribute('data-selected', onlyId);
                    if (ifpTaxUseListboxUi(sel)) {
                        ifpTaxFlashListboxSelection(sel);
                    }
                    v = String(sel.value || '').trim();
                }
                if (ifpTaxUseListboxUi(sel) && !v && data.items.length > 1) {
                    ifpTaxSelectHiddenPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
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
                    updateTaxSroTriggerSummary(row);
                    loadSroSchedulesForRow(row, v);
                } else {
                    updateTaxSroTriggerSummary(row);
                }
            })
            .catch(function() {});
    }

    function loadSroSchedulesForRow(row, rateId) {
        var sel = rowEl(row, '.ifp-sro-schedule');
        if (!sel) return;
        var selected = String(sel.getAttribute('data-selected') || '').trim();
        ifpTaxClearFilter(sel);
        sel.innerHTML = '';

        function finishScheduleNone() {
            ifpTaxFillSroListWithNone(sel);
            loadSroItemsForRow(row, '');
            updateTaxSroTriggerSummary(row);
        }

        if (!rateId) {
            finishScheduleNone();
            return;
        }
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxAppendHiddenPlaceholder(sel, 'Please select SRO schedule');
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select SRO Schedule'; sel.appendChild(ph);
        }
        fetch(INV_SRO_SCHEDULES + '?rate_id=' + encodeURIComponent(rateId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    finishScheduleNone();
                    return;
                }
                if (!data.items.length) {
                    finishScheduleNone();
                    return;
                }
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && ifpTaxOptionValuesMatch(selected, item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                if (ifpTaxUseListboxUi(sel)) {
                    ifpTaxDeselectPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
                var v = String(sel.value || '').trim();
                if (!v && selected) {
                    var wantSch = String(selected).trim();
                    for (var si = 0; si < sel.options.length; si++) {
                        if (sel.options[si].getAttribute('data-ifp-placeholder') === '1') continue;
                        if (ifpTaxOptionValuesMatch(wantSch, sel.options[si].value)) {
                            sel.selectedIndex = si;
                            sel.value = String(sel.options[si].value);
                            if (ifpTaxUseListboxUi(sel)) {
                                ifpTaxDeselectPlaceholder(sel);
                                ifpTaxFlashListboxSelection(sel);
                            }
                            v = String(sel.value || '').trim();
                            break;
                        }
                    }
                }
                if (v) loadSroItemsForRow(row, v);
                else loadSroItemsForRow(row, '');
                updateTaxSroTriggerSummary(row);
            })
            .catch(function() { finishScheduleNone(); });
    }

    function loadSroItemsForRow(row, sroId) {
        var sel = rowEl(row, '.ifp-sro-item');
        if (!sel) return;
        var selected = String(sel.getAttribute('data-selected') || '').trim();
        ifpTaxClearFilter(sel);
        sel.innerHTML = '';

        if (!sroId) {
            ifpTaxFillSroListWithNone(sel);
            return;
        }
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxAppendHiddenPlaceholder(sel, 'Please select SRO item');
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select SRO Item'; sel.appendChild(ph);
        }
        fetch(INV_SRO_ITEMS + '?sro_id=' + encodeURIComponent(sroId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    ifpTaxFillSroListWithNone(sel);
                    return;
                }
                if (!data.items.length) {
                    ifpTaxFillSroListWithNone(sel);
                    return;
                }
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && ifpTaxOptionValuesMatch(selected, item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                if (ifpTaxUseListboxUi(sel)) {
                    ifpTaxDeselectPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
                if (!String(sel.value || '').trim() && selected) {
                    var wantIt = String(selected).trim();
                    for (var ii = 0; ii < sel.options.length; ii++) {
                        if (sel.options[ii].getAttribute('data-ifp-placeholder') === '1') continue;
                        if (ifpTaxOptionValuesMatch(wantIt, sel.options[ii].value)) {
                            sel.selectedIndex = ii;
                            sel.value = String(sel.options[ii].value);
                            if (ifpTaxUseListboxUi(sel)) {
                                ifpTaxDeselectPlaceholder(sel);
                                ifpTaxFlashListboxSelection(sel);
                            }
                            break;
                        }
                    }
                }
            })
            .catch(function() { ifpTaxFillSroListWithNone(sel); });
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
                    ifpTaxClearFilter(saleTypeToRateSel);
                    saleTypeToRateSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(saleTypeToRateSel)) {
                        ifpTaxAppendHiddenPlaceholder(saleTypeToRateSel, 'Please select sale type to rate');
                        ifpTaxSelectHiddenPlaceholder(saleTypeToRateSel);
                    } else {
                        var o = document.createElement('option'); o.value = ''; o.textContent = 'Select Sale Type first'; saleTypeToRateSel.appendChild(o);
                    }
                }
                if (rtInp) rtInp.value = '';
                updateTaxSroTriggerSummary(row);
                if (sroScheduleSel) {
                    sroScheduleSel.setAttribute('data-selected', '');
                    ifpTaxClearFilter(sroScheduleSel);
                    sroScheduleSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(sroScheduleSel)) {
                        ifpTaxAppendHiddenPlaceholder(sroScheduleSel, 'Please select sale type to rate');
                        ifpTaxSelectHiddenPlaceholder(sroScheduleSel);
                    } else {
                        var o2 = document.createElement('option'); o2.value = ''; o2.textContent = 'Select Sale Type To Rate first'; sroScheduleSel.appendChild(o2);
                    }
                }
                if (sroItemSel) {
                    sroItemSel.setAttribute('data-selected', '');
                    ifpTaxClearFilter(sroItemSel);
                    sroItemSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(sroItemSel)) {
                        ifpTaxAppendHiddenPlaceholder(sroItemSel, 'Please select SRO schedule');
                        ifpTaxSelectHiddenPlaceholder(sroItemSel);
                    } else {
                        var o3 = document.createElement('option'); o3.value = ''; o3.textContent = 'Select SRO Schedule first'; sroItemSel.appendChild(o3);
                    }
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
                    updateTaxSroTriggerSummary(row);
                } else {
                    if (gstEl) gstEl.value = '0';
                    if (rtInp) rtInp.value = '';
                    updateTaxSroTriggerSummary(row);
                }
                if (sroScheduleSel) {
                    sroScheduleSel.setAttribute('data-selected', '');
                    ifpTaxClearFilter(sroScheduleSel);
                    sroScheduleSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(sroScheduleSel)) {
                        ifpTaxAppendHiddenPlaceholder(sroScheduleSel, 'Please select SRO schedule');
                        ifpTaxSelectHiddenPlaceholder(sroScheduleSel);
                    } else {
                        var o = document.createElement('option'); o.value = ''; o.textContent = 'Select Sale Type To Rate first'; sroScheduleSel.appendChild(o);
                    }
                }
                if (sroItemSel) {
                    sroItemSel.setAttribute('data-selected', '');
                    ifpTaxClearFilter(sroItemSel);
                    sroItemSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(sroItemSel)) {
                        ifpTaxAppendHiddenPlaceholder(sroItemSel, 'Please select SRO schedule');
                        ifpTaxSelectHiddenPlaceholder(sroItemSel);
                    } else {
                        var o2 = document.createElement('option'); o2.value = ''; o2.textContent = 'Select SRO Schedule first'; sroItemSel.appendChild(o2);
                    }
                }
                loadSroSchedulesForRow(row, v);
            });
        }
        if (sroScheduleSel) {
            sroScheduleSel.addEventListener('change', function() {
                var v = sroScheduleSel.value || '';
                if (sroItemSel) sroItemSel.setAttribute('data-selected', '');
                if (sroItemSel) ifpTaxClearFilter(sroItemSel);
                loadSroItemsForRow(row, v);
            });
        }
        ifpTaxBindAllListboxSearches(row);
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
                        <span class="ifp-tax-sro-trigger__text"><span class="ifp-tax-sro-trigger__sub"><span class="ifp-tax-sro-trigger__sale">Sale type —</span><span class="ifp-tax-sro-trigger__sep" aria-hidden="true">·</span><span class="ifp-tax-sro-trigger__rate">Rate profile —</span></span></span>
                        <i class="ri-arrow-right-s-line ifp-tax-sro-trigger__chev" aria-hidden="true"></i>
                    </button>
                    <div class="ifp-tax-sro-slot d-none">
                        <div class="ifp-tax-sro-fields">
                            <div class="ifp-tax-sro-fields__head">Classification &amp; SRO</div>
                            <div class="ifp-tax-sro-fields__body">
                                <div class="ifp-tax-sro-listbox-area border-bottom mb-2 pb-2 px-2 pt-2">
                                    <div class="row g-0 ifp-tax-picker-row">
                                        <div class="col-6 col-lg-4 ifp-tax-listbox-col"><div class="ifp-tax-column-header">Sale type</div><input type="search" class="form-control form-control-sm ifp-tax-listbox-search" data-ifp-pair="st" placeholder="Search…" autocomplete="off" aria-label="Search sale types"><select id="${uid}_st_${idx}" name="items[${idx}][SaleTypeID]" class="${selCls} ifp-inv-tax-listbox ifp-sale-type" data-ifp-pair="st" size="5" aria-label="Sale type" data-selected=""><option value="" hidden data-ifp-placeholder="1" selected title="Please select sale type">Please select sale type</option></select></div>
                                        <div class="col-6 col-lg-3 ifp-tax-listbox-col"><div class="ifp-tax-column-header">Sale type to rate</div><input type="search" class="form-control form-control-sm ifp-tax-listbox-search" data-ifp-pair="str" placeholder="Search…" autocomplete="off" aria-label="Search sale type to rate"><select id="${uid}_str_${idx}" name="items[${idx}][SaleTypeToRateID]" class="${selCls} ifp-inv-tax-listbox ifp-sale-type-to-rate" data-ifp-pair="str" size="5" aria-label="Sale type to rate" data-selected=""><option value="" hidden data-ifp-placeholder="1" selected title="Please select sale type to rate">Please select sale type to rate</option></select><input type="hidden" name="items[${idx}][RateType]" class="ifp-rate-type" value=""></div>
                                        <div class="col-6 col-lg-3 ifp-tax-listbox-col"><div class="ifp-tax-column-header">SRO schedule</div><input type="search" class="form-control form-control-sm ifp-tax-listbox-search" data-ifp-pair="sros" placeholder="Search…" autocomplete="off" aria-label="Search SRO schedule"><select id="${uid}_sros_${idx}" name="items[${idx}][SroScheduleID]" class="${selCls} ifp-inv-tax-listbox ifp-sro-schedule" data-ifp-pair="sros" size="5" aria-label="SRO schedule" data-selected=""><option value="" hidden data-ifp-placeholder="1" selected title="Please select SRO schedule">Please select SRO schedule</option></select></div>
                                        <div class="col-6 col-lg-2 ifp-tax-listbox-col"><div class="ifp-tax-column-header">SRO item</div><input type="search" class="form-control form-control-sm ifp-tax-listbox-search" data-ifp-pair="sroi" placeholder="Search…" autocomplete="off" aria-label="Search SRO item"><select id="${uid}_sroi_${idx}" name="items[${idx}][SroItemID]" class="${selCls} ifp-inv-tax-listbox ifp-sro-item" data-ifp-pair="sroi" size="5" aria-label="SRO item" data-selected=""><option value="" hidden data-ifp-placeholder="1" selected title="Please select SRO item">Please select SRO item</option></select></div>
                                    </div>
                                </div>
                                <div class="row g-2 px-2 pb-2 ifp-tax-sro-fields-lower">
                                    <div class="col-12 col-md-4"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_ifiler_${idx}">Is Filer</label><input type="hidden" name="items[${idx}][IsFiler]" class="ifp-is-filer" value="0"><div class="form-check ps-0 pt-1"><input type="checkbox" id="${uid}_ifiler_${idx}" class="form-check-input ifp-is-filer-cb m-0" disabled></div></div></div>
                                    <div class="col-12 col-md-4"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_gst_${idx}">GST Rate %</label><input type="number" step="0.01" id="${uid}_gst_${idx}" name="items[${idx}][GstPercentage]" class="form-control ifp-gst-pct" value="0" min="0"></div></div>
                                    <div class="col-12 col-md-4"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_ifr_${idx}">Is Fixed</label><input type="hidden" name="items[${idx}][IsFixedRate]" class="ifp-is-fixed-rate" value="0"><div class="form-check ps-0 pt-1"><input type="checkbox" id="${uid}_ifr_${idx}" class="form-check-input ifp-is-fixed-rate-cb m-0"></div></div></div>
                                    <div class="col-12 col-md-4"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_fr_${idx}">Fixed Rate</label><input type="number" step="0.01" id="${uid}_fr_${idx}" name="items[${idx}][TPRate]" class="form-control ifp-fixed-rate" value="0" readonly></div></div>
                                    <div class="col-12 col-md-4"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_dp_${idx}">Discount %</label><input type="number" step="0.01" id="${uid}_dp_${idx}" name="items[${idx}][DiscountPercentage]" class="form-control ifp-disc-pct" value="0" min="0" max="100"></div></div>
                                    <div class="col-12 col-md-4"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_etp_${idx}">Extra Tax %</label><input type="number" step="0.01" id="${uid}_etp_${idx}" class="form-control ifp-extra-tax-pct" value="0" readonly></div></div>
                                    <div class="col-12 col-md-4"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_ftp_${idx}">Further Tax %</label><input type="number" step="0.01" id="${uid}_ftp_${idx}" class="form-control ifp-further-tax-pct" value="0" readonly></div></div>
                                    <div class="col-12 col-md-4"><div class="ifp-tax-sro-field mb-0"><label class="ifp-tax-sro-lbl" for="${uid}_fpp_${idx}">FED Payable %</label><input type="number" step="0.01" id="${uid}_fpp_${idx}" class="form-control ifp-fed-payable-pct" value="0" readonly></div></div>
                                    <div class="col-12"><div class="ifp-tax-sro-field mb-0 ifp-tax-sro-field--last"><label class="ifp-tax-sro-lbl" for="${uid}_stw_${idx}">Sales Tax Withheld at Source</label><input type="number" step="0.01" id="${uid}_stw_${idx}" name="items[${idx}][SalesTaxWithheldAtSource]" class="form-control ifp-sales-tax-withheld" value="0" readonly></div></div>
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
                '<td class="ifp-col-qty"><input type="text" inputmode="decimal" name="items[' + idx + '][Quantity]" class="form-control form-control-sm ifp-qty ifp-numfmt" value="1"></td>' +
                '<td class="ifp-col-sale-rate"><input type="text" inputmode="decimal" name="items[' + idx + '][Rate]" class="form-control form-control-sm ifp-rate ifp-numfmt" value="0.00"></td>' +
                '<td class="ifp-col-fbr-qty"><input type="number" step="0.01" name="items[' + idx + '][FBRQty]" class="form-control form-control-sm ifp-fbr-qty" value="" disabled><input type="hidden" name="items[' + idx + '][Unit]" class="ifp-product-unit" value="0"></td>' +
                '<td class="ifp-col-amount"><input type="text"     name="items[' + idx + '][Amount]"             class="form-control form-control-sm ifp-amount ifp-numfmt"   value="0.00" readonly></td>' +
                '<td class="ifp-col-fixed"><input type="text"     name="items[' + idx + '][FixedNotifiedValueOrRetailPrice]" class="form-control form-control-sm ifp-fixed-notified-value ifp-numfmt" value="0.00" readonly></td>' +
                '<td class="ifp-col-disc-amt"><input type="text" name="items[' + idx + '][DiscountAmount]" class="form-control form-control-sm ifp-disc-amt ifp-numfmt" value="0.00" readonly></td>' +
                '<td class="ifp-col-gst-amt"><input type="text"     name="items[' + idx + '][GSTAmount]"          class="form-control form-control-sm ifp-gst-amt ifp-numfmt"  value="0.00" readonly></td>' +
                '<td class="ifp-col-net"><input type="text"     name="items[' + idx + '][Total]"              class="form-control form-control-sm ifp-total ifp-numfmt fw-bold" value="0.00" readonly></td>' +
                '<td class="ifp-col-extra-tax"><input type="text" name="items[' + idx + '][ExtraTax]" class="form-control form-control-sm ifp-extra-tax ifp-numfmt" value="0.00" readonly></td>' +
                '<td class="ifp-col-further-tax"><input type="text" name="items[' + idx + '][FurtherTax]" class="form-control form-control-sm ifp-further-tax ifp-numfmt" value="0.00" readonly></td>' +
                '<td class="ifp-col-fed-payable"><input type="text" name="items[' + idx + '][FedPayable]" class="form-control form-control-sm ifp-fed-payable ifp-numfmt" value="0.00" readonly></td>' +
                '<td class="ifp-col-actions"><button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button></td>'
            )
            : (
                '<td class="ifp-col-qty"><input type="text" inputmode="decimal" name="items[' + idx + '][Quantity]" class="form-control form-control-sm ifp-qty ifp-numfmt" value="1"></td>' +
                '<td class="ifp-col-sale-rate"><input type="text" inputmode="decimal" name="items[' + idx + '][Rate]" class="form-control form-control-sm ifp-rate ifp-numfmt" value="0.00"></td>' +
                '<td class="ifp-col-fbr-qty"><input type="number" step="0.01" name="items[' + idx + '][FBRQty]" class="form-control form-control-sm ifp-fbr-qty" value="" disabled><input type="hidden" name="items[' + idx + '][Unit]" class="ifp-product-unit" value="0"></td>' +
                '<td class="ifp-col-amount"><input type="text"     name="items[' + idx + '][Amount]"             class="form-control form-control-sm ifp-amount ifp-numfmt"   value="0.00" readonly></td>' +
                '<td><input type="number" step="0.01"     name="items[' + idx + '][DiscountPercentage]" class="form-control form-control-sm ifp-disc-pct" value="0" min="0" max="100"></td>' +
                '<td class="ifp-col-disc-amt"><input type="text" name="items[' + idx + '][DiscountAmount]" class="form-control form-control-sm ifp-disc-amt ifp-numfmt" value="0.00" readonly></td>' +
                '<td><input type="number" step="0.01"     name="items[' + idx + '][GstPercentage]"      class="form-control form-control-sm ifp-gst-pct"  value="0" min="0"></td>' +
                '<td class="ifp-col-gst-amt"><input type="text"     name="items[' + idx + '][GSTAmount]"          class="form-control form-control-sm ifp-gst-amt ifp-numfmt"  value="0.00" readonly></td>' +
                '<td class="ifp-col-net"><input type="text"     name="items[' + idx + '][Total]"              class="form-control form-control-sm ifp-total ifp-numfmt fw-bold" value="0.00" readonly></td>' +
                '<td class="ifp-col-extra-tax"><input type="text" name="items[' + idx + '][ExtraTax]" class="form-control form-control-sm ifp-extra-tax ifp-numfmt" value="0.00" readonly></td>' +
                '<td class="ifp-col-further-tax"><input type="text" name="items[' + idx + '][FurtherTax]" class="form-control form-control-sm ifp-further-tax ifp-numfmt" value="0.00" readonly></td>' +
                '<td class="ifp-col-fed-payable"><input type="text" name="items[' + idx + '][FedPayable]" class="form-control form-control-sm ifp-fed-payable ifp-numfmt" value="0.00" readonly></td>' +
                '<td class="ifp-col-actions"><button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button></td>'
            );
        var descCell = '<td class="ifp-col-desc"><input type="hidden" name="items[' + idx + '][HsCode]" class="ifp-hscode" value=""><textarea name="items[' + idx + '][Details]" rows="1" class="form-control form-control-sm ifp-details" placeholder="Enter Description"></textarea></td>';
        tr.innerHTML =
            '<td class="ifp-col-product"><div class="d-flex gap-1 align-items-start ifp-product-select-wrap"><select name="items[' + idx + '][ProductID]" class="' + selCls + ' ifp-product flex-grow-1" style="min-width:0;">' + optionsHTML + '</select><button type="button" class="btn btn-sm btn-primary ifp-line-add-entity-btn flex-shrink-0 ifp-new-product-btn" title="New product" aria-label="New product"><i class="ri-add-line"></i></button></div></td>' +
            descCell +
            '<td class="ifp-col-uom"><select name="items[' + idx + '][UomID]" class="' + selCls + ' ifp-uom" data-selected=""><option value="">Select UOM</option></select></td>' +
            taxSroCells +
            rowNumericTail;

        body.appendChild(tr);
        bindRow(tr);
        bindRowTaxDropdowns(tr);
        loadSaleTypesForRow(tr);
    });

    // ── Recalculate Units from Product Master ──
    const recalcUnitsBtn = document.getElementById(uid + '_recalcUnits');
    if (recalcUnitsBtn) {
        recalcUnitsBtn.addEventListener('click', function() {
            let updatedCount = 0;
            const rows = body.querySelectorAll('.ifp-row');

            rows.forEach(function(row) {
                const prodSel = rowEl(row, '.ifp-product');
                if (!prodSel || !prodSel.value) return;

                const opt = prodSel.options[prodSel.selectedIndex];
                if (!opt) return;

                const unit = parseInt(opt.getAttribute('data-unit') || '0', 10);
                const unitEl = rowEl(row, '.ifp-product-unit');
                const fbrQtyEl = rowEl(row, '.ifp-fbr-qty');

                const prevUnit = parseInt(unitEl ? unitEl.value : '0', 10);

                if (unitEl) {
                    unitEl.value = String(unit);
                }

                if (fbrQtyEl) {
                    if (unit > 1) {
                        fbrQtyEl.removeAttribute('disabled');
                    } else {
                        fbrQtyEl.value = '';
                        fbrQtyEl.setAttribute('disabled', 'true');
                    }
                }

                if (prevUnit !== unit) {
                    updatedCount++;
                    row.style.transition = 'background-color 0.4s ease';
                    row.style.backgroundColor = 'rgba(25, 135, 84, 0.12)';
                    setTimeout(function() {
                        row.style.backgroundColor = '';
                    }, 1400);
                }
            });

            toggleFbrQtyColumn();

            const msg = updatedCount > 0
                ? (updatedCount + ' line item(s) synchronized with product master. Quantity, Rate, and other details were preserved.')
                : 'All line item units already match the latest product master.';

            if (typeof showToast === 'function') {
                showToast(updatedCount > 0 ? 'success' : 'info', msg);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: updatedCount > 0 ? 'success' : 'info',
                    title: updatedCount > 0 ? 'Units Synchronized' : 'Already Synchronized',
                    text: msg,
                    timer: 2500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                alert(msg);
            }
        });
    }

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

    // ── Quick create Customer / Product (revert: remove this block with the modal HTML above) ──
    var productQuickTargetRow = null;
    function ifpCsrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? (m.getAttribute('content') || '') : '';
    }
    function ifpAppendCustomerOption(sel, c) {
        if (!sel || !c || !c.id) return;
        var id = String(c.id);
        var exists = false;
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === id) { exists = true; break; }
        }
        if (!exists) {
            var o = document.createElement('option');
            o.value = id;
            o.textContent = c.label || ('Customer #' + id);
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
    function ifpBuildProductOptionEl(p) {
        var id = String(p.id);
        var o = document.createElement('option');
        o.value = id;
        o.textContent = p.label || ('Product #' + id);
        o.setAttribute('data-rate', String(p.rate != null ? p.rate : 0));
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
    function ifpApplyNewProductToAllRows(p) {
        if (!p || !p.id) return;
        var id = String(p.id);
        body.querySelectorAll('.ifp-product').forEach(function(sel) {
            var exists = false;
            for (var i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === id) { exists = true; break; }
            }
            if (!exists) sel.appendChild(ifpBuildProductOptionEl(p));
        });
        var target = productQuickTargetRow && productQuickTargetRow.querySelector('.ifp-product');
        productQuickTargetRow = null;
        if (target) {
            target.value = id;
            target.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
    /** Move #customer_modal to document.body while invoice modal is open so the 2nd backdrop sits above the invoice layer (nested modal inside fullscreen invoice otherwise hides dimming). */
    function ifpElevateCustomerModalToBody(modalEl) {
        if (!modalEl || modalEl.dataset.ifpElevated === '1') return;
        var invoiceEl = document.getElementById('invoice_modal');
        if (!invoiceEl || !modalEl.closest('#invoice_modal')) return;
        var parent = modalEl.parentNode;
        if (!parent || parent === document.body) return;
        var ph = document.createElement('div');
        ph.id = 'ifp_customer_modal_ph';
        ph.className = 'd-none';
        ph.setAttribute('data-ifp-customer-anchor', '1');
        parent.insertBefore(ph, modalEl);
        document.body.appendChild(modalEl);
        modalEl.classList.add('ifp-customer-over-invoice');
        modalEl.dataset.ifpElevated = '1';
        document.body.classList.add('ifp-invoice-customer-open');
    }
    function ifpRestoreCustomerModalFromBody(modalEl) {
        document.body.classList.remove('ifp-invoice-customer-open');
        if (!modalEl || modalEl.dataset.ifpElevated !== '1') return;
        var ph = document.getElementById('ifp_customer_modal_ph');
        modalEl.classList.remove('ifp-customer-over-invoice');
        delete modalEl.dataset.ifpElevated;
        if (ph && ph.parentNode) {
            ph.parentNode.insertBefore(modalEl, ph);
            ph.parentNode.removeChild(ph);
        } else {
            var invBody = document.getElementById('invoice_modal_body');
            if (invBody) invBody.appendChild(modalEl);
        }
    }

    function ifpInvoiceShowCustomerModalErrors(errors) {
        var modalBody = document.getElementById('customer_modal_body');
        if (!modalBody) return;
        modalBody.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
        modalBody.querySelectorAll('.ajax-error-feedback').forEach(function(el) { el.remove(); });
        var firstField = null;
        Object.keys(errors || {}).forEach(function(field) {
            var messages = errors[field];
            var input = modalBody.querySelector('[name="' + field + '"]');
            if (input && messages && messages.length) {
                input.classList.add('is-invalid');
                var feedback = document.createElement('div');
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

    function openInvoiceCustomerModalCreate() {
        var modalEl = document.getElementById('customer_modal');
        var modalBody = document.getElementById('customer_modal_body');
        var modalTitle = document.getElementById('customer_modal_title');
        var saveBtn = document.getElementById('customer_modal_save_btn');
        if (!modalEl || !modalBody || !saveBtn) return;
        ifpElevateCustomerModalToBody(modalEl);
        var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        if (modalTitle) modalTitle.textContent = 'Add Customer';
        saveBtn.classList.remove('d-none');
        var saveLabelEl = document.getElementById('customer_modal_save_label');
        if (saveLabelEl) saveLabelEl.textContent = 'Save Customer';
        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted mb-0">Loading…</p></div>';
        bsModal.show();
        var url = @json(route('customers.modal')) + '?mode=create&_t=' + Date.now();
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': ifpCsrfToken(),
                'Cache-Control': 'no-cache'
            },
            credentials: 'same-origin'
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Server error: ' + res.status);
            return res.text();
        })
        .then(function(html) {
            modalBody.innerHTML = html;
            var scripts = modalBody.getElementsByTagName('script');
            Array.from(scripts).forEach(function(oldScript) {
                var newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                    newScript.async = false;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                document.body.appendChild(newScript);
            });
            saveBtn.onclick = function() {
                var form = modalBody.querySelector('form');
                if (!form) return;
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
                var formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': ifpCsrfToken()
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json().then(function(data) { return { res: res, data: data }; });
                })
                .then(function(pair) {
                    if (pair.data && pair.data.status === 'success') {
                        if (typeof window.clearCustomerModalDrafts === 'function') {
                            window.clearCustomerModalDrafts();
                        }
                        Object.keys(localStorage).forEach(function(k) {
                            if (k.startsWith('cfp_') || k.startsWith('customer_')) localStorage.removeItem(k);
                        });
                        bsModal.hide();
                        if (pair.data.customer) {
                            var mainCust = document.getElementById(uid + '_CustomerID');
                            if (mainCust) ifpAppendCustomerOption(mainCust, pair.data.customer);
                        }
                        if (typeof showToast === 'function') {
                            showToast('success', pair.data.message || 'Customer saved.');
                        }
                    } else {
                        ifpInvoiceShowCustomerModalErrors(pair.data && pair.data.errors ? pair.data.errors : {});
                        if (pair.data && pair.data.message && typeof showToast === 'function') {
                            showToast('error', pair.data.message);
                        }
                    }
                })
                .catch(function() {
                    if (typeof showToast === 'function') {
                        showToast('error', 'Submission failed. Please try again.');
                    }
                })
                .finally(function() {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> <span id="customer_modal_save_label">Save Customer</span>';
                });
            };
        })
        .catch(function(err) {
            modalBody.innerHTML = '<div class="alert alert-danger m-3">Failed to load customer form.<br><small class="text-muted">' + String(err.message || err) + '</small></div>';
        });
    }

    function ifpElevateProductModalToBody(modalEl) {
        if (!modalEl || modalEl.dataset.ifpElevated === '1') return;
        var invoiceEl = document.getElementById('invoice_modal');
        if (!invoiceEl || !modalEl.closest('#invoice_modal')) return;
        var parent = modalEl.parentNode;
        if (!parent || parent === document.body) return;
        var ph = document.createElement('div');
        ph.id = 'ifp_product_modal_ph';
        ph.className = 'd-none';
        ph.setAttribute('data-ifp-product-anchor', '1');
        parent.insertBefore(ph, modalEl);
        document.body.appendChild(modalEl);
        modalEl.classList.add('ifp-product-over-invoice');
        modalEl.dataset.ifpElevated = '1';
        document.body.classList.add('ifp-invoice-product-open');
    }
    function ifpRestoreProductModalFromBody(modalEl) {
        document.body.classList.remove('ifp-invoice-product-open');
        if (!modalEl || modalEl.dataset.ifpElevated !== '1') return;
        var ph = document.getElementById('ifp_product_modal_ph');
        modalEl.classList.remove('ifp-product-over-invoice');
        delete modalEl.dataset.ifpElevated;
        if (ph && ph.parentNode) {
            ph.parentNode.insertBefore(modalEl, ph);
            ph.parentNode.removeChild(ph);
        } else {
            var invBody = document.getElementById('invoice_modal_body');
            if (invBody) invBody.appendChild(modalEl);
        }
    }
    function ifpInvoiceShowProductModalErrors(errors) {
        var modalBody = document.getElementById('product_modal_body');
        if (!modalBody) return;
        modalBody.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
        modalBody.querySelectorAll('.ajax-error-feedback').forEach(function(el) { el.remove(); });
        var firstField = null;
        Object.keys(errors || {}).forEach(function(field) {
            var messages = errors[field];
            var input = modalBody.querySelector('[name="' + field + '"]');
            if (input && messages && messages.length) {
                input.classList.add('is-invalid');
                var feedback = document.createElement('div');
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
    function openInvoiceProductModalCreate() {
        var modalEl = document.getElementById('product_modal');
        var modalBody = document.getElementById('product_modal_body');
        var modalTitle = document.getElementById('product_modal_title');
        var saveBtn = document.getElementById('product_modal_save_btn');
        if (!modalEl || !modalBody || !saveBtn) return;
        ifpElevateProductModalToBody(modalEl);
        var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        if (modalTitle) modalTitle.textContent = 'Add Product';
        saveBtn.classList.remove('d-none');
        var saveLabelEl = document.getElementById('product_modal_save_label');
        if (saveLabelEl) saveLabelEl.textContent = 'Save Product';
        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted mb-0">Loading…</p></div>';
        bsModal.show();
        var url = @json(route('products.modal')) + '?mode=create';
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': ifpCsrfToken()
            },
            credentials: 'same-origin'
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Server error: ' + res.status);
            return res.text();
        })
        .then(function(html) {
            modalBody.innerHTML = html;
            var scripts = modalBody.getElementsByTagName('script');
            Array.from(scripts).forEach(function(oldScript) {
                var newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                    newScript.async = false;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                document.body.appendChild(newScript);
            });
            saveBtn.onclick = function() {
                var form = modalBody.querySelector('form');
                if (!form) return;
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
                var formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': ifpCsrfToken()
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json().then(function(data) { return { res: res, data: data }; });
                })
                .then(function(pair) {
                    if (pair.data && pair.data.status === 'success') {
                        bsModal.hide();
                        if (pair.data.product) {
                            ifpApplyNewProductToAllRows(pair.data.product);
                        }
                        if (typeof showToast === 'function') {
                            showToast('success', pair.data.message || 'Product saved.');
                        }
                    } else {
                        ifpInvoiceShowProductModalErrors(pair.data && pair.data.errors ? pair.data.errors : {});
                        if (pair.data && pair.data.message && typeof showToast === 'function') {
                            showToast('error', pair.data.message);
                        }
                    }
                })
                .catch(function() {
                    if (typeof showToast === 'function') {
                        showToast('error', 'Submission failed. Please try again.');
                    }
                })
                .finally(function() {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> <span id="product_modal_save_label">Save Product</span>';
                });
            };
        })
        .catch(function(err) {
            modalBody.innerHTML = '<div class="alert alert-danger m-3">Failed to load product form.<br><small class="text-muted">' + String(err.message || err) + '</small></div>';
        });
    }

    document.getElementById(uid + '_newCustomerBtn')?.addEventListener('click', function() {
        openInvoiceCustomerModalCreate();
    });
    var ifpCustomerModalRoot = document.getElementById('customer_modal');
    if (ifpCustomerModalRoot) {
        ifpCustomerModalRoot.addEventListener('hidden.bs.modal', function() {
            ifpRestoreCustomerModalFromBody(ifpCustomerModalRoot);
        });
    }
    body.addEventListener('click', function(ev) {
        var b = ev.target.closest('.ifp-new-product-btn');
        if (!b || !body.contains(b)) return;
        productQuickTargetRow = b.closest('.ifp-row');
        openInvoiceProductModalCreate();
    });
    var ifpProductModalRoot = document.getElementById('product_modal');
    if (ifpProductModalRoot) {
        ifpProductModalRoot.addEventListener('hidden.bs.modal', function() {
            ifpRestoreProductModalFromBody(ifpProductModalRoot);
        });
    }

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
    toggleFbrQtyColumn();
    calcSummary();
    if (!window.ifpNormalizeFormattedInputs) {
        window.ifpNormalizeFormattedInputs = ifpNormalizeFormattedInputs;
    }

})();
</script>
@endif