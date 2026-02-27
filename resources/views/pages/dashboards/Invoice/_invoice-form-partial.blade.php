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
    <span><strong>Due:</strong> {{ $invoice->DueDate ? \Carbon\Carbon::parse($invoice->DueDate)->format('d M Y') : 'N/A' }}</span>
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

    {{-- ══ Column 1 — Customer + Address ══ --}}
    <div class="col-md-4">
        <div class="row g-2">

            {{-- Customer --}}
            <div class="col-md-12">
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
                            <option value="{{ $cust->ID }}"
                                    data-address="{{ $cust->Address }}"
                                    {{ old('CustomerID', $invoice->CustomerID ?? '') == $cust->ID ? 'selected' : '' }}>
                                {{ $cust->DisplayName }}
                            </option>
                        @endforeach
                    </select>
                    @error('CustomerID')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @endif
            </div>

            {{-- Address --}}
            <div class="col-md-12">
                <label class="form-label fw-semibold">Address</label>
                @if($isReadonly)
                    <div class="form-control form-control-sm bg-light" style="min-height:70px;white-space:pre-line;">{{ $invoice->Address ?? '-' }}</div>
                @else
                    <textarea name="Address"
                              id="{{ $uid }}_Address"
                              rows="3"
                              class="form-control form-control-sm @error('Address') is-invalid @enderror"
                              placeholder="Auto-filled on customer select">{{ old('Address', $invoice->Address ?? '') }}</textarea>
                    @error('Address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @endif
            </div>

        </div>
    </div>

    {{-- ══ Column 2 — Date / TermDays / DueDate ══ --}}
    <div class="col-md-2">
        <div class="row g-2">

            {{-- Invoice Date --}}
            <div class="col-md-12">
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

            {{-- Term Days --}}
            <div class="col-md-12">
                <label class="form-label fw-semibold">Term Days</label>
                @if($isReadonly)
                    <div class="form-control form-control-sm bg-light">{{ $invoice->TermDays ?? '0' }}</div>
                @else
                    <input type="number"
                           name="TermDays"
                           id="{{ $uid }}_TermDays"
                           class="form-control form-control-sm @error('TermDays') is-invalid @enderror"
                           value="{{ old('TermDays', $invoice->TermDays ?? 0) }}"
                           min="0"
                           placeholder="0">
                    @error('TermDays')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @endif
            </div>

            {{-- Due Date --}}
            <div class="col-md-12">
                <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                @if($isReadonly)
                    <div class="form-control form-control-sm bg-light">{{ $invoice->DueDate ? \Carbon\Carbon::parse($invoice->DueDate)->format('d M Y') : '-' }}</div>
                @else
                    <input type="date"
                           name="DueDate"
                           id="{{ $uid }}_DueDate"
                           class="form-control form-control-sm @error('DueDate') is-invalid @enderror"
                           value="{{ old('DueDate', $invoice->DueDate ?? '') }}"
                           required>
                    @error('DueDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @endif
            </div>

        </div>
    </div>

    {{-- ══ Column 3 — Doc No + Status ══ --}}
    <div class="col-md-2">
        <div class="row g-2">

            {{-- Doc No --}}
            <div class="col-md-12">
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

            {{-- Status (edit only) --}}
            @if($isEdit)
            <div class="col-md-12">
                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                <select name="Status" class="form-select form-select-sm @error('Status') is-invalid @enderror" required>
                    <option value="1" {{ old('Status', $invoice->Status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                    <option value="2" {{ old('Status', $invoice->Status ?? 1) == 2 ? 'selected' : '' }}>Archive</option>
                </select>
                @error('Status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @elseif($isCreate)
                <input type="hidden" name="Status" value="1">
            @endif

        </div>
    </div>

    {{-- ══ Line Items Table ══ --}}
    <div class="col-xl-12">
        <table class="table nowrap text-nowrap table-borderless border mt-3 table-inv">
            <thead>
                <tr>
                    <th width="15%">Product / Services</th>
                    <th width="15%">Description</th>
                    <th width="5%">Qty</th>
                    <th width="8%">Rate</th>
                    <th width="8%">Amount</th>
                    <th width="8%">Disc %</th>
                    <th width="8%">Disc Amount</th>
                    <th width="8%">GST Rate</th>
                    <th width="8%">GST Amount</th>
                    <th width="8%">Net</th>
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
                                            {{ ($item->ProductID ?? 0) == $prod->ID ? 'selected' : '' }}>
                                        {{ $prod->ProductName }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </td>
                    <td>
                        @if($isReadonly)
                            <span class="small">{{ $item->Details ?? '-' }}</span>
                        @else
                            <textarea name="items[{{ $idx }}][Details]" rows="1" class="form-control form-control-sm" placeholder="Enter Description">{{ $item->Details ?? '' }}</textarea>
                        @endif
                    </td>
                    <td class="invoice-quantity-container">
                        @if($isReadonly)
                            <span class="small">{{ $item->Quantity ?? 0 }}</span>
                        @else
                            <div class="input-group border rounded flex-nowrap">
                                <button type="button" class="btn btn-sm btn-icon btn-primary input-group-text flex-fill ifp-minus"><i class="ri-subtract-line"></i></button>
                                <input type="number" name="items[{{ $idx }}][Quantity]" class="form-control form-control-sm border-0 text-center ifp-qty" value="{{ $item->Quantity ?? 1 }}" min="0" step="0.000001">
                                <button type="button" class="btn btn-sm btn-icon btn-primary input-group-text flex-fill ifp-plus"><i class="ri-add-line"></i></button>
                            </div>
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
                                        data-gst="{{ $prod->GstPercentage ?? 0 }}">
                                    {{ $prod->ProductName }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td><textarea name="items[0][Details]" rows="1" class="form-control form-control-sm" placeholder="Enter Description"></textarea></td>
                    <td class="invoice-quantity-container">
                        <div class="input-group border rounded flex-nowrap">
                            <button type="button" class="btn btn-sm btn-icon btn-primary input-group-text flex-fill ifp-minus"><i class="ri-subtract-line"></i></button>
                            <input type="number" name="items[0][Quantity]" class="form-control form-control-sm border-0 text-center ifp-qty" value="1" min="0" step="0.000001">
                            <button type="button" class="btn btn-sm btn-icon btn-primary input-group-text flex-fill ifp-plus"><i class="ri-add-line"></i></button>
                        </div>
                    </td>
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
                <tr><td colspan="10" class="text-center text-muted py-3"><i>No line items found.</i></td></tr>
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
                                       value="{{ $isReadonly ? number_format($invoice->SubTotal ?? 0, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><div class="fw-medium">Avail Discount :</div></th>
                            <td>
                                <input type="number" step="0.01" name="TotalDiscount" id="{{ $uid }}_TotalDiscount"
                                       class="form-control invoice-amount-input w-100"
                                       value="{{ $isReadonly ? number_format($invoice->TotalDiscount ?? 0, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><div class="fw-medium">GST :</div></th>
                            <td>
                                <input type="number" step="0.01" name="TotalGST" id="{{ $uid }}_TotalGST"
                                       class="form-control invoice-amount-input w-100"
                                       value="{{ $isReadonly ? number_format($invoice->TotalGST ?? 0, 2) : '0.00' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><div class="fs-14 fw-medium">Total :</div></th>
                            <td>
                                <input type="number" step="0.01" name="GrandTotal" id="{{ $uid }}_GrandTotal"
                                       class="form-control invoice-amount-input w-100 fw-bold"
                                       value="{{ $isReadonly ? number_format($invoice->TotalAmount ?? 0, 2) : '0.00' }}" readonly>
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
        row.querySelectorAll('.ifp-qty,.ifp-rate,.ifp-disc-pct,.ifp-gst-pct').forEach(function(inp) {
            inp.addEventListener('input', calcSummary);
        });

        // +/- qty buttons
        const minusBtn = row.querySelector('.ifp-minus');
        const plusBtn  = row.querySelector('.ifp-plus');
        const qtyInp   = row.querySelector('.ifp-qty');

        if (minusBtn) minusBtn.addEventListener('click', function() {
            qtyInp.value = Math.max(0, n(qtyInp.value) - 1);
            calcSummary();
        });
        if (plusBtn) plusBtn.addEventListener('click', function() {
            qtyInp.value = n(qtyInp.value) + 1;
            calcSummary();
        });

        // Product select → auto-fill rate + GST
        const prodSel = row.querySelector('.ifp-product');
        if (prodSel) prodSel.addEventListener('change', function() {
            const opt   = this.options[this.selectedIndex];
            const rateEl = row.querySelector('.ifp-rate');
            const gstEl  = row.querySelector('.ifp-gst-pct');
            if (rateEl) rateEl.value = opt.dataset.rate || 0;
            if (gstEl)  gstEl.value  = opt.dataset.gst  || 0;
            calcSummary();
        });

        // Delete row
        const delBtn = row.querySelector('.ifp-del');
        if (delBtn) delBtn.addEventListener('click', function() {
            if (body.querySelectorAll('.ifp-row').length === 1) {
                alert('At least one line item is required.');
                return;
            }
            row.remove();
            reIndex();
            calcSummary();
        });
    }

    // ── Re-index row names after delete ──
    function reIndex() {
        body.querySelectorAll('.ifp-row').forEach(function(row, i) {
            row.querySelectorAll('[name]').forEach(function(el) {
                el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
            });
        });
    }

    // ── Add Row ──
    const addBtn = document.getElementById(uid + '_addRow');
    if (addBtn) addBtn.addEventListener('click', function() {
        const idx         = body.querySelectorAll('.ifp-row').length;
        const firstSel    = body.querySelector('.ifp-product');
        const optionsHTML = firstSel ? firstSel.innerHTML : '';

        const tr = document.createElement('tr');
        tr.className = 'ifp-row';
        tr.innerHTML =
            '<td><select name="items[' + idx + '][ProductID]" class="form-select form-select-sm ifp-product">' + optionsHTML + '</select></td>' +
            '<td><textarea name="items[' + idx + '][Details]" rows="1" class="form-control form-control-sm" placeholder="Enter Description"></textarea></td>' +
            '<td class="invoice-quantity-container"><div class="input-group border rounded flex-nowrap">' +
                '<button type="button" class="btn btn-sm btn-icon btn-primary input-group-text flex-fill ifp-minus"><i class="ri-subtract-line"></i></button>' +
                '<input type="number" name="items[' + idx + '][Quantity]" class="form-control form-control-sm border-0 text-center ifp-qty" value="1" min="0" step="0.000001">' +
                '<button type="button" class="btn btn-sm btn-icon btn-primary input-group-text flex-fill ifp-plus"><i class="ri-add-line"></i></button>' +
            '</div></td>' +
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
    });

    // ── Due Date auto-calc from InvoiceDate + TermDays ──
    const dateEl = document.getElementById(uid + '_InvoiceDate');
    const termEl = document.getElementById(uid + '_TermDays');
    const dueEl  = document.getElementById(uid + '_DueDate');

    function calcDueDate() {
        if (!dateEl || !dateEl.value || !termEl || !dueEl) return;
        const d = new Date(dateEl.value);
        d.setDate(d.getDate() + (parseInt(termEl.value) || 0));
        dueEl.value = d.toISOString().split('T')[0];
    }
    if (dateEl) dateEl.addEventListener('change', calcDueDate);
    if (termEl) termEl.addEventListener('input',  calcDueDate);

    // ── Customer → auto-fill Address ──
    const custSel = document.getElementById(uid + '_CustomerID');
    if (custSel) custSel.addEventListener('change', function() {
        const opt    = this.options[this.selectedIndex];
        const addrEl = document.getElementById(uid + '_Address');
        if (addrEl) addrEl.value = opt.dataset.address || '';
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
    body.querySelectorAll('.ifp-row').forEach(bindRow);
    calcSummary();

})();
</script>
@endif