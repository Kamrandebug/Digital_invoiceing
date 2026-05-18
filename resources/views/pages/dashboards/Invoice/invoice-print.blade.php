@php
    $invoice     = $invoice ?? null;
    $items       = $items   ?? collect();
    $orientation = $orientation ?? 'portrait';
    $seller      = $seller ?? null;
    $buyer       = $buyer ?? null;

    $sellerLogoSrc = '';
    if ($seller) {
        $raw = trim((string) ($seller->ImageUrl ?? ''));
        if ($raw !== '') {
            if (preg_match('#^https?://#i', $raw)) {
                $sellerLogoSrc = $raw;
            } elseif (str_starts_with($raw, '//')) {
                $sellerLogoSrc = request()->getScheme().':'.$raw;
            } elseif (str_starts_with($raw, '/')) {
                $absPath = ltrim(str_replace('\\', '/', $raw), '/');
                // DB may wrongly store /business_logos/...; files live under storage/app/public/ => /storage/...
                if (str_starts_with($absPath, 'business_logos/')) {
                    $sellerLogoSrc = rtrim(request()->getSchemeAndHttpHost(), '/').'/storage/'.$absPath;
                } else {
                    $sellerLogoSrc = rtrim(request()->getSchemeAndHttpHost(), '/').$raw;
                }
            } else {
                $path = ltrim(str_replace('\\', '/', $raw), '/');
                if (str_starts_with($path, 'public/')) {
                    $sellerLogoSrc = asset(substr($path, strlen('public/')));
                } elseif (str_starts_with($path, 'storage/')) {
                    $sellerLogoSrc = asset($path);
                } else {
                    // BusinessInfoController stores paths relative to the public disk (e.g. business_logos/...)
                    $sellerLogoSrc = asset('storage/'.$path);
                }
            }
        }
    }

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
    $showFixedNotifiedColumn = false;
    foreach ($items as $it) {
        $sumAmount += (float) ($it->Amount ?? 0);
        $sumDisc += $lineDisc($it);
        $sumGst += $lineGst($it);
        $sumNet += $lineNet($it);
        if ((float) ($it->FixedNotifiedValueOrRetailPrice ?? 0) > 0.000001) {
            $showFixedNotifiedColumn = true;
        }
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

    $advanceTaxRate = 0.0;
    $advanceTaxOn = $buyer && (int) ($buyer->IsAdvanceTax ?? $buyer->IsAdvancedTax ?? 0) === 1;
    if ($advanceTaxOn) {
        $advanceTaxRate = (float) ($buyer->AdvanceTax ?? $buyer->AdvanceTaxRate ?? 0);
    }
    $customerWht236g = $advanceTaxRate > 0.000001
        ? round($headerTotal * ($advanceTaxRate / 100.0), 2)
        : 0.0;
    $grandTotalAllTaxes = round($headerTotal + $customerWht236g, 2);

    $fbrLogoUrl = asset('images/fbr/fbr-digital-invoicing-badge.jpg');
    $fbrTxId = trim((string) data_get($invoice, 'FbrTransactionID', ''));
    $qrPx = 320;
    $qrSource = $fbrTxId !== ''
        ? 'https://api.qrserver.com/v1/create-qr-code/?size='.$qrPx.'x'.$qrPx.'&ecc=M&data='.urlencode($fbrTxId)
        : '';
    $printDocumentTitle = $invoice
        ? 'GD-DI-'.\Carbon\Carbon::now()->format('Ymd').'-'.str_pad((string) ($invoice->ID ?? 0), 2, '0', STR_PAD_LEFT)
        : 'Invoice Print';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $printDocumentTitle }}</title>
    <style>
        @page { size: {{ $orientation }}; margin: 15mm; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .print-section-1-logo img { max-height: 80px !important; }
            .fbr-brand-logo { max-width: 1in !important; max-height: 1in !important; }
            .fbr-qr-image { width: 1in !important; height: 1in !important; }
        }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #222; margin: 0; padding: 12px; }
        body.orientation-landscape .invoice-print { max-width: none; }
        body.orientation-portrait .invoice-print { max-width: 210mm; margin: 0 auto; }
        .invoice-print { max-width: 100%; }
        .invoice-print-head {
            display: flex;
            justify-content: flex-end;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .invoice-doc-title {
            text-align: right;
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #111;
        }
        /* Section 1: business logo (left, aligned with Doc No row) + invoice meta (right) */
        .print-section-1 {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #333;
        }
        .print-section-1-logo {
            flex: 0 0 auto;
            max-width: 220px;
            align-self: flex-start;
        }
        .print-section-1-logo img {
            max-width: 200px;
            max-height: 80px;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
        }
        .print-section-1-meta { text-align: right; flex: 0 0 auto; min-width: 0; margin-left: auto; }
        .print-section-1-meta table { margin-left: auto; border-collapse: collapse; }
        .print-section-1-meta th { text-align: right; padding: 2px 8px 2px 0; color: #555; font-weight: 600; white-space: nowrap; vertical-align: top; }
        .print-section-1-meta td { text-align: right; padding: 2px 0; }
        /* Section 2: wider seller/buyer columns so addresses & email wrap cleanly in PDF */
        .print-section-2 {
            display: grid;
            grid-template-columns: 38% 24% 38%;
            align-items: start;
            column-gap: 0;
            padding: 14px 0 16px;
            border-bottom: 2px solid #333;
            margin-bottom: 16px;
        }
        .party-col { min-width: 0; font-size: 12px; text-align: left; }
        .party-spacer { min-width: 0; }
        .party-col h2 {
            margin: 0 0 10px 0;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #111;
            text-align: left;
            text-decoration: underline;
            text-decoration-thickness: 2px;
            text-underline-offset: 5px;
            text-decoration-color: #222;
        }
        .party-col table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .party-col th {
            text-align: left;
            padding: 2px 10px 2px 0;
            width: 30%;
            max-width: 7.5rem;
            white-space: nowrap;
            color: #555;
            font-weight: 600;
            vertical-align: top;
        }
        .party-col td {
            padding: 2px 0;
            width: 70%;
            vertical-align: top;
            text-align: left;
            word-break: normal;
            overflow-wrap: break-word;
            hyphens: none;
            line-height: 1.35;
        }
        .party-col .party-name {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #111;
            text-align: left;
            word-break: normal;
            overflow-wrap: break-word;
            hyphens: none;
            line-height: 1.35;
        }
        table.items { width: 100%; border-collapse: collapse; margin: 16px 0; table-layout: fixed; }
        /* Column widths: description = HS code + product description text */
        table.items col.col-idx { width: 4%; }
        table.items col.col-desc { width: 24%; }
        table.items col.col-qty { width: 8%; }
        table.items col.col-uom { width: 10%; }
        table.items col.col-rate { width: 9%; }
        table.items col.col-value { width: 9%; }
        table.items col.col-fixed { width: 8%; }
        table.items col.col-taxrate { width: 8%; }
        table.items col.col-tax { width: 9%; }
        table.items col.col-inclusive { width: 11%; }
        /* When Fixed Notified column is hidden, redistribute its width */
        table.items.items--no-fixed col.col-desc { width: 29%; }
        table.items.items--no-fixed col.col-inclusive { width: 14%; }
        table.items th, table.items td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; box-sizing: border-box; }
        table.items th {
            background: #f5f5f5;
            font-size: 12px;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: none;
            vertical-align: middle;
            min-width: 0;
            line-height: 1.25;
        }
        table.items td { font-size: 12px; hyphens: none; }
        table.items .num { text-align: right; }
        table.items .cell-mid { text-align: center; }
        /* Unit (UOM): enough padding + wrapping so text does not crowd the border */
        table.items th.cell-uom,
        table.items td.cell-uom {
            text-align: center;
            overflow-wrap: break-word;
            word-break: break-word;
            padding: 6px 10px;
            vertical-align: middle;
        }
        table.items th.th-inclusive { line-height: 1.2; font-size: 10px; }
        /* Description of Goods: bold HS code, then product description */
        table.items td.td-desc-goods { vertical-align: top; }
        .desc-goods-hs { font-size: 12px; color: #111; margin: 0 0 6px 0; line-height: 1.35; }
        .desc-goods-hs strong { font-weight: 700; }
        .desc-goods-detail { font-size: 12px; color: #333; margin: 0; line-height: 1.4; word-break: break-word; }
        .after-table-summary {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .invoice-print-footer {
            padding-bottom: 14px;
            border-bottom: 2px solid #333;
        }
        .totals { margin-top: 0; margin-left: auto; width: 360px; flex-shrink: 0; }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals th { text-align: right; padding: 4px 12px 4px 0; font-weight: 600; white-space: normal; line-height: 1.25; }
        .totals td { text-align: right; padding: 4px 0; }
        .totals tr.grand th, .totals tr.grand td { font-size: 14px; font-weight: bold; border-top: 2px solid #333; padding-top: 8px; padding-bottom: 8px; }
        .totals tr.totals-inc th, .totals tr.totals-inc td {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .notes { margin-top: 20px; padding: 8px; background: #f9f9f9; border-radius: 4px; font-size: 12px; white-space: pre-line; }
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
    </style>
</head>
<body class="orientation-{{ $orientation }}">
    @if($invoice)
    <div class="invoice-print">
        <div class="invoice-print-head">
            <h1 class="invoice-doc-title">Sales Tax Invoice</h1>
        </div>

        {{-- Section 1: logo (left, Doc No row) + invoice numbers / dates (right) --}}
        <div class="print-section-1">
            @if($sellerLogoSrc !== '')
            <div class="print-section-1-logo">
                <img src="{{ $sellerLogoSrc }}" alt="{{ trim((string) ($seller->BusinessName ?? '')) ?: 'Business logo' }}">
            </div>
            @endif
            <div class="print-section-1-meta">
                <table>
                    <tr><th>Doc No.</th><td>{{ $invoice->DocNo ?? 'N/A' }}</td></tr>
                    <tr><th>Invoice Date</th><td>{{ $invoice->InvoiceDate ? \Carbon\Carbon::parse($invoice->InvoiceDate)->format('d M Y') : '-' }}</td></tr>
                    <tr><th>FBR Invoice#</th><td>@if(trim((string) ($invoice->FbrTransactionID ?? '')) !== '')<strong>{{ $invoice->FbrTransactionID }}</strong>@else - @endif</td></tr>
                </table>
            </div>
        </div>

        {{-- Section 2: seller | gap | buyer (wider outer columns for PDF wrapping) --}}
        <div class="print-section-2">
            <div class="party-col party-seller">
                <h2>Seller</h2>
                @if($seller)
                    <div class="party-name">{{ $seller->BusinessName ?? '—' }}</div>
                    <table>
                        @if(!empty(trim((string) ($seller->Address ?? ''))))
                        <tr><th>Address:</th><td>{{ $seller->Address }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($seller->Province ?? ''))))
                        <tr><th>Province:</th><td>{{ $seller->Province }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($seller->Phone ?? ''))))
                        <tr><th>Phone:</th><td>{{ $seller->Phone }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($seller->NTN ?? ''))))
                        <tr><th>NTN:</th><td>{{ $seller->NTN }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($seller->SalesTaxNumber ?? ''))))
                        <tr><th>STN:</th><td>{{ $seller->SalesTaxNumber }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($seller->Email ?? ''))))
                        <tr><th>Email:</th><td>{{ $seller->Email }}</td></tr>
                        @endif
                    </table>
                @else
                    <p style="margin:0;color:#777;">No business profile on file.</p>
                @endif
            </div>
            <div class="party-spacer" aria-hidden="true"></div>
            <div class="party-col party-buyer">
                <h2>Buyer</h2>
                @if($buyer)
                    <div class="party-name">{{ $buyer->BusinessName ?? ($invoice->CustomerDisplayName ?? '—') }}</div>
                    <table>
                        @if(!empty(trim((string) ($buyer->Address ?? ''))))
                        <tr><th>Address:</th><td>{{ $buyer->Address }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($buyer->County ?? ''))))
                        <tr><th>Province:</th><td>{{ $buyer->County }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($buyer->Phone ?? ''))))
                        <tr><th>Phone:</th><td>{{ $buyer->Phone }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($buyer->NTN ?? ''))))
                        <tr><th>NTN:</th><td>{{ $buyer->NTN }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($buyer->SalesTaxNumber ?? ''))))
                        <tr><th>STN:</th><td>{{ $buyer->SalesTaxNumber }}</td></tr>
                        @endif
                        @if(!empty(trim((string) ($buyer->Email ?? ''))))
                        <tr><th>Email:</th><td>{{ $buyer->Email }}</td></tr>
                        @endif
                    </table>
                @else
                    <div class="party-name">{{ $invoice->CustomerDisplayName ?? '—' }}</div>
                @endif
            </div>
        </div>

        {{-- Section 3: line items + totals / FBR (unchanged) --}}
        <table class="items @if(! $showFixedNotifiedColumn) items--no-fixed @endif">
            <colgroup>
                <col class="col-idx">
                <col class="col-desc">
                <col class="col-qty">
                <col class="col-uom">
                <col class="col-rate">
                <col class="col-value">
                @if($showFixedNotifiedColumn)
                <col class="col-fixed">
                @endif
                <col class="col-taxrate">
                <col class="col-tax">
                <col class="col-inclusive">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description of Goods</th>
                    <th class="num">Qty</th>
                    <th class="cell-uom">Unit</th>
                    <th class="num">Unit Rate</th>
                    <th class="num">Value Excel ST</th>
                    @if($showFixedNotifiedColumn)
                    <th class="num">Fixed Notified Value</th>
                    @endif
                    <th class="cell-mid">Sales Tax Rate</th>
                    <th class="num">Sales Tax Amount</th>
                    <th class="num th-inclusive">Value<br>Inclusive<br>Sales Tax</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $item)
                @php $net = $lineNet($item); @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="td-desc-goods">
                        @if(trim((string) ($item->HsCode ?? '')) !== '')
                        <div class="desc-goods-hs"><strong>HS Code: {{ $item->HsCode }}</strong></div>
                        @endif
                        @php $descGoods = trim((string) ($item->ProductDescription ?? $item->Details ?? '')); @endphp
                        @if($descGoods !== '')
                        <div class="desc-goods-detail">{{ $descGoods }}</div>
                        @endif
                        @if(trim((string) ($item->HsCode ?? '')) === '' && $descGoods === '')
                        —
                        @endif
                    </td>
                    <td class="num">{{ $item->Quantity ?? 0 }}</td>
                    <td class="cell-uom">{{ trim((string) ($item->Uom_Desc ?? '')) !== '' ? $item->Uom_Desc : '—' }}</td>
                    <td class="num">{{ number_format((float) ($item->Rate ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float) ($item->Amount ?? 0), 2) }}</td>
                    @if($showFixedNotifiedColumn)
                    <td class="num">{{ number_format((float) ($item->FixedNotifiedValueOrRetailPrice ?? 0), 2) }}</td>
                    @endif
                    <td class="cell-mid">{{ trim((string) ($item->SaleTypeToRateDesc ?? '')) !== '' ? $item->SaleTypeToRateDesc : '—' }}</td>
                    <td class="num">{{ number_format($lineGst($item), 2) }}</td>
                    <td class="num">{{ number_format($net, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="{{ $showFixedNotifiedColumn ? 10 : 9 }}" class="text-center">No line items.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="invoice-print-footer">
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
                    <tr><th>Total Amount Excluding ST:</th><td>{{ number_format($headerSub, 2) }} PKR</td></tr>
                    <tr><th>Discount:</th><td>{{ number_format($headerDisc, 2) }} PKR</td></tr>
                    <tr><th>Total Sales Tax:</th><td>{{ number_format($headerGst, 2) }} PKR</td></tr>
                    @if($advanceTaxRate > 0.000001)
                    <tr class="totals-inc"><th>Total Inc. All Taxes:</th><td>{{ number_format($headerTotal, 2) }} PKR</td></tr>
                    <tr><th>Customer WHT 236G({{ number_format($advanceTaxRate, 2) }}%):</th><td>{{ number_format($customerWht236g, 2) }} PKR</td></tr>
                    <tr class="grand"><th>Grand Total:</th><td>{{ number_format($grandTotalAllTaxes, 2) }} PKR</td></tr>
                    @else
                    <tr class="totals-inc"><th>Total Inc. All Taxes:</th><td>{{ number_format($headerTotal, 2) }} PKR</td></tr>
                    @endif
                </table>
            </div>
        </div>

        @if(!empty(trim($invoice->Notes ?? '')))
        <div class="notes"><strong>Notes:</strong><br>{{ $invoice->Notes }}</div>
        @endif
        </div>
    </div>
    @else
    <p>Invoice not found.</p>
    @endif
</body>
</html>
