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
    $fbrTxId = trim((string) data_get($invoice, 'FbrTransactionID', ''));
    $qrPx = 320;
    $qrSource = $fbrTxId !== ''
        ? 'https://api.qrserver.com/v1/create-qr-code/?size='.$qrPx.'x'.$qrPx.'&ecc=M&data='.urlencode($fbrTxId)
        : '';
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
        .after-table-summary {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .totals { margin-top: 0; margin-left: auto; width: 280px; flex-shrink: 0; }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals th { text-align: right; padding: 4px 12px 4px 0; font-weight: 600; }
        .totals td { text-align: right; padding: 4px 0; }
        .totals tr.grand th, .totals tr.grand td { font-size: 14px; font-weight: bold; border-top: 2px solid #333; padding-top: 8px; }
        .notes { margin-top: 20px; padding: 8px; background: #f9f9f9; border-radius: 4px; font-size: 11px; white-space: pre-line; }
        /* FBR: matching 1\" × 1\" slots for badge + QR (no captions) */
        .fbr-digital-section { flex: 1 1 auto; min-width: 0; margin-top: 0; }
        .fbr-digital-title { font-size: 12px; font-weight: 700; margin-bottom: 10px; color: #1e3a5f; }
        .fbr-digital-row {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 16px;
        }
        .fbr-badge-wrap {
            flex: 0 0 auto;
            border: 1px solid #d8e2f0;
            background: #fff;
            border-radius: 6px;
            padding: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .fbr-badge-slot {
            width: 1in;
            height: 1in;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .fbr-brand-logo {
            max-width: 1in;
            max-height: 1in;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
        }
        .fbr-qr-image {
            width: 1in;
            height: 1in;
            min-width: 1in;
            min-height: 1in;
            object-fit: contain;
            box-sizing: border-box;
            display: block;
        }
        .fbr-qr-placeholder {
            width: 1in;
            height: 1in;
            min-width: 1in;
            min-height: 1in;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            box-sizing: border-box;
        }
        @media print {
            .fbr-brand-logo { max-width: 1in !important; max-height: 1in !important; }
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

        <div class="after-table-summary">
            <div class="fbr-digital-section">
                <div class="fbr-digital-title">FBR Digital Invoice</div>
                <div class="fbr-digital-row">
                    <div class="fbr-badge-wrap">
                        <div class="fbr-badge-slot">
                            <img src="{{ $fbrLogoUrl }}" alt="FBR Digital Invoicing System" class="fbr-brand-logo">
                        </div>
                    </div>
                    <div class="fbr-badge-wrap">
                        <div class="fbr-badge-slot">
                            @if($fbrTxId !== '')
                                <img src="{{ $qrSource }}" alt="" class="fbr-qr-image" width="{{ $qrPx }}" height="{{ $qrPx }}">
                            @else
                                <div class="fbr-qr-placeholder" aria-hidden="true"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="totals">
                <table>
                    <tr><th>Sub Total</th><td>{{ number_format($headerSub, 2) }} PKR</td></tr>
                    <tr><th>Discount</th><td>{{ number_format($headerDisc, 2) }} PKR</td></tr>
                    <tr><th>GST</th><td>{{ number_format($headerGst, 2) }} PKR</td></tr>
                    <tr class="grand"><th>Total</th><td>{{ number_format($headerTotal, 2) }} PKR</td></tr>
                </table>
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
