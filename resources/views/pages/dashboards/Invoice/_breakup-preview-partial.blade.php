{{-- Breakup Staging Preview Table Partial --}}
@php
    $diff = round($targetQty - $totalQty, 4);
    $isBalanced = abs($diff) < 0.0001;
@endphp

{{-- Top Balance Status & Totals Bar --}}
<div class="row g-2 mb-3 align-items-center">
    <div class="col-md-3 col-sm-6">
        <div class="p-2 border rounded bg-light">
            <span class="d-block text-muted fs-11 text-uppercase fw-semibold">Target Quantity</span>
            <div class="fs-15 fw-bold text-dark">{{ number_format($targetQty, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="p-2 border rounded bg-light">
            <span class="d-block text-muted fs-11 text-uppercase fw-semibold">Generated Sum</span>
            <div class="fs-15 fw-bold text-primary" id="previewTotalQtyDisplay">{{ number_format($totalQty, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="p-2 border rounded {{ $isBalanced ? 'bg-success-transparent border-success' : 'bg-warning-transparent border-warning' }}" id="previewDiffCard">
            <span class="d-block text-muted fs-11 text-uppercase fw-semibold">Difference</span>
            <div class="fs-15 fw-bold {{ $isBalanced ? 'text-success' : 'text-warning' }}" id="previewDiffDisplay">
                @if($isBalanced)
                    <i class="ri-checkbox-circle-line me-1"></i> 0.00 (Balanced)
                @elseif($diff > 0)
                    <i class="ri-alert-line me-1"></i> -{{ number_format($diff, 2) }} (Short)
                @else
                    <i class="ri-alert-line me-1"></i> +{{ number_format(abs($diff), 2) }} (Over)
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="p-2 border rounded bg-light">
            <span class="d-block text-muted fs-11 text-uppercase fw-semibold">Invoices / Grand Total</span>
            <div class="fs-15 fw-bold text-dark">
                <span id="previewTotalCountDisplay">{{ $totalInvoices }}</span> Invs &bull; PKR <span id="previewTotalAmountDisplay">{{ number_format($totalAmount, 2) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Preview Grid Table --}}
<div class="table-responsive border rounded mb-3" style="max-height: 420px; overflow-y: auto;">
    <table class="table table-hover table-sm align-middle mb-0" id="breakupPreviewTable">
        <thead class="table-light sticky-top">
            <tr class="fs-12 text-nowrap">
                <th style="width: 45px;">#</th>
                <th style="width: 140px;">Draft / Inv #</th>
                <th style="width: 130px;">Invoice Date</th>
                <th>Customer</th>
                <th>Product</th>
                <th style="width: 110px;" class="text-end">Qty</th>
                <th style="width: 100px;" class="text-end">Rate</th>
                <th style="width: 100px;" class="text-end">Excl. Tax</th>
                <th style="width: 105px;" class="text-end">Fixed Notified</th>
                <th style="width: 100px;" class="text-end">GST</th>
                <th style="width: 110px;" class="text-end">Total</th>
                <th style="width: 40px;" class="text-center">Action</th>
            </tr>
        </thead>
        <tbody class="fs-12">
            @forelse($rows as $idx => $r)
                @php
                    $rate = $r->Quantity > 0 ? ($r->ValueSalesExcludingST / $r->Quantity) : 0;
                @endphp
                <tr id="preview-row-{{ $r->ID }}" data-temp-id="{{ $r->ID }}">
                    <td class="text-muted text-center">{{ $idx + 1 }}</td>
                    <td>
                        @if(str_starts_with($r->DocNo, 'DRAFT-'))
                            <span class="badge bg-light text-primary border"><i class="ri-draft-line me-1"></i>Draft #{{ $idx + 1 }}</span>
                            <small class="text-muted d-block fs-11">Auto on save</small>
                        @else
                            <span class="fw-semibold text-primary">{{ $r->DocNo }}</span>
                        @endif
                    </td>
                    <td>
                        <input type="date" 
                               class="form-control form-control-sm preview-date-input" 
                               value="{{ $r->InvoiceDate }}" 
                               data-temp-id="{{ $r->ID }}"
                               onchange="onPreviewRowChange({{ $r->ID }})">
                    </td>
                    <td class="text-truncate" style="max-width: 150px;" title="{{ $r->CustomerCode }}">
                        {{ $r->CustomerCode ?: 'Customer #' . $r->CustomerID }}
                    </td>
                    <td class="text-truncate" style="max-width: 160px;" title="{{ $r->ProductDescription }}">
                        {{ $r->ProductDescription }}
                    </td>
                    <td>
                        <input type="number" 
                               step="any" 
                               min="0.0001" 
                               class="form-control form-control-sm text-end fw-semibold preview-qty-input" 
                               id="row-qty-{{ $r->ID }}"
                               value="{{ (float) $r->Quantity }}" 
                               data-temp-id="{{ $r->ID }}"
                               data-rate="{{ $rate }}"
                               onchange="onPreviewRowChange({{ $r->ID }})">
                    </td>
                    <td class="text-end text-muted">
                        {{ number_format($rate, 2) }}
                    </td>
                    <td class="text-end fw-medium" id="row-excl-{{ $r->ID }}">
                        {{ number_format($r->ValueSalesExcludingST, 2) }}
                    </td>
                    <td class="text-end text-muted" id="row-fixed-{{ $r->ID }}">
                        {{ number_format($r->FixedNotifiedValueOrRetailPrice ?? 0, 2) }}
                    </td>
                    <td class="text-end text-muted" id="row-tax-{{ $r->ID }}" title="{{ $r->SaleTypeToRateDesc }}">
                        {{ number_format($r->SalesTaxApplicable, 2) }}
                    </td>
                    <td class="text-end fw-bold text-dark" id="row-total-{{ $r->ID }}">
                        {{ number_format($r->TotalValues, 2) }}
                    </td>
                    <td class="text-center">
                        <button type="button" 
                                class="btn btn-sm btn-icon btn-outline-danger border-0" 
                                title="Delete this invoice" 
                                onclick="deletePreviewRow({{ $r->ID }})">
                            <i class="ri-delete-bin-line fs-14"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center py-4 text-muted">No records generated.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="table-light sticky-bottom fw-bold fs-12">
            <tr>
                <td colspan="5" class="text-end text-uppercase">Totals:</td>
                <td class="text-end text-primary" id="tableFootQty">{{ number_format($totalQty, 2) }}</td>
                <td></td>
                <td class="text-end" id="tableFootExcl">{{ number_format($totalExclTax, 2) }}</td>
                <td class="text-end text-muted" id="tableFootFixed">{{ number_format($totalFixedNotified ?? 0, 2) }}</td>
                <td class="text-end" id="tableFootTax">{{ number_format($totalTax, 2) }}</td>
                <td class="text-end text-dark" id="tableFootTotal">{{ number_format($totalAmount, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Commit Action Bar --}}
<div class="card bg-light border-0 p-3 rounded">
    <div class="row g-2 align-items-center">
        <div class="col-md-6 col-sm-12">
            <label class="form-label fs-12 fw-semibold text-dark mb-1">Batch Title (Optional)</label>
            <input type="text" 
                   class="form-control form-control-sm" 
                   id="commitBatchTitle" 
                   placeholder="e.g. Breakup Invoices - March Sales" 
                   value="Breakup: {{ count($rows) ? ($rows[0]->ProductDescription ?? 'Invoices') : 'Invoices' }} ({{ date('d-M-Y') }})">
        </div>
        <div class="col-md-6 col-sm-12 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="reRollBreakup()">
                <i class="ri-refresh-line me-1"></i> Re-generate / Re-roll
            </button>
            <button type="button" class="btn btn-sm btn-success px-3" id="btnCommitInvoices" onclick="commitBreakupInvoices()">
                <i class="ri-check-double-line me-1"></i> Confirm & Move to Invoices
            </button>
        </div>
    </div>
</div>

