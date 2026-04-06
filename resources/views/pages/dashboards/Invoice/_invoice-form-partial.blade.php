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
                    class="form-select form-select-sm @error('InvoiceType') is-invalid @enderror">
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
                    class="form-select form-select-sm @error('CustomerID') is-invalid @enderror"
                    required>
                <option value="">— Select Customer —</option>
                @foreach($customers as $cust)
                    <option value="{{ $cust->ID }}" {{ old('CustomerID', $invoice->CustomerID ?? '') == $cust->ID ? 'selected' : '' }}>
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
        <select name="Status" class="form-select form-select-sm @error('Status') is-invalid @enderror" required>
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
        /* Compact spacing for invoice detail table only; enough padding so content is not cut off */
        .table-inv th,
        .table-inv td { padding: 0.25rem 0.4rem !important; vertical-align: middle; }
        .table-inv th.ifp-col-desc,
        .table-inv td.ifp-col-desc { min-width: 320px; width: 30%; }
        .table-inv .form-control,
        .table-inv .form-select { padding: 0.25rem 0.4rem; margin: 0; min-height: 1.75rem; font-size: 0.8125rem; box-sizing: border-box; min-width: 3.5rem; }
        .table-inv textarea.form-control { padding: 0.25rem 0.4rem; margin: 0; min-height: 1.75rem; }
        .table-inv td.ifp-col-desc textarea.form-control { min-width: 200px; width: 100%; }
        .table-inv td.ifp-col-desc .ifp-readonly-desc {
            display: block;
            min-width: 320px;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
        }
        .table-inv .btn-icon { padding: 0.2rem 0.35rem; margin: 0; }
    </style>
    <div class="col-xl-12">
        <table class="table nowrap {{ $isReadonly ? '' : 'text-nowrap' }} table-borderless border mt-3 table-inv">
            <thead>
                <tr>
                    <th>Product / Services</th>
                    <th class="ifp-col-desc">Description</th>
                    <th>UOM</th>
                    <th>Sale Type</th>
                    <th>Sale Type To Rate</th>
                    <th>SRO Schedule</th>
                    <th>SRO Item</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>Disc %</th>
                    <th>Disc Amount</th>
                    <th>GST Rate</th>
                    <th>GST Amount</th>
                    <th>Net</th>
                    @if(!$isReadonly)<th></th>@endif
                </tr>
            </thead>
            <tbody id="{{ $uid }}_body">

            @if($items->isNotEmpty())
                @foreach($items as $idx => $item)
                <tr class="ifp-row">
                    <td class="form-select-sm">
                        @if($isReadonly)
                            <span class="small">{{ $item->ProductName ?? '-' }}</span>
                        @else
                            <select name="items[{{ $idx }}][ProductID]" class="form-select form-select-sm ifp-product">
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
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->Uom_Desc ?? $item->UomName ?? $item->UomID ?? '-' }}</span>
                        @else
                            <select name="items[{{ $idx }}][UomID]" class="form-select form-select-sm ifp-uom" data-selected="{{ $item->UomID ?? '' }}">
                                <option value="">Select UOM</option>
                            </select>
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->SaleTypeDesc ?? $item->SaleTypeName ?? $item->SaleTypeID ?? '-' }}</span>
                        @else
                            <select name="items[{{ $idx }}][SaleTypeID]" class="form-select form-select-sm ifp-sale-type" data-selected="{{ $item->SaleTypeID ?? '' }}">
                                <option value="">Loading...</option>
                            </select>
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->SaleTypeToRateDesc ?? $item->SaleTypeToRateName ?? $item->SaleTypeToRateID ?? '-' }}</span>
                        @else
                            <select name="items[{{ $idx }}][SaleTypeToRateID]" class="form-select form-select-sm ifp-sale-type-to-rate" data-selected="{{ $item->SaleTypeToRateID ?? '' }}">
                                <option value="">Select Sale Type first</option>
                            </select>
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->SroScheduleDesc ?? $item->SroScheduleName ?? $item->SroScheduleID ?? '-' }}</span>
                        @else
                            <select name="items[{{ $idx }}][SroScheduleID]" class="form-select form-select-sm ifp-sro-schedule" data-selected="{{ $item->SroScheduleID ?? '' }}">
                                <option value="">Select Sale Type To Rate first</option>
                            </select>
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->SroItemSerialNo ?? $item->SroItemName ?? $item->SroItemID ?? '-' }}</span>
                        @else
                            <select name="items[{{ $idx }}][SroItemID]" class="form-select form-select-sm ifp-sro-item" data-selected="{{ $item->SroItemID ?? '' }}">
                                <option value="">Select SRO Schedule first</option>
                            </select>
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->Quantity ?? 0 }}</span>
                        @else
                            <input type="number" name="items[{{ $idx }}][Quantity]" class="form-control form-control-sm ifp-qty" value="{{ $item->Quantity ?? 1 }}" min="0" step="0.000001">
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->Rate ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.000001" name="items[{{ $idx }}][Rate]" class="form-control form-control-sm ifp-rate" value="{{ $item->Rate ?? 0 }}" min="0">
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->Amount ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][Amount]" class="form-control form-control-sm ifp-amount" value="{{ $item->Amount ?? 0 }}" readonly>
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->DiscountPercentage ?? 0 }}%</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][DiscountPercentage]" class="form-control form-control-sm ifp-disc-pct" value="{{ $item->DiscountPercentage ?? 0 }}" min="0" max="100">
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->DiscountAmount ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][DiscountAmount]" class="form-control form-control-sm ifp-disc-amt" value="{{ $item->DiscountAmount ?? 0 }}" readonly>
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->GstPercentage ?? 0 }}%</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][GstPercentage]" class="form-control form-control-sm ifp-gst-pct" value="{{ $item->GstPercentage ?? 0 }}" min="0">
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ number_format($item->GSTAmount ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][GSTAmount]" class="form-control form-control-sm ifp-gst-amt" value="{{ $item->GSTAmount ?? 0 }}" readonly>
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small fw-bold">{{ number_format($item->Total ?? 0, 2) }}</span>
                        @else
                            <input type="number" step="0.01" name="items[{{ $idx }}][Total]" class="form-control form-control-sm ifp-total fw-bold" value="{{ $item->Total ?? 0 }}" readonly>
                        @endif
                    </td>
                    @if(!$isReadonly)
                    <td>
                        <button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button>
                    </td>
                    @endif
                </tr>
                @endforeach

            @elseif(!$isReadonly)
                {{-- Default empty row --}}
                <tr class="ifp-row">
                    <td class="form-select-sm">
                        <select name="items[0][ProductID]" class="form-select form-select-sm ifp-product">
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
                                        data-sro-item-id="{{ $prod->SroItemID ?? '' }}">
                                    {{ $prod->ProductName ?? $prod->Name ?? $prod->Code ?? ('Product #' . $prod->ID) }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="ifp-col-desc"><input type="hidden" name="items[0][HsCode]" class="ifp-hscode" value=""><textarea name="items[0][Details]" rows="1" class="form-control form-control-sm ifp-details" placeholder="Enter Description"></textarea></td>
                    <td><select name="items[0][UomID]" class="form-select form-select-sm ifp-uom" data-selected=""><option value="">Select UOM</option></select></td>
                    <td><select name="items[0][SaleTypeID]" class="form-select form-select-sm ifp-sale-type" data-selected=""><option value="">Loading...</option></select></td>
                    <td><select name="items[0][SaleTypeToRateID]" class="form-select form-select-sm ifp-sale-type-to-rate" data-selected=""><option value="">Select Sale Type first</option></select></td>
                    <td><select name="items[0][SroScheduleID]" class="form-select form-select-sm ifp-sro-schedule" data-selected=""><option value="">Select Sale Type To Rate first</option></select></td>
                    <td><select name="items[0][SroItemID]" class="form-select form-select-sm ifp-sro-item" data-selected=""><option value="">Select SRO Schedule first</option></select></td>
                    <td><input type="number" name="items[0][Quantity]" class="form-control form-control-sm ifp-qty" value="1" min="0" step="0.000001"></td>
                    <td><input type="number" step="0.000001" name="items[0][Rate]"               class="form-control form-control-sm ifp-rate"     value="0" min="0"></td>
                    <td><input type="number" step="0.01"     name="items[0][Amount]"             class="form-control form-control-sm ifp-amount"   value="0" readonly></td>
                    <td><input type="number" step="0.01"     name="items[0][DiscountPercentage]" class="form-control form-control-sm ifp-disc-pct" value="0" min="0" max="100"></td>
                    <td><input type="number" step="0.01"     name="items[0][DiscountAmount]"     class="form-control form-control-sm ifp-disc-amt" value="0" readonly></td>
                    <td><input type="number" step="0.01"     name="items[0][GstPercentage]"      class="form-control form-control-sm ifp-gst-pct"  value="0" min="0"></td>
                    <td><input type="number" step="0.01"     name="items[0][GSTAmount]"          class="form-control form-control-sm ifp-gst-amt"  value="0" readonly></td>
                    <td><input type="number" step="0.01"     name="items[0][Total]"              class="form-control form-control-sm ifp-total fw-bold" value="0" readonly></td>
                    <td><button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button></td>
                </tr>
            @else
                <tr><td colspan="15" class="text-center text-muted py-3"><i>No line items found.</i></td></tr>
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

@if(!$isReadonly)
    <button type="submit" id="{{ $uid }}_submit" class="d-none">Submit</button>
</form>
@endif


{{-- ════ JavaScript (create / edit only) ════ --}}
@if(!$isReadonly)
<script>
(function () {
    const uid  = '{{ $uid }}';
    const body = document.getElementById(uid + '_body');
    if (!body) return;

    const INV_HSCODE_URL       = "{{ route('products.hscode-info') }}";
    const INV_SALE_TYPES_URL   = "{{ route('products.sale-types') }}";
    const INV_SALE_TYPE_RATES  = "{{ route('products.sale-type-to-rates') }}";
    const INV_SRO_SCHEDULES    = "{{ route('products.sro-schedules') }}";
    const INV_SRO_ITEMS        = "{{ route('products.sro-items') }}";

    function n(v) { return parseFloat(v) || 0; }

    // ── Row calculation ──
    function calcRow(row) {
        const qty     = n(row.querySelector('.ifp-qty')?.value);
        const rate    = n(row.querySelector('.ifp-rate')?.value);
        const discPct = n(row.querySelector('.ifp-disc-pct')?.value);
        const gstPct  = n(row.querySelector('.ifp-gst-pct')?.value);

        const amount  = qty * rate;
        const discAmt = amount * discPct / 100;
        const taxable = amount - discAmt;
        const gstAmt  = taxable * gstPct / 100;
        const total   = taxable + gstAmt;

        function set(cls, val) {
            const el = row.querySelector('.' + cls);
            if (el) el.value = val.toFixed(2);
        }
        set('ifp-amount',   amount);
        set('ifp-disc-amt', discAmt);
        set('ifp-gst-amt',  gstAmt);
        set('ifp-total',    total);

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
        row.querySelectorAll('.ifp-qty, .ifp-rate, .ifp-disc-pct, .ifp-gst-pct').forEach(function(inp) {
            inp.addEventListener('input', function() {
                calcRow(row);
                calcSummary();
            });
        });

        // Product select → auto-fill rate, GST, and product fields (HsCode, Description, UOM, Sale Type cascade)
        const prodSel = row.querySelector('.ifp-product');
        if (prodSel) {
            const applySelectedProductToRow = function(forceDetailsFill) {
                const opt = (prodSel.selectedOptions && prodSel.selectedOptions.length)
                    ? prodSel.selectedOptions[0]
                    : prodSel.options[prodSel.selectedIndex];
                if (!opt) return;
                const rateEl = row.querySelector('.ifp-rate');
                const gstEl  = row.querySelector('.ifp-gst-pct');
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

                const hsInp = row.querySelector('.ifp-hscode');
                const detailsTa = row.querySelector('textarea[name*="[Details]"]');
                const uomSel = row.querySelector('.ifp-uom');
                const saleTypeSel = row.querySelector('.ifp-sale-type');
                const saleTypeToRateSel = row.querySelector('.ifp-sale-type-to-rate');
                const sroScheduleSel = row.querySelector('.ifp-sro-schedule');
                const sroItemSel = row.querySelector('.ifp-sro-item');

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

    // ── Tax dropdowns (per row): HS Code → UOM; Sale Type → Sale Type To Rate → SRO Schedule → SRO Item ──
    function clearUomForRow(row) {
        var sel = row.querySelector('.ifp-uom');
        if (!sel) return;
        sel.innerHTML = '';
        var opt = document.createElement('option');
        opt.value = ''; opt.textContent = 'Select UOM';
        sel.appendChild(opt);
    }

    function fetchHsCodeInfoForRow(row) {
        var hsInp = row.querySelector('.ifp-hscode');
        var uomSel = row.querySelector('.ifp-uom');
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
        var sel = row.querySelector('.ifp-sale-type');
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
        var sel = row.querySelector('.ifp-sale-type-to-rate');
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
                    if (selected && String(selected) === String(item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                var v = sel.value;
                if (v) {
                    var opt = sel.options[sel.selectedIndex];
                    if (opt && opt.getAttribute('data-rate-pct')) {
                        var gstEl = row.querySelector('.ifp-gst-pct');
                        if (gstEl) { gstEl.value = opt.getAttribute('data-rate-pct'); calcRow(row); calcSummary(); }
                    }
                    loadSroSchedulesForRow(row, v);
                }
            })
            .catch(function() {});
    }

    function loadSroSchedulesForRow(row, rateId) {
        var sel = row.querySelector('.ifp-sro-schedule');
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
        var sel = row.querySelector('.ifp-sro-item');
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
        var hsInp = row.querySelector('.ifp-hscode');
        var uomSel = row.querySelector('.ifp-uom');
        var saleTypeSel = row.querySelector('.ifp-sale-type');
        var saleTypeToRateSel = row.querySelector('.ifp-sale-type-to-rate');
        var sroScheduleSel = row.querySelector('.ifp-sro-schedule');
        var sroItemSel = row.querySelector('.ifp-sro-item');

        if (hsInp && uomSel && hsInp.value && String(hsInp.value).trim() !== '') {
            fetchHsCodeInfoForRow(row);
        }

        if (saleTypeSel) {
            saleTypeSel.addEventListener('change', function() {
                var v = saleTypeSel.value || '';
                if (saleTypeToRateSel) {
                    saleTypeToRateSel.setAttribute('data-selected', '');
                    saleTypeToRateSel.innerHTML = '';
                    var o = document.createElement('option'); o.value = ''; o.textContent = 'Select Sale Type first'; saleTypeToRateSel.appendChild(o);
                }
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
                var gstEl = row.querySelector('.ifp-gst-pct');
                if (v) {
                    var opt = saleTypeToRateSel.options[saleTypeToRateSel.selectedIndex];
                    if (opt && opt.getAttribute('data-rate-pct') && gstEl) {
                        gstEl.value = opt.getAttribute('data-rate-pct');
                        calcRow(row);
                        calcSummary();
                    }
                } else if (gstEl) gstEl.value = '0';
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
        tr.innerHTML =
            '<td><select name="items[' + idx + '][ProductID]" class="form-select form-select-sm ifp-product">' + optionsHTML + '</select></td>' +
            '<td class="ifp-col-desc"><input type="hidden" name="items[' + idx + '][HsCode]" class="ifp-hscode" value=""><textarea name="items[' + idx + '][Details]" rows="1" class="form-control form-control-sm ifp-details" placeholder="Enter Description"></textarea></td>' +
            '<td><select name="items[' + idx + '][UomID]" class="form-select form-select-sm ifp-uom" data-selected=""><option value="">Select UOM</option></select></td>' +
            '<td><select name="items[' + idx + '][SaleTypeID]" class="form-select form-select-sm ifp-sale-type" data-selected=""><option value="">Loading...</option></select></td>' +
            '<td><select name="items[' + idx + '][SaleTypeToRateID]" class="form-select form-select-sm ifp-sale-type-to-rate" data-selected=""><option value="">Select Sale Type first</option></select></td>' +
            '<td><select name="items[' + idx + '][SroScheduleID]" class="form-select form-select-sm ifp-sro-schedule" data-selected=""><option value="">Select Sale Type To Rate first</option></select></td>' +
            '<td><select name="items[' + idx + '][SroItemID]" class="form-select form-select-sm ifp-sro-item" data-selected=""><option value="">Select SRO Schedule first</option></select></td>' +
            '<td><input type="number" name="items[' + idx + '][Quantity]" class="form-control form-control-sm ifp-qty" value="1" min="0" step="0.000001"></td>' +
            '<td><input type="number" step="0.000001" name="items[' + idx + '][Rate]"               class="form-control form-control-sm ifp-rate"     value="0" min="0"></td>' +
            '<td><input type="number" step="0.01"     name="items[' + idx + '][Amount]"             class="form-control form-control-sm ifp-amount"   value="0" readonly></td>' +
            '<td><input type="number" step="0.01"     name="items[' + idx + '][DiscountPercentage]" class="form-control form-control-sm ifp-disc-pct" value="0" min="0" max="100"></td>' +
            '<td><input type="number" step="0.01"     name="items[' + idx + '][DiscountAmount]"     class="form-control form-control-sm ifp-disc-amt" value="0" readonly></td>' +
            '<td><input type="number" step="0.01"     name="items[' + idx + '][GstPercentage]"      class="form-control form-control-sm ifp-gst-pct"  value="0" min="0"></td>' +
            '<td><input type="number" step="0.01"     name="items[' + idx + '][GSTAmount]"          class="form-control form-control-sm ifp-gst-amt"  value="0" readonly></td>' +
            '<td><input type="number" step="0.01"     name="items[' + idx + '][Total]"              class="form-control form-control-sm ifp-total fw-bold" value="0" readonly></td>' +
            '<td><button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del"><i class="ri-delete-bin-5-line"></i></button></td>';

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
    body.querySelectorAll('.ifp-row').forEach(function(row) {
        bindRow(row);
        bindRowTaxDropdowns(row);
        loadSaleTypesForRow(row);
    });
    calcSummary();

})();
</script>
@endif