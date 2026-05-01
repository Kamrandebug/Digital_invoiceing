{{-- Sale Report — line-level sales from invoice header/detail + customer + product --}}
@extends('layouts.master')

@section('styles')
<style>
    .sale-report-toolbar .btn { white-space: nowrap; }
    #saleReportTable th[data-col],
    #saleReportTable td[data-col] { transition: opacity 0.15s ease; }
    #saleReportTable col.hidden-col { visibility: collapse; width: 0 !important; }
    /* Match <td> font family and size to <th> */
    #saleReportTable thead th,
    #saleReportTable tbody td,
    #saleReportTable tfoot td {
        font-family: var(--bs-body-font-family, inherit);
        font-size: 0.875rem;
    }
    @media print {
        .no-print { display: none !important; }
        #saleReportTable { font-size: 10px; }
        #saleReportTable thead th,
        #saleReportTable tbody td,
        #saleReportTable tfoot td { font-size: 10px; }
    }
</style>
@endsection

@section('content')
@php
    $q = request()->query();
    $exportUrl = route('reports.sale.export', $q);
    $printUrl = route('reports.sale.print', $q);
    $mailtoSubject = rawurlencode('Sale Report');
    $mailtoBody = rawurlencode(
        "Sale Report (filtered)\n\n"
        . 'Date range: ' . ($filters['start_date'] ?? '—') . ' to ' . ($filters['end_date'] ?? '—') . "\n"
        . 'Lines: ' . ($totals->line_count ?? 0) . "\n"
        . 'Total amount: ' . number_format((float) ($totals->amount ?? 0), 2) . "\n"
        . 'Export CSV from: ' . $exportUrl
    );
    $mailtoHref = 'mailto:?subject=' . $mailtoSubject . '&body=' . $mailtoBody;
@endphp

<div class="page-header-breadcrumb mb-3 no-print">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Sale Report</h1>
        <div class="sale-report-toolbar d-flex flex-wrap align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-sm btn-secondary-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ri-layout-column-line me-1"></i> Columns
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-3 shadow" style="min-width: 14rem;" onclick="event.stopPropagation();">
                    <li class="small text-muted mb-2">Toggle visibility</li>
                    @foreach ([
                        'docno' => 'Doc No',
                        'invdate' => 'Invoice date',
                        'status' => 'Invoice status',
                        'customer' => 'Customer',
                        'product' => 'Product',
                        'desc' => 'Description',
                        'hscode' => 'HS code',
                        'uom' => 'UOM',
                        'qty' => 'Qty',
                        'rate' => 'Rate',
                        'amount' => 'Amount',
                        'disc' => 'Discount',
                        'gst' => 'GST',
                        'extra' => 'Extra tax',
                        'further' => 'Further tax',
                        'fed' => 'FED payable',
                        'fbr' => 'FBR Txn ID',
                    ] as $col => $label)
                        <li class="form-check mb-1">
                            <input class="form-check-input sr-col-toggle" type="checkbox" id="sr_col_{{ $col }}" data-col="{{ $col }}" checked>
                            <label class="form-check-label small" for="sr_col_{{ $col }}">{{ $label }}</label>
                        </li>
                    @endforeach
                </ul>
            </div>
            <a href="{{ $exportUrl }}" class="btn btn-sm btn-primary"><i class="ri-file-excel-2-line me-1"></i> Export CSV</a>
            <button type="button" class="btn btn-sm btn-primary-light" id="saleReportPrintBtn"><i class="ri-printer-line me-1"></i> Print / PDF</button>
            <a href="{{ $mailtoHref }}" class="btn btn-sm btn-outline-secondary" title="Opens your email app with a short summary"><i class="ri-mail-send-line me-1"></i> Email</a>
        </div>
    </div>
</div>

<div class="card custom-card mb-3 no-print">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.sale') }}" class="row g-2 align-items-end" id="saleReportFilterForm">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-0">Customer</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">All customers</option>
                    @foreach ($customers as $c)
                        @php
                            $label = $c->DisplayName ?? trim(($c->FirstName ?? '') . ' ' . ($c->LastName ?? ''));
                            if (!empty($c->BusinessName)) { $label = $c->BusinessName . ($label ? ' — '.$label : ''); }
                        @endphp
                        <option value="{{ $c->ID }}" @selected((string)($filters['customer_id'] ?? '') === (string)$c->ID)>{{ $label ?: 'Customer #'.$c->ID }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-0">Product</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">All products</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->ID }}" @selected((string)($filters['product_id'] ?? '') === (string)$p->ID)>{{ $p->ProductName ?? $p->Name ?? ('#'.$p->ID) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-0">Invoice status</label>
                <select name="invoice_status" class="form-select form-select-sm">
                    <option value="" @selected(($filters['invoice_status'] ?? '') === '')>Active (excl. archived)</option>
                    <option value="active" @selected(($filters['invoice_status'] ?? '') === 'active')>Status = Active</option>
                    <option value="not_posted_fbr" @selected(($filters['invoice_status'] ?? '') === 'not_posted_fbr')>Draft (not posted to FBR)</option>
                    <option value="posted_fbr" @selected(($filters['invoice_status'] ?? '') === 'posted_fbr')>Posted to FBR</option>
                    <option value="archived" @selected(($filters['invoice_status'] ?? '') === 'archived')>Archived</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-2">
                <label class="form-label small mb-0">Start date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $filters['start_date'] ?? '' }}">
            </div>
            <div class="col-md-2 col-lg-2">
                <label class="form-label small mb-0">End date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $filters['end_date'] ?? '' }}">
            </div>
            <div class="col-md-6 col-lg-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                <a href="{{ route('reports.sale') }}" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card custom-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover text-nowrap mb-0" id="saleReportTable">
                <thead class="table-light">
                    <tr>
                        <th data-col="docno">Doc No</th>
                        <th data-col="invdate">Invoice date</th>
                        <th data-col="status">Invoice status</th>
                        <th data-col="customer">Customer</th>
                        <th data-col="product">Product</th>
                        <th data-col="desc">Description</th>
                        <th data-col="hscode">HS code</th>
                        <th data-col="uom">UOM</th>
                        <th class="text-end" data-col="qty">Qty</th>
                        <th class="text-end" data-col="rate">Rate</th>
                        <th class="text-end" data-col="amount">Amount</th>
                        <th class="text-end" data-col="disc">Discount</th>
                        <th class="text-end" data-col="gst">GST</th>
                        <th class="text-end" data-col="extra">Extra tax</th>
                        <th class="text-end" data-col="further">Further tax</th>
                        <th class="text-end" data-col="fed">FED payable</th>
                        <th data-col="fbr">FBR Txn ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td data-col="docno">{{ $r->DocNo ?? '—' }}</td>
                            <td data-col="invdate">{{ $r->InvoiceDate ? \Carbon\Carbon::parse($r->InvoiceDate)->format('Y-m-d') : '—' }}</td>
                            <td data-col="status">{{ \App\Http\Controllers\SaleReportController::statusLabel($r) }}</td>
                            <td data-col="customer">{{ $r->CustomerDisplayName ?? '—' }}</td>
                            <td data-col="product">{{ $r->product_name ?? '—' }}</td>
                            <td data-col="desc">{{ \Illuminate\Support\Str::limit($r->line_description ?? '', 80) }}</td>
                            <td data-col="hscode">{{ $r->HsCode ?? '—' }}</td>
                            <td data-col="uom">{{ $r->Uom_Desc ?? '—' }}</td>
                            <td class="text-end" data-col="qty">{{ number_format((float) ($r->Quantity ?? 0), 4) }}</td>
                            <td class="text-end" data-col="rate">{{ number_format((float) ($r->Rate ?? 0), 4) }}</td>
                            <td class="text-end" data-col="amount">{{ number_format((float) ($r->Amount ?? 0), 2) }}</td>
                            <td class="text-end" data-col="disc">{{ number_format((float) ($r->DiscountAmount ?? 0), 2) }}</td>
                            <td class="text-end" data-col="gst">{{ number_format((float) ($r->GSTAmount ?? 0), 2) }}</td>
                            <td class="text-end" data-col="extra">{{ number_format((float) ($r->ExtraTax ?? 0), 2) }}</td>
                            <td class="text-end" data-col="further">{{ number_format((float) ($r->FurtherTax ?? 0), 2) }}</td>
                            <td class="text-end" data-col="fed">{{ number_format((float) ($r->FedPayable ?? 0), 2) }}</td>
                            <td data-col="fbr">{{ $r->FbrTransactionID ? \Illuminate\Support\Str::limit($r->FbrTransactionID, 24) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18" class="text-center text-muted py-4">No rows match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-secondary fw-semibold">
                    <tr>
                        <td data-col="docno" colspan="8" class="text-end">Totals ({{ (int) ($totals->line_count ?? 0) }} lines)</td>
                        <td class="text-end" data-col="qty">{{ number_format((float) ($totals->qty ?? 0), 4) }}</td>
                        <td data-col="rate"></td>
                        <td class="text-end" data-col="amount">{{ number_format((float) ($totals->amount ?? 0), 2) }}</td>
                        <td class="text-end" data-col="disc">{{ number_format((float) ($totals->discount ?? 0), 2) }}</td>
                        <td class="text-end" data-col="gst">{{ number_format((float) ($totals->gst ?? 0), 2) }}</td>
                        <td class="text-end" data-col="extra">{{ number_format((float) ($totals->extra_tax ?? 0), 2) }}</td>
                        <td class="text-end" data-col="further">{{ number_format((float) ($totals->further_tax ?? 0), 2) }}</td>
                        <td class="text-end" data-col="fed">{{ number_format((float) ($totals->fed_payable ?? 0), 2) }}</td>
                        <td data-col="fbr"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var storageKey = 'sale_report_hidden_cols';
    function loadHidden() {
        try {
            var raw = localStorage.getItem(storageKey);
            return raw ? JSON.parse(raw) : [];
        } catch (e) { return []; }
    }
    function saveHidden(arr) {
        try { localStorage.setItem(storageKey, JSON.stringify(arr)); } catch (e) {}
    }
    function applyColVisibility(hidden) {
        document.querySelectorAll('#saleReportTable [data-col]').forEach(function (el) {
            var c = el.getAttribute('data-col');
            if (!c) return;
            el.style.display = hidden.indexOf(c) !== -1 ? 'none' : '';
        });
        document.querySelectorAll('.sr-col-toggle').forEach(function (cb) {
            var c = cb.getAttribute('data-col');
            cb.checked = hidden.indexOf(c) === -1;
        });
    }
    var hidden = loadHidden();
    applyColVisibility(hidden);

    document.querySelectorAll('.sr-col-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var c = cb.getAttribute('data-col');
            var idx = hidden.indexOf(c);
            if (cb.checked && idx !== -1) hidden.splice(idx, 1);
            if (!cb.checked && idx === -1) hidden.push(c);
            saveHidden(hidden);
            applyColVisibility(hidden);
        });
    });

    var printBtn = document.getElementById('saleReportPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.open(@json($printUrl), '_blank', 'noopener');
        });
    }
})();
</script>
@endsection
