<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Report — Print</title>
    <link href="{{ asset('build/assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        body { padding: 1rem; font-size: 11px; }
        h1 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        .meta { color: #444; margin-bottom: 1rem; font-size: 10px; }
        table { width: 100%; }
        table thead th,
        table tbody td,
        table tfoot td {
            font-family: inherit;
            font-size: 11px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <h1>Sale Report</h1>
    <div class="meta">
        Printed {{ $printed_at->format('Y-m-d H:i') }} —
        Filters:
        Customer {{ $filters['customer_id'] ?: 'All' }},
        Product {{ $filters['product_id'] ?: 'All' }},
        Status {{ $filters['invoice_status'] ?: 'default' }},
        Dates {{ $filters['start_date'] ?: '—' }} to {{ $filters['end_date'] ?: '—' }}
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Doc No</th>
                    <th>Invoice date</th>
                    <th>Status</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">GST</th>
                    <th>FBR Txn</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td>{{ $r->DocNo }}</td>
                        <td>{{ $r->InvoiceDate ? \Carbon\Carbon::parse($r->InvoiceDate)->format('Y-m-d') : '' }}</td>
                        <td>{{ \App\Http\Controllers\SaleReportController::statusLabel($r) }}</td>
                        <td>{{ $r->CustomerDisplayName ?? '' }}</td>
                        <td>{{ $r->product_name ?? '' }}</td>
                        <td class="text-end">{{ number_format((float) ($r->Quantity ?? 0), 4) }}</td>
                        <td class="text-end">{{ number_format((float) ($r->Rate ?? 0), 4) }}</td>
                        <td class="text-end">{{ number_format((float) ($r->Amount ?? 0), 2) }}</td>
                        <td class="text-end">{{ number_format((float) ($r->GSTAmount ?? 0), 2) }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($r->FbrTransactionID ?? '', 20) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="5" class="text-end">Totals ({{ (int) $totals->line_count }} lines)</td>
                    <td class="text-end">{{ number_format((float) $totals->qty, 4) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format((float) $totals->amount, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $totals->gst, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="no-print mt-3">
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print / Save as PDF</button>
        <button type="button" class="btn btn-light btn-sm" onclick="window.close()">Close</button>
    </p>
    <script>
        setTimeout(function () { window.print(); }, 400);
    </script>
</body>
</html>
