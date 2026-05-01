@php
    $invoice     = $invoice ?? null;
    $items       = $items   ?? collect();
    $orientation = $orientation ?? 'portrait';

    $lineNet = static function ($item): float {
        $stored = (float) ($item->Total ?? 0);
        if (abs($stored) >= 0.000001) {
            return $stored;
        }
        $amt = (float) ($item->Amount ?? 0);
        $disc = (float) ($item->DiscountAmount ?? $item->Discount ?? 0);
        $gst = (float) ($item->GSTAmount ?? $item->SalesTaxApplicable ?? 0);

        return max(0, $amt - $disc) + $gst;
    };

    $lineGst = static function ($item): float {
        return (float) ($item->GSTAmount ?? $item->SalesTaxApplicable ?? 0);
    };

    $lineDisc = static function ($item): float {
        return (float) ($item->DiscountAmount ?? $item->Discount ?? 0);
    };

    $sumAmount = 0.0;
    $sumDisc = 0.0;
    $sumGst = 0.0;
    $sumNet = 0.0;
    foreach ($items as $it) {
        $sumAmount += (float) ($it->Amount ?? 0);
        $sumDisc += $lineDisc($it);
        $sumGst += $lineGst($it);
        $sumNet += $lineNet($it);
    }

    $headerSub = (float) ($invoice->SubTotal ?? 0);
    if ($headerSub <= 0 && $sumAmount > 0) {
        $headerSub = $sumAmount;
    }

    $headerDisc = (float) ($invoice->DiscountAmount ?? $invoice->TotalDiscount ?? 0);
    if ($headerDisc <= 0 && $sumDisc > 0) {
        $headerDisc = $sumDisc;
    }

    $headerGst = (float) ($invoice->GSTAmount ?? $invoice->TotalGST ?? 0);
    if ($headerGst <= 0 && $sumGst > 0) {
        $headerGst = $sumGst;
    }

    $headerTotal = (float) ($invoice->Total ?? $invoice->TotalAmount ?? 0);
    if ($headerTotal <= 0 && $sumNet > 0) {
        $headerTotal = $sumNet;
    }

    $fbrLogoUrl = asset('images/fbr/fbr-digital-invoicing-badge.svg');
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
        /* FBR: QR exactly 1.0\" × 1.0\" (spec); logo scaled to same height */
        .fbr-digital-section { margin-top: 20px; clear: both; }
        .fbr-digital-title { font-size: 12px; font-weight: 700; margin-bottom: 10px; color: #1e3a5f; }
        .fbr-digital-row {
            display: flex;
            flex-direction: row;
            align-items: flex-end;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 16px;
        }
        .fbr-logo-wrap {
            flex: 0 0 auto;
            border: 1px solid #d8e2f0;
            background: #fff;
            border-radius: 6px;
            padding: 6px;
        }
        .fbr-brand-logo {
            height: 1in;
            width: auto;
            max-width: 2.2in;
            display: block;
            object-fit: contain;
        }
        .fbr-qr-box {
            flex: 0 0 auto;
            border: 1px solid #d8e2f0;
            background: #f7fbff;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }
        .fbr-qr-label { font-size: 11px; font-weight: 700; margin-bottom: 8px; color: #1e3a5f; }
        /* Physical 1\" × 1\" QR per FBR printing guidelines */
        .fbr-qr-image {
            width: 1in;
            height: 1in;
            min-width: 1in;
            min-height: 1in;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 2px;
            object-fit: contain;
            box-sizing: border-box;
        }
        .fbr-qr-txid {
            margin-top: 8px;
            font-size: 9px;
            max-width: 2.4in;
            word-break: break-all;
            color: #374151;
        }
        .fbr-qr-note { margin-top: 6px; font-size: 10px; color: #6b7280; max-width: 3in; }
        @media print {
            .fbr-brand-logo { height: 1in; max-width: 2.2in; }
            .fbr-qr-image { width: 1in !important; height: 1in !important; }
        }
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
                @php $net = $lineNet($item); @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->ProductName ?? '-' }}</td>
                    <td>{{ $item->ProductDescription ?? $item->Details ?? '-' }}</td>
                    <td class="num">{{ $item->Quantity ?? 0 }}</td>
                    <td class="num">{{ number_format((float) ($item->Rate ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float) ($item->Amount ?? 0), 2) }}</td>
                    <td class="num">{{ number_format($lineDisc($item), 2) }}</td>
                    <td class="num">{{ number_format($lineGst($item), 2) }}</td>
                    <td class="num">{{ number_format($net, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center">No line items.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr><th>Sub Total</th><td>{{ number_format($headerSub, 2) }} PKR</td></tr>
                <tr><th>Discount</th><td>{{ number_format($headerDisc, 2) }} PKR</td></tr>
                <tr><th>GST</th><td>{{ number_format($headerGst, 2) }} PKR</td></tr>
                <tr class="grand"><th>Total</th><td>{{ number_format($headerTotal, 2) }} PKR</td></tr>
            </table>
        </div>

        @php
            $fbrTxId = trim((string) ($invoice->FbrTransactionID ?? ''));
            /* High-res source image scaled in CSS to 1\" print (≈300px for crisp output) */
            $qrPx = 320;
            $qrSource = $fbrTxId !== ''
                ? 'https://api.qrserver.com/v1/create-qr-code/?size='.$qrPx.'x'.$qrPx.'&ecc=M&data='.urlencode($fbrTxId)
                : '';
        @endphp
        <div class="fbr-digital-section">
            <div class="fbr-digital-title">FBR Digital Invoice</div>
            <div class="fbr-digital-row">
                <div class="fbr-logo-wrap">
                    <img src="{{ $fbrLogoUrl }}" alt="FBR Digital Invoicing System" class="fbr-brand-logo">
                </div>
                <div class="fbr-qr-box">
                    <div class="fbr-qr-label">QR code (1.0&quot; × 1.0&quot;)</div>
                    @if($fbrTxId !== '')
                        <img src="{{ $qrSource }}" alt="FBR QR Code" class="fbr-qr-image" width="{{ $qrPx }}" height="{{ $qrPx }}">
                        <div class="fbr-qr-txid">Transaction ID: {{ $fbrTxId }}</div>
                    @else
                        <div class="fbr-qr-placeholder" style="width:1in;min-width:1in;height:1in;min-height:1in;border:1px dashed #cbd5e1;background:#f8fafc;display:flex;align-items:center;justify-content:center;font-size:9px;color:#64748b;text-align:center;padding:4px;box-sizing:border-box;">
                            QR after FBR post
                        </div>
                        <div class="fbr-qr-note">QR will appear here once the invoice is posted to FBR.</div>
                    @endif
                </div>
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
