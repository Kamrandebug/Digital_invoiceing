@php
    $invoice     = $invoice ?? null;
    $items       = $items   ?? collect();
    $orientation = $orientation ?? 'portrait';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Invoice {{ $invoice->DocNo ?? 'Print' }}</title>
    <style>
        @page { size: {{ $orientation }}; margin: 15mm; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #222; margin: 0; padding: 12px; }
        body.orientation-landscape .invoice-print { max-width: none; }
        body.orientation-landscape .header { flex-direction: row; }
        body.orientation-portrait .invoice-print { max-width: 210mm; margin: 0 auto; }
        .invoice-print { max-width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #333; }
        .header-left h1 { margin: 0 0 4px 0; font-size: 20px; }
        .header-right { text-align: right; }
        .meta { margin-bottom: 16px; }
        .meta table { width: 100%; }
        .meta th { text-align: left; padding: 2px 8px 2px 0; width: 110px; color: #555; }
        .meta td { padding: 2px 0; }
        table.items { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.items th, table.items td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        table.items th { background: #f5f5f5; font-size: 11px; }
        table.items td { font-size: 11px; }
        table.items .num { text-align: right; }
        .totals { margin-top: 16px; margin-left: auto; width: 280px; }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals th { text-align: right; padding: 4px 12px 4px 0; font-weight: 600; }
        .totals td { text-align: right; padding: 4px 0; }
        .totals tr.grand th, .totals tr.grand td { font-size: 14px; font-weight: bold; border-top: 2px solid #333; padding-top: 8px; }
        .notes { margin-top: 20px; padding: 8px; background: #f9f9f9; border-radius: 4px; font-size: 11px; white-space: pre-line; }
        .fbr-qr-wrap { margin-top: 18px; display: flex; justify-content: flex-end; }
        .fbr-qr-box {
            width: 280px;
            border: 1px solid #d8e2f0;
            background: #f7fbff;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }
        .fbr-qr-title { font-size: 12px; font-weight: 700; margin-bottom: 8px; }
        .fbr-qr-image {
            width: 110px;
            height: 110px;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 4px;
        }
        .fbr-qr-txid {
            margin-top: 6px;
            font-size: 10px;
            word-break: break-all;
            color: #374151;
        }
        .fbr-qr-note { margin-top: 4px; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body class="orientation-{{ $orientation }}">
    @if($invoice)
    <div class="invoice-print">
        <div class="header">
            <div class="header-left">
                <h1>INVOICE</h1>
                <div class="meta">
                    <table>
                        <tr><th>Doc No.</th><td>{{ $invoice->DocNo ?? 'N/A' }}</td></tr>
                        <tr><th>Invoice Date</th><td>{{ $invoice->InvoiceDate ? \Carbon\Carbon::parse($invoice->InvoiceDate)->format('d M Y') : '-' }}</td></tr>
                        <tr><th>Due Date</th><td>{{ $invoice->DueDate ? \Carbon\Carbon::parse($invoice->DueDate)->format('d M Y') : '-' }}</td></tr>
                    </table>
                </div>
            </div>
            <div class="header-right">
                <strong>Bill To</strong><br>
                {{ $invoice->CustomerDisplayName ?? 'N/A' }}
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product / Service</th>
                    <th>Description</th>
                    <th class="num">Qty</th>
                    <th class="num">Rate</th>
                    <th class="num">Amount</th>
                    <th class="num">Disc</th>
                    <th class="num">GST</th>
                    <th class="num">Net</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->ProductName ?? '-' }}</td>
                    <td>{{ $item->Details ?? '-' }}</td>
                    <td class="num">{{ $item->Quantity ?? 0 }}</td>
                    <td class="num">{{ number_format($item->Rate ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($item->Amount ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($item->DiscountAmount ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($item->GSTAmount ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($item->Total ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center">No line items.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr><th>Sub Total</th><td>{{ number_format($invoice->SubTotal ?? 0, 2) }} PKR</td></tr>
                <tr><th>Discount</th><td>{{ number_format($invoice->TotalDiscount ?? 0, 2) }} PKR</td></tr>
                <tr><th>GST</th><td>{{ number_format($invoice->TotalGST ?? 0, 2) }} PKR</td></tr>
                <tr class="grand"><th>Total</th><td>{{ number_format($invoice->TotalAmount ?? $invoice->Total ?? 0, 2) }} PKR</td></tr>
            </table>
        </div>

        @php
            $fbrTxId = trim((string) ($invoice->FbrTransactionID ?? ''));
            $qrSource = $fbrTxId !== '' ? 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($fbrTxId) : '';
        @endphp
        <div class="fbr-qr-wrap">
            <div class="fbr-qr-box">
                <div class="fbr-qr-title">FBR Digital Invoice QR</div>
                @if($fbrTxId !== '')
                    <img src="{{ $qrSource }}" alt="FBR QR Code" class="fbr-qr-image">
                    <div class="fbr-qr-txid">Transaction ID: {{ $fbrTxId }}</div>
                @else
                    <div class="fbr-qr-note">QR not available (invoice not posted to FBR yet).</div>
                @endif
            </div>
        </div>

        @if(!empty(trim($invoice->Notes ?? '')))
        <div class="notes"><strong>Notes:</strong><br>{{ $invoice->Notes }}</div>
        @endif
    </div>
    @else
    <p>Invoice not found.</p>
    @endif
</body>
</html>
