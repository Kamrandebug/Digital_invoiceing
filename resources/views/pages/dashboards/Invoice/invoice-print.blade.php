@php
    $invoice         = $invoice ?? null;
    $items           = $items   ?? collect();
    $orientation     = $orientation ?? 'portrait';
    $seller          = $seller ?? null;
    $buyer           = $buyer ?? null;
    $template        = $template ?? null;
    $allTemplates    = $allTemplates ?? collect();
    $templateFields  = $templateFields ?? collect();
    $templateColumns = $templateColumns ?? collect();

    // Template design settings with safe fallbacks matching original invoice-print
    $tplId               = (int) ($template->ID ?? 1);
    $tplName             = trim((string) ($template->TemplateName ?? 'Standard Tax Invoice'));
    $docTitle            = trim((string) ($template->CustomTitle ?? 'Sales Tax Invoice')) ?: 'Sales Tax Invoice';
    $isDefaultTpl        = (int) ($template->IsDefault ?? 1) === 1;
    $isSystemDefault     = (int) ($template->IsSystemDefault ?? 1) === 1;
    $paperSize           = trim((string) ($template->PaperSize ?? 'A4')) ?: 'A4';
    $themeStyle          = strtolower(trim((string) ($template->ThemeStyle ?? 'classic'))) ?: 'classic';
    $accentColor         = trim((string) ($template->AccentColor ?? '#1e3a5f')) ?: '#1e3a5f';
    $fontFamily          = trim((string) ($template->FontFamily ?? 'Arial, sans-serif')) ?: 'Arial, sans-serif';
    $baseFontSize        = max(9, min(16, (int) ($template->BaseFontSize ?? 12)));
    $showLogo            = (int) ($template->ShowLogo ?? 1) === 1;
    $logoMaxHeight       = max(40, min(200, (int) ($template->LogoMaxHeight ?? 105)));
    $topMarginPx         = max(10, min(250, (int) ($template->TopMarginPx ?? 35)));
    $showSellerHeader    = (int) ($template->ShowSellerHeader ?? 1) === 1;
    $sellerHeading       = trim((string) ($template->SellerHeading ?? 'Seller')) ?: 'Seller';
    $buyerHeading        = trim((string) ($template->BuyerHeading ?? 'Buyer')) ?: 'Buyer';
    $showDraftWatermark  = (int) ($template->ShowDraftWatermark ?? 0) === 1;
    $showAmountInWords   = (int) ($template->ShowAmountInWords ?? 0) === 1;
    $showBankDetails     = (int) ($template->ShowBankDetails ?? 0) === 1;
    $bankDetailsText     = trim((string) ($template->BankDetailsText ?? ''));
    $showSignatureBox    = (int) ($template->ShowSignatureBox ?? 0) === 1;
    $signatureLabelLeft  = trim((string) ($template->SignatureLabelLeft ?? 'Prepared / Checked By'));
    $signatureLabelRight = trim((string) ($template->SignatureLabelRight ?? 'Authorized Signature & Stamp'));

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
                    $sellerLogoSrc = asset('storage/'.$path);
                }
            }
        }
    }

    $lineGst = static function ($item): float {
        return (float) ($item->GSTAmount ?? $item->SalesTaxApplicable ?? 0);
    };

    $lineDisc = static function ($item): float {
        return (float) ($item->DiscountAmount ?? $item->Discount ?? 0);
    };

    $lineExtraTax = static function ($item): float {
        return (float) ($item->ExtraTax ?? 0);
    };

    $lineFurtherTax = static function ($item): float {
        return (float) ($item->FurtherTax ?? 0);
    };

    $lineFedPayable = static function ($item): float {
        return (float) ($item->FedPayable ?? 0);
    };

    $lineNet = static function ($item) use ($lineGst, $lineDisc, $lineExtraTax, $lineFurtherTax, $lineFedPayable): float {
        $stored = (float) ($item->TotalValues ?? $item->Total ?? 0);
        if (abs($stored) >= 0.000001) {
            return $stored;
        }
        $amt = (float) ($item->Amount ?? 0);
        $disc = $lineDisc($item);
        $gst = $lineGst($item);
        $extra = $lineExtraTax($item);
        $further = $lineFurtherTax($item);
        $fed = $lineFedPayable($item);

        return max(0, $amt - $disc) + $gst + $extra + $further + $fed;
    };

    $sumQty = 0.0;
    $sumFbrQty = 0.0;
    $sumAmount = 0.0;
    $sumDisc = 0.0;
    $sumFixedNotified = 0.0;
    $sumGst = 0.0;
    $sumExtraTax = 0.0;
    $sumFurtherTax = 0.0;
    $sumFedPayable = 0.0;
    $sumNet = 0.0;
    $showFixedNotifiedColumn = false;
    $hasAnyHsCode = false;
    $hasFbrQty = false;

    foreach ($items as $it) {
        $qVal = (float) ($it->raw_Quantity ?? 0);
        $fbrQVal = (float) ($it->raw_FbrQty ?? 0);
        $amtVal = (float) ($it->raw_Amount ?? 0);
        $discVal = (float) ($it->raw_Discount ?? 0);
        $fixedVal = (float) ($it->raw_FixedNotifiedValue ?? 0);
        $gstVal = (float) ($it->raw_SalesTaxAmount ?? 0);
        $extraVal = (float) ($it->raw_ExtraTax ?? 0);
        $furtherVal = (float) ($it->raw_FurtherTax ?? 0);
        $fedVal = (float) ($it->raw_FedPayable ?? 0);
        $netVal = (float) ($it->raw_TotalValues ?? 0);
        if (abs($netVal) < 0.000001) {
            $netVal = max(0, $amtVal - $discVal) + $gstVal + $extraVal + $furtherVal + $fedVal;
        }

        $sumQty += $qVal;
        $sumFbrQty += $fbrQVal;
        $sumAmount += $amtVal;
        $sumDisc += $discVal;
        $sumFixedNotified += $fixedVal;
        $sumGst += $gstVal;
        $sumExtraTax += $extraVal;
        $sumFurtherTax += $furtherVal;
        $sumFedPayable += $fedVal;
        $sumNet += $netVal;

        if ($fixedVal > 0.000001) {
            $showFixedNotifiedColumn = true;
        }
        if (trim((string) ($it->HsCode ?? '')) !== '' && trim((string) ($it->HsCode ?? '')) !== '—') {
            $hasAnyHsCode = true;
        }
        if (($it->raw_Unit ?? 0) > 1 || (abs($fbrQVal - $qVal) > 0.000001 && $fbrQVal > 0.000001)) {
            $hasFbrQty = true;
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

    $headerGst = (float) ($invoice->GSTAmount ?? $invoice->TotalGST ?? $invoice->SalesTaxApplicable ?? 0);
    if ($headerGst <= 0 && $sumGst > 0) {
        $headerGst = $sumGst;
    }

    $headerExtraTax = (float) ($invoice->ExtraTax ?? 0);
    if ($headerExtraTax <= 0 && $sumExtraTax > 0) {
        $headerExtraTax = $sumExtraTax;
    }

    $headerFurtherTax = (float) ($invoice->FurtherTax ?? 0);
    if ($headerFurtherTax <= 0 && $sumFurtherTax > 0) {
        $headerFurtherTax = $sumFurtherTax;
    }

    $headerFedPayable = (float) ($invoice->FedPayable ?? 0);
    if ($headerFedPayable <= 0 && $sumFedPayable > 0) {
        $headerFedPayable = $sumFedPayable;
    }

    $headerTotal = (float) ($invoice->TotalValues ?? $invoice->TotalAmount ?? $invoice->Total ?? 0);
    if ($headerTotal <= 0 && $sumNet > 0) {
        $headerTotal = $sumNet;
    }

    $extraTaxRate = (float) ($buyer->ExtraTax ?? 0);
    $furtherTaxRate = (float) ($buyer->FurtherTax ?? 0);
    $fedPayableRate = (float) ($buyer->FedPayable ?? 0);

    if ($headerSub > 0.000001) {
        if ($extraTaxRate <= 0.000001 && $headerExtraTax > 0.000001) {
            $extraTaxRate = round(($headerExtraTax / $headerSub) * 100, 2);
        }
        if ($furtherTaxRate <= 0.000001 && $headerFurtherTax > 0.000001) {
            $furtherTaxRate = round(($headerFurtherTax / $headerSub) * 100, 2);
        }
        if ($fedPayableRate <= 0.000001 && $headerFedPayable > 0.000001) {
            $fedPayableRate = round(($headerFedPayable / $headerSub) * 100, 2);
        }
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
    $finalPayableAmount = $advanceTaxRate > 0.000001 ? $grandTotalAllTaxes : $headerTotal;
    $amountInWordsText  = \App\Models\PrintTemplateModel::amountInWordsPKR($finalPayableAmount);

    $fbrLogoUrl = asset('images/fbr/fbr-digital-invoicing-badge.jpg');
    $fbrTxId = trim((string) data_get($invoice, 'FbrTransactionID', ''));
    $qrPx = 320;
    $qrSource = $fbrTxId !== ''
        ? 'https://api.qrserver.com/v1/create-qr-code/?size='.$qrPx.'x'.$qrPx.'&ecc=M&data='.urlencode($fbrTxId)
        : '';
    $printDocumentTitle = $invoice
        ? 'GD-DI-'.\Carbon\Carbon::now()->format('Ymd').'-'.(int) ($invoice->ID ?? 0)
        : 'Invoice Print';

    // Group template fields by SectionGroup
    $fieldsByGroup = $templateFields->groupBy('SectionGroup');
    $metaFields    = $fieldsByGroup->get('meta', collect())->sortBy('CustomDisplayOrder')->values();
    $sellerFields  = $fieldsByGroup->get('seller', collect())->sortBy('CustomDisplayOrder')->values();
    $buyerFields   = $fieldsByGroup->get('buyer', collect())->sortBy('CustomDisplayOrder')->values();
    $summaryFields = $fieldsByGroup->get('summary', collect())->sortBy('CustomDisplayOrder')->values();
    $notesFields   = $fieldsByGroup->get('notes', collect())->sortBy('CustomDisplayOrder')->values();

    // Helper to evaluate 3-state visibility: 1 = Always Show, 2 = Auto-Hide if empty/zero, 0 = Hide
    $shouldShowField = static function (int $mode, bool $hasValue): bool {
        if ($mode === 0) return false;
        if ($mode === 2) return $hasValue;
        return true;
    };

    // Determine visible line-item columns in CustomDisplayOrder
    $orderedColumns = $templateColumns->sortBy('CustomDisplayOrder')->values();
    $visibleColumns = $orderedColumns->filter(function ($col) use (
        $shouldShowField, $hasAnyHsCode, $hasFbrQty, $sumDisc,
        $showFixedNotifiedColumn, $sumExtraTax, $sumFurtherTax, $sumFedPayable
    ) {
        $mode = (int) ($col->CustomIsOn ?? 1);
        $key  = (string) $col->FieldKey;
        $hasData = match ($key) {
            'HsCode'             => $hasAnyHsCode,
            'FbrQty', 'FbrQtyWithUnit', 'HsUnit' => $hasFbrQty,
            'Discount'           => $sumDisc > 0.000001,
            'FixedNotifiedValue' => $showFixedNotifiedColumn,
            'ExtraTax'           => $sumExtraTax > 0.000001,
            'FurtherTax'         => $sumFurtherTax > 0.000001,
            'FedPayable'         => $sumFedPayable > 0.000001,
            default              => true,
        };
        return $shouldShowField($mode, $hasData);
    })->values();

    $visibleColKeys      = $visibleColumns->pluck('FieldKey')->toArray();
    $isHsCodeSeparateCol = in_array('HsCode', $visibleColKeys, true);
    $hasAnyTotalCol      = $visibleColumns->contains(fn ($c) => (int) ($c->IsTotal ?? 0) === 1);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $printDocumentTitle }}</title>

    {{-- Theme CSS (Bootstrap 5, FontAwesome, Theme Custom CSS, Listing Table CSS) --}}
    <link href="{{ asset('build/assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('build/assets/themecss/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('build/assets/themecss/custom.css') }}" rel="stylesheet">
    <link href="{{ asset('css/customer-management.css') }}" rel="stylesheet">

    <style>
        /* Printable Document Sheet Layout (matches original invoice-print paper formatting) */
        @page { size: {{ $paperSize }} {{ $orientation }}; margin: 20px; }
        @media print {
            @page { size: {{ $paperSize }} {{ $orientation }}; margin: 20px; }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .invoice-print {
                padding-top: {{ $topMarginPx }}px !important;
                padding-bottom: 35px !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                margin-left: 20px !important;
                margin-right: 20px !important;
                width: calc(100% - 40px) !important;
                max-width: calc(100% - 40px) !important;
                box-sizing: border-box !important;
            }
            .no-print { display: none !important; }
            .print-section-1-logo img { max-height: {{ $logoMaxHeight }}px !important; max-width: 300px !important; }
            .fbr-brand-logo { max-width: 1in !important; max-height: 1in !important; }
            .fbr-qr-image { width: 1in !important; height: 1in !important; }
            .after-table-summary {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 360px !important;
                column-gap: 28px !important;
                page-break-inside: avoid !important;
                break-inside: avoid-page !important;
            }
            .invoice-print-footer, .fbr-digital-section, .totals {
                page-break-inside: avoid !important;
                break-inside: avoid-page !important;
            }
            table.items thead th {
                font-size: {{ max(9, $baseFontSize - 2) }}px !important;
            }
            .invoice-print-watermark {
                display: block !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .invoice-cancelled-stamp {
                display: inline-block !important;
                color: #dc2626 !important;
                border: 2px solid #dc2626 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        body {
            background: #fff !important;
            color: #222;
        }
        .invoice-print {
            position: relative;
            font-family: {!! $fontFamily !!};
            font-size: {{ $baseFontSize }}px;
            line-height: {{ $themeStyle === 'compact' ? '1.25' : '1.4' }};
            width: calc(100% - 40px);
            max-width: calc(100% - 40px);
            padding-top: {{ $topMarginPx }}px;
            padding-bottom: 35px;
            margin-left: 20px;
            margin-right: 20px;
            box-sizing: border-box;
        }
        .print-section-1 {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding-bottom: {{ $themeStyle === 'compact' ? '8px' : '12px' }};
            border-bottom: 2px solid {{ $themeStyle === 'modern' ? $accentColor : '#333' }};
        }
        .print-section-1-logo { flex: 0 0 auto; max-width: 320px; align-self: flex-start; }
        .print-section-1-logo img {
            max-width: 300px;
            max-height: {{ $logoMaxHeight }}px;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
        }
        .print-section-1-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-left: auto;
        }
        .invoice-doc-title {
            text-align: right;
            margin: 0 0 6px 0;
            font-size: {{ $themeStyle === 'compact' ? '20px' : '24px' }};
            font-weight: 800;
            letter-spacing: 0.04em;
            color: {{ $themeStyle === 'modern' ? $accentColor : '#111' }};
            line-height: 1.1;
        }
        .print-section-1-meta { text-align: right; width: 100%; }
        .print-section-1-meta table { margin-left: auto; border-collapse: collapse; }
        .print-section-1-meta th { text-align: right; padding: 2px 8px 2px 0; color: #555; font-weight: 600; white-space: nowrap; vertical-align: top; }
        .print-section-1-meta td { text-align: right; padding: 2px 0; }
        .print-section-2 {
            display: grid;
            grid-template-columns: 38% 24% 38%;
            align-items: start;
            padding: {{ $themeStyle === 'compact' ? '8px 0 10px' : '14px 0 16px' }};
            border-bottom: 2px solid {{ $themeStyle === 'modern' ? $accentColor : '#333' }};
            margin-bottom: {{ $themeStyle === 'compact' ? '10px' : '16px' }};
        }
        .party-col { min-width: 0; font-size: 10px; text-align: left; }
        @if($themeStyle === 'modern')
        .party-col {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-top: 3px solid {{ $accentColor }};
            border-radius: 6px;
            padding: 10px 12px;
        }
        @endif
        .party-col h2 {
            margin: 0 0 10px 0;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: {{ $themeStyle === 'modern' ? $accentColor : '#111' }};
            text-decoration: {{ $themeStyle === 'modern' ? 'none' : 'underline' }};
            text-decoration-thickness: 2px;
            text-underline-offset: 5px;
        }
        .party-col table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .party-col th { text-align: left; padding: 2px 8px 2px 0; width: 44%; color: #555; font-weight: 600; vertical-align: top; line-height: 1.35; overflow-wrap: break-word; }
        .party-col td { padding: 2px 0 2px 4px; width: 56%; vertical-align: top; text-align: left; overflow-wrap: break-word; line-height: 1.35; text-transform: uppercase; }
        .party-col .party-name { font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #111; text-transform: uppercase; }
        table.items { width: 100%; border-collapse: collapse; margin: {{ $themeStyle === 'compact' ? '10px 0' : '16px 0' }}; }
        table.items th, table.items td {
            border: 1px solid {{ $themeStyle === 'modern' ? '#cbd5e1' : '#ddd' }};
            padding: {{ $themeStyle === 'compact' ? '4px 6px' : '6px 8px' }};
        }
        table.items thead th {
            background: {{ $themeStyle === 'modern' ? $accentColor : '#f5f5f5' }} !important;
            color: {{ $themeStyle === 'modern' ? '#ffffff' : '#222222' }} !important;
            font-size: {{ max(9, $baseFontSize - 2) }}px !important;
            font-weight: 600;
            vertical-align: middle;
            line-height: 1.2;
        }
        table.items tfoot th {
            background: #f8fafc !important;
            font-size: {{ max(10, $baseFontSize - 1) }}px !important;
            font-weight: 700;
            border-top: 2px solid {{ $themeStyle === 'modern' ? $accentColor : '#333' }};
        }
        table.items td { font-size: {{ $themeStyle === 'compact' ? max(9, $baseFontSize - 1) : $baseFontSize }}px; }
        table.items td.col-value-amount { white-space: nowrap; font-variant-numeric: tabular-nums; }
        table.items td.td-desc-goods { vertical-align: top; }
        .desc-goods-hs { font-size: {{ $baseFontSize }}px; color: #111; margin: 0 0 4px 0; line-height: 1.35; }
        .desc-goods-hscode { font-size: {{ max(9, $baseFontSize - 1.5) }}px; color: #555; }
        .desc-goods-detail { font-size: {{ $baseFontSize }}px; color: #333; margin: 0; line-height: 1.4; word-break: break-word; }
        .after-table-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            align-items: start;
            column-gap: 28px;
            margin-top: {{ $themeStyle === 'compact' ? '10px' : '16px' }};
        }
        .invoice-print-footer {
            padding-bottom: 14px;
            border-bottom: 2px solid {{ $themeStyle === 'modern' ? $accentColor : '#333' }};
        }
        .totals { width: 100%; max-width: 360px; justify-self: end; }
        .totals table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
        .totals th { text-align: right; padding: 3px 10px 3px 0; font-weight: 600; width: 58%; }
        .totals td { text-align: right; padding: 3px 0; white-space: nowrap; width: 42%; }
        .totals tr.totals-rule-top th, .totals tr.totals-rule-top td { border-top: 1px solid #222; padding-top: 6px; }
        .totals tr.totals-rule-bottom th, .totals tr.totals-rule-bottom td { border-bottom: 1px solid #222; padding-bottom: 6px; }
        .notes { margin-top: 14px; padding: 8px 12px; background: #f9f9f9; border-radius: 4px; font-size: 12px; white-space: pre-line; }
        .business-notes { max-width: 96%; margin-left: auto; margin-right: auto; padding: 10px 14px; font-size: 10px; line-height: 1.5; white-space: normal; }
        .business-notes .notes-label { display: block; text-align: left; margin: 0 0 2px 0; }
        .business-notes .notes-body { margin: 0; text-align: center; white-space: pre-line; }
        .fbr-digital-title { font-size: 12px; font-weight: 700; margin-bottom: 10px; color: {{ $accentColor }}; }
        .fbr-digital-row { display: flex; align-items: center; gap: 16px; }
        .fbr-badge-wrap { border: 1px solid #d8e2f0; background: #fff; border-radius: 6px; padding: 6px; display: inline-flex; }
        .fbr-badge-slot { width: 1in; height: 1in; display: flex; align-items: center; justify-content: center; }
        .fbr-brand-logo { max-width: 1in; max-height: 1in; object-fit: contain; }
        .fbr-qr-image { width: 1in; height: 1in; object-fit: contain; }
        .fbr-qr-placeholder { width: 1in; height: 1in; border: 1px dashed #cbd5e1; background: #f8fafc; }
        .invoice-print-watermark {
            position: absolute; top: 42%; left: 50%; transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 76px; font-weight: 900; color: rgba(220, 38, 38, 0.14) !important;
            text-transform: uppercase; letter-spacing: 0.18em; pointer-events: none; z-index: 99;
            border: 6px solid rgba(220, 38, 38, 0.14) !important; padding: 12px 52px; border-radius: 12px; white-space: nowrap;
        }
        .invoice-print-watermark.watermark-draft {
            color: rgba(100, 116, 139, 0.13) !important;
            border-color: rgba(100, 116, 139, 0.13) !important;
        }
        .invoice-cancelled-stamp {
            position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
            font-size: 13px; font-weight: 800; color: #dc2626 !important; border: 2px solid #dc2626 !important;
            padding: 3px 12px; border-radius: 4px; letter-spacing: 0.1em; text-transform: uppercase;
        }
    </style>
</head>
<body class="orientation-{{ $orientation }}">

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{--  TOP CONTROL BAR (.no-print) — Uses Standard Bootstrap / Theme Classes --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 bg-light border-bottom sticky-top no-print">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <label for="toolbar_template_select" class="form-label mb-0 fw-semibold fs-12">Template:</label>
            <select id="toolbar_template_select" class="form-select form-select-sm w-auto" onchange="onChangePrintToolbar()">
                @foreach($allTemplates as $tItem)
                    <option value="{{ $tItem->ID }}" @selected((int) $tItem->ID === $tplId)>
                        {{ $tItem->TemplateName }}{{ (int) ($tItem->IsDefault ?? 0) === 1 ? ' (Default)' : '' }}
                    </option>
                @endforeach
            </select>

            <label for="toolbar_orientation_select" class="form-label mb-0 fw-semibold fs-12 ms-2">Orientation:</label>
            <select id="toolbar_orientation_select" class="form-select form-select-sm w-auto" onchange="onChangePrintToolbar()">
                <option value="portrait" @selected($orientation === 'portrait')>Portrait</option>
                <option value="landscape" @selected($orientation === 'landscape')>Landscape</option>
            </select>

            <button type="button" class="btn btn-sm btn-secondary-light border ms-1"
                    data-bs-toggle="offcanvas" data-bs-target="#printSettingsDrawer" aria-controls="printSettingsDrawer">
                <i class="fas fa-cog me-1"></i>Customize Template
            </button>
        </div>

        <div class="d-flex align-items-center gap-2">
            <small class="text-muted d-none d-lg-inline">
                Uncheck <strong>Headers and footers</strong> in browser print dialog
            </small>
            <button type="button" class="btn btn-sm btn-success" onclick="triggerInvoicePrint()">
                <i class="fas fa-print me-1"></i>Print / Save as PDF
            </button>
        </div>
    </div>

    @if($invoice)
    @php
        $isCancelled = (int) ($invoice->Statusi ?? 0) === 8;
        $isDraftUnposted = ! $isCancelled && $fbrTxId === '';
    @endphp
    <div class="invoice-print">
        @if($isCancelled)
            <div class="invoice-print-watermark" aria-hidden="true">CANCELLED</div>
            <div class="invoice-cancelled-stamp">CANCELLED INVOICE</div>
        @elseif($showDraftWatermark && $isDraftUnposted)
            <div class="invoice-print-watermark watermark-draft" aria-hidden="true">DRAFT</div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{--  SECTION 1: Logo (Left) | Document Title + Dynamic Meta (Right)    --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div class="print-section-1">
            <div class="print-section-1-logo">
                @if($showLogo && $sellerLogoSrc !== '')
                    <img src="{{ $sellerLogoSrc }}" alt="{{ trim((string) ($seller->BusinessName ?? '')) ?: 'Business logo' }}">
                @endif
            </div>
            <div class="print-section-1-right">
                <h1 class="invoice-doc-title">{{ $docTitle }}</h1>
                <div class="print-section-1-meta">
                    <table>
                        @foreach($metaFields as $mField)
                            @php
                                $mKey  = (string) $mField->FieldKey;
                                $mMode = (int) ($mField->CustomIsOn ?? 1);
                                $mVal  = match ($mKey) {
                                    'DocNo'            => trim((string) ($invoice->DocNo ?? '')),
                                    'DocNo2'           => trim((string) ($invoice->DocNo2 ?? '')),
                                    'DocNo3'           => trim((string) ($invoice->DocNo3 ?? '')),
                                    'InvoiceDate'      => $invoice->InvoiceDate ? \Carbon\Carbon::parse($invoice->InvoiceDate)->format('d M Y') : '',
                                    'SaleOfTerm'       => trim((string) ($invoice->SaleOfTerm ?? '')),
                                    'FbrTransactionID' => $fbrTxId,
                                    default            => trim((string) data_get($invoice, $mKey, '')),
                                };
                                $hasVal = $mVal !== '';
                                if (! $shouldShowField($mMode, $hasVal)) {
                                    continue;
                                }
                                $displayVal = $hasVal ? $mVal : ($mKey === 'DocNo' ? 'N/A' : '-');
                                $fSize = (int) ($mField->CustomFontSize ?? 12);
                                $isBold = (int) ($mField->IsBold ?? 0) === 1;
                            @endphp
                            <tr style="font-size: {{ $fSize }}px;">
                                <th>{{ $mField->CustomLabel }}</th>
                                <td class="{{ $isBold ? 'fw-bold' : '' }}">{{ $displayVal }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{--  SECTION 2: Dynamic Seller (Left) | Spacer | Dynamic Buyer (Right) --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div class="print-section-2">
            <div class="party-col party-seller">
                @if($showSellerHeader)
                    <h2>{{ $sellerHeading }}</h2>
                    @if($seller)
                        @php
                            $sellerNameField = $sellerFields->firstWhere('FieldKey', 'SellerBusinessName');
                            $showSellerName  = $sellerNameField ? $shouldShowField((int) $sellerNameField->CustomIsOn, !empty(trim((string) ($seller->BusinessName ?? '')))) : true;
                        @endphp
                        @if($showSellerName)
                            <div class="party-name" style="font-size: {{ (int) ($sellerNameField->CustomFontSize ?? 12) }}px;">
                                {{ $seller->BusinessName ?? '—' }}
                            </div>
                        @endif
                        <table>
                            @foreach($sellerFields as $sField)
                                @php
                                    if ($sField->FieldKey === 'SellerBusinessName') continue;
                                    $sVal = match ((string) $sField->FieldKey) {
                                        'SellerAddress'  => trim((string) ($seller->Address ?? '')),
                                        'SellerProvince' => trim((string) ($seller->Province ?? '')),
                                        'SellerPhone'    => trim((string) ($seller->Phone ?? '')),
                                        'SellerNTN'      => trim((string) ($seller->NTN ?? '')),
                                        'SellerSTN'      => trim((string) ($seller->SalesTaxNumber ?? '')),
                                        'SellerEmail'    => trim((string) ($seller->Email ?? '')),
                                        default          => '',
                                    };
                                    if (! $shouldShowField((int) $sField->CustomIsOn, $sVal !== '')) {
                                        continue;
                                    }
                                @endphp
                                <tr style="font-size: {{ (int) ($sField->CustomFontSize ?? 10) }}px;">
                                    <th>{{ $sField->CustomLabel }}</th>
                                    <td class="{{ (int) ($sField->IsBold ?? 0) === 1 ? 'fw-bold' : '' }}">{{ $sVal !== '' ? $sVal : '—' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <p class="m-0 text-muted">No business profile on file.</p>
                    @endif
                @endif
            </div>
            <div class="party-spacer" aria-hidden="true"></div>
            <div class="party-col party-buyer">
                <h2>{{ $buyerHeading }}</h2>
                @if($buyer)
                    @php
                        $buyerNameField = $buyerFields->firstWhere('FieldKey', 'BuyerBusinessName');
                        $rawBuyerName   = trim((string) ($buyer->BusinessName ?? ($invoice->CustomerDisplayName ?? '')));
                        $showBuyerName  = $buyerNameField ? $shouldShowField((int) $buyerNameField->CustomIsOn, $rawBuyerName !== '') : true;
                    @endphp
                    @if($showBuyerName)
                        <div class="party-name" style="font-size: {{ (int) ($buyerNameField->CustomFontSize ?? 12) }}px;">
                            {{ $rawBuyerName !== '' ? $rawBuyerName : '—' }}
                        </div>
                    @endif
                    <table>
                        @foreach($buyerFields as $bField)
                            @php
                                if ($bField->FieldKey === 'BuyerBusinessName') continue;
                                $bVal = match ((string) $bField->FieldKey) {
                                    'BuyerAddress'  => trim((string) ($buyer->Address ?? '')),
                                    'BuyerProvince' => trim((string) ($buyer->County ?? '')),
                                    'BuyerPhone'    => trim((string) ($buyer->Phone ?? '')),
                                    'BuyerNTN'      => trim((string) ($buyer->NTN ?? '')),
                                    'BuyerSTN'      => trim((string) ($buyer->SalesTaxNumber ?? '')),
                                    'BuyerEmail'    => trim((string) ($buyer->Email ?? '')),
                                    default         => '',
                                };
                                if (! $shouldShowField((int) $bField->CustomIsOn, $bVal !== '')) {
                                    continue;
                                };
                            @endphp
                            <tr style="font-size: {{ (int) ($bField->CustomFontSize ?? 10) }}px;">
                                <th>{{ $bField->CustomLabel }}</th>
                                <td class="{{ (int) ($bField->IsBold ?? 0) === 1 ? 'fw-bold' : '' }}">{{ $bVal !== '' ? $bVal : '—' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <div class="party-name">{{ $invoice->CustomerDisplayName ?? '—' }}</div>
                @endif
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{--  SECTION 3: Dynamic Line-Items Table (`tbl_print_template_columns`) --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <table class="items">
            <thead>
                <tr>
                    @foreach($visibleColumns as $col)
                        @php
                            $align = strtolower((string) ($col->CustomAlignment ?? 'left'));
                            $alignClass = $align === 'right' ? 'text-end' : ($align === 'center' ? 'text-center' : 'text-start');
                            $minW  = trim((string) ($col->CustomWidthMin ?? '50px'));
                            $maxW  = trim((string) ($col->CustomWidthMax ?? '150px'));
                        @endphp
                        <th class="{{ $alignClass }}" style="min-width: {{ $minW }}; max-width: {{ $maxW }};">
                            {{ $col->CustomLabel }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $item)
                    <tr>
                        @foreach($visibleColumns as $col)
                            @php
                                $cKey  = (string) $col->FieldKey;
                                $align = strtolower((string) ($col->CustomAlignment ?? 'left'));
                                $alignClass = $align === 'right' ? 'text-end' : ($align === 'center' ? 'text-center' : 'text-start');
                                $minW  = trim((string) ($col->CustomWidthMin ?? '50px'));
                                $maxW  = trim((string) ($col->CustomWidthMax ?? '150px'));

                                // Special case for CombinedDescription: if HsCode standalone column is visible, omit (HS Code: ...)
                                $cellContent = '';
                                if ($cKey === 'SrNo') {
                                    $cellContent = (string) ($idx + 1);
                                } elseif ($cKey === 'CombinedDescription' || $cKey === 'Description') {
                                    $pName = trim((string) ($item->ProductName ?? ''));
                                    $hCode = trim((string) ($item->HsCode ?? ''));
                                    $pDesc = trim((string) ($item->ProductDescription ?? ''));
                                    $htmlParts = [];
                                    if ($pName !== '' && $pName !== '—') {
                                        $htmlParts[] = '<strong>' . e($pName) . '</strong>';
                                    }
                                    if (! $isHsCodeSeparateCol && $hCode !== '' && $hCode !== '—') {
                                        $htmlParts[] = ' <span class="text-muted" style="font-size:10.5px;">(HS Code: ' . e($hCode) . ')</span>';
                                    }
                                    if ($pDesc !== '' && $pDesc !== '—' && strcasecmp($pDesc, $pName) !== 0) {
                                        $htmlParts[] = '<div class="text-muted mt-1" style="font-size:11.5px; line-height:1.35;">' . e($pDesc) . '</div>';
                                    }
                                    $cellContent = !empty($htmlParts) ? implode('', $htmlParts) : '—';
                                } elseif ($cKey === 'Unit' || ($cKey === 'CustomUnit' && (!isset($item->CustomUnit) || $item->CustomUnit === '—' || $col->CustomLabel === 'Unit'))) {
                                    // Smart Combined Unit: custom packing unit if Unit > 1, else HS/FBR UoM, fallback '—'
                                    $smartUnit = trim((string) ($item->Unit ?? ''));
                                    if ($smartUnit === '' || in_array($smartUnit, ['No Unit', '—', '-'], true)) {
                                        $smartUnit = trim((string) ($item->CustomUnit ?? ''));
                                    }
                                    if ($smartUnit === '' || in_array($smartUnit, ['No Unit', '—', '-'], true)) {
                                        $smartUnit = trim((string) ($item->HsUnit ?? ''));
                                    }
                                    $cellContent = ($smartUnit !== '' && !in_array($smartUnit, ['No Unit', '—', '-'], true)) ? $smartUnit : '—';
                                } else {
                                    $cellContent = (string) ($item->{$cKey} ?? '—');
                                    if ($cellContent === '' || $cellContent === 'No Unit') {
                                        $cellContent = '—';
                                    }
                                }
                            @endphp
                            <td class="{{ $alignClass }} {{ in_array($cKey, ['ValueExclTax', 'SalesTaxAmount', 'TotalInclTax', 'Rate', 'FixedNotifiedValue', 'ExtraTax', 'FurtherTax', 'FedPayable'], true) ? 'col-value-amount' : '' }} {{ in_array($cKey, ['Description', 'CombinedDescription'], true) ? 'td-desc-goods' : '' }}"
                                style="min-width: {{ $minW }}; max-width: {{ $maxW }};">
                                {!! $cellContent !!}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(1, $visibleColumns->count()) }}" class="text-center">No line items.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($hasAnyTotalCol && $items->count() > 0)
                <tfoot>
                    <tr>
                        @foreach($visibleColumns as $colIdx => $col)
                            @php
                                $cKey       = (string) $col->FieldKey;
                                $align      = strtolower((string) ($col->CustomAlignment ?? 'left'));
                                $alignClass = $align === 'right' ? 'text-end' : ($align === 'center' ? 'text-center' : 'text-start');
                                $isTotal    = (int) ($col->IsTotal ?? 0) === 1;
                                $totText    = '';
                                if ($isTotal) {
                                    $totText = match ($cKey) {
                                        'FbrQty', 'FbrQtyWithUnit' => number_format($sumFbrQty, 2),
                                        'Quantity', 'QtyWithUnit'  => number_format($sumQty, 2),
                                        'Discount'                 => number_format($sumDisc, 2),
                                        'ValueExclTax'             => number_format($sumAmount, 2),
                                        'FixedNotifiedValue'       => number_format($sumFixedNotified, 2),
                                        'SalesTaxAmount'           => number_format($sumGst, 2),
                                        'ExtraTax'                 => number_format($sumExtraTax, 2),
                                        'FurtherTax'               => number_format($sumFurtherTax, 2),
                                        'FedPayable'               => number_format($sumFedPayable, 2),
                                        'TotalInclTax'             => number_format($sumNet, 2),
                                        default                    => '',
                                    };
                                } elseif ($colIdx === 0) {
                                    $totText = 'Total';
                                }
                            @endphp
                            <th class="{{ $alignClass }}">{{ $totText }}</th>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{--  SECTION 4: FBR QR Badge (Left) + Dynamic Summary Totals (Right)   --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
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
                    <div class="totals-inner">
                        <table>
                            @foreach($summaryFields as $sumField)
                                @php
                                    $sKey  = (string) $sumField->FieldKey;
                                    $sMode = (int) ($sumField->CustomIsOn ?? 1);
                                    $sVal  = match ($sKey) {
                                        'TotalExclTax'    => $headerSub,
                                        'Discount'        => $headerDisc,
                                        'TotalSalesTax'   => $headerGst,
                                        'ExtraTax'        => $headerExtraTax,
                                        'FurtherTax'      => $headerFurtherTax,
                                        'FedPayable'      => $headerFedPayable,
                                        'TotalInclTax'    => $headerTotal,
                                        'CustomerWht236g' => $customerWht236g,
                                        'GrandTotal'      => $grandTotalAllTaxes,
                                        default           => 0.0,
                                    };
                                    $isNonZero = match ($sKey) {
                                        'CustomerWht236g', 'GrandTotal' => $advanceTaxRate > 0.000001,
                                        default => abs($sVal) > 0.000001,
                                    };
                                    if (! $shouldShowField($sMode, $isNonZero)) {
                                        continue;
                                    }

                                    $label = rtrim((string) $sumField->CustomLabel, ':');
                                    if ($sKey === 'ExtraTax' && $extraTaxRate > 0.000001) {
                                        $label .= ' (' . number_format($extraTaxRate, 2) . '%)';
                                    } elseif ($sKey === 'FurtherTax' && $furtherTaxRate > 0.000001) {
                                        $label .= ' (' . number_format($furtherTaxRate, 2) . '%)';
                                    } elseif ($sKey === 'FedPayable' && $fedPayableRate > 0.000001) {
                                        $label .= ' (' . number_format($fedPayableRate, 2) . '%)';
                                    } elseif ($sKey === 'CustomerWht236g' && $advanceTaxRate > 0.000001) {
                                        $label .= ' (' . number_format($advanceTaxRate, 2) . '%)';
                                    }
                                    $label .= ':';

                                    $rowClass = match ($sKey) {
                                        'TotalInclTax' => 'totals-inc totals-rule-top totals-rule-bottom',
                                        'GrandTotal'   => 'grand totals-rule-top',
                                        default        => '',
                                    };
                                    $fSize  = (int) ($sumField->CustomFontSize ?? 12);
                                    $isBold = (int) ($sumField->IsBold ?? 0) === 1;
                                @endphp
                                <tr class="{{ $rowClass }}" style="font-size: {{ $fSize }}px;">
                                    <th class="{{ $isBold ? 'fw-bold' : '' }}">{{ $label }}</th>
                                    <td class="{{ $isBold ? 'fw-bold' : '' }}">{{ number_format($sVal, 2) }} PKR</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>

            {{-- Amount in Words: single line, right-aligned, bold prefix, normal text --}}
            @if($showAmountInWords)
                <div class="amount-in-words-line text-end mt-2 pt-2 border-top">
                    <strong class="text-uppercase" style="font-size: 11.5px; letter-spacing: 0.03em;">AMOUNT IN WORDS:</strong>
                    <span class="fw-normal text-dark ms-1" style="font-size: 12px;">{{ $amountInWordsText }}</span>
                </div>
            @endif
        </div>

        {{-- Optional Bank / Payment Details --}}
        @if($showBankDetails && $bankDetailsText !== '')
            <div class="p-2 mt-3 bg-light border rounded" style="white-space: pre-line;">
                <strong>Bank / Payment Instructions:</strong><br>{{ $bankDetailsText }}
            </div>
        @endif

        {{-- Dynamic Notes Fields (`SellerNotes`, `InvoiceNotes`) --}}
        @foreach($notesFields as $nField)
            @php
                $nKey  = (string) $nField->FieldKey;
                $nMode = (int) ($nField->CustomIsOn ?? 2);
                $nText = match ($nKey) {
                    'SellerNotes'  => trim((string) ($seller->Notes ?? '')),
                    'InvoiceNotes' => trim((string) ($invoice->Notes ?? '')),
                    default        => '',
                };
                if (! $shouldShowField($nMode, $nText !== '') || $nText === '') {
                    continue;
                }
                $fSize = (int) ($nField->CustomFontSize ?? 12);
            @endphp
            @if($nKey === 'SellerNotes')
                <div class="notes business-notes" style="font-size: {{ $fSize }}px;">
                    <strong class="notes-label">{{ $nField->CustomLabel }}</strong>
                    <div class="notes-body">{{ ltrim($nText) }}</div>
                </div>
            @else
                <div class="notes" style="font-size: {{ $fSize }}px;">
                    <strong>{{ $nField->CustomLabel }}</strong><br>{{ $nText }}
                </div>
            @endif
        @endforeach

        {{-- Optional Signature & Stamp Footer --}}
        @if($showSignatureBox)
            <div class="d-flex justify-content-between align-items-end mt-5 pt-3">
                <div class="border-top border-dark pt-1 text-center fw-semibold" style="width: 220px;">
                    {{ $signatureLabelLeft }}
                </div>
                <div class="border-top border-dark pt-1 text-center fw-semibold" style="width: 220px;">
                    {{ $signatureLabelRight }}
                </div>
            </div>
        @endif
    </div>
    @else
        <p class="p-3">Invoice not found.</p>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{--  BOOTSTRAP 5 OFFCANVAS — PRINT TEMPLATE SETTINGS (Theme CSS Only)      --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="offcanvas offcanvas-end no-print" tabindex="-1" id="printSettingsDrawer"
         aria-labelledby="printSettingsDrawerLabel" style="width: 880px; max-width: 100vw; z-index: 1065;">
        <div class="offcanvas-header border-bottom">
            <div>
                <h6 id="printSettingsDrawerLabel" class="offcanvas-title fw-semibold mb-0">
                    <i class="fas fa-cog me-1 text-primary"></i>Print Template Settings
                </h6>
                <small class="text-muted">Customize layout, branding, Seller/Buyer fields, or drag &amp; drop line-item columns</small>
            </div>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column justify-content-between p-3">
            <div>
                <div id="psdAlertBox" class="alert d-none py-2 mb-3"></div>

                {{-- Bootstrap Nav Tabs --}}
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab_design" type="button" role="tab">
                            1. Design &amp; Branding
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_fields" type="button" role="tab">
                            2. Header, Parties &amp; Totals
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_columns" type="button" role="tab">
                            3. Line-Item Columns
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- TAB 1: DESIGN, BRANDING & MODERN FEATURES --}}
                    <div class="tab-pane fade show active" id="tab_design" role="tabpanel">
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Template Name</label>
                                <input type="text" id="cfg_TemplateName" class="form-control form-control-sm" value="{{ $tplName }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Invoice Document Title</label>
                                <input type="text" id="cfg_CustomTitle" class="form-control form-control-sm" value="{{ $docTitle }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end pb-1">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="cfg_IsDefault" @checked($isDefaultTpl)>
                                    <label class="form-check-label fw-medium" for="cfg_IsDefault">Set as Default Template</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Default Orientation</label>
                                <select id="cfg_PrintOrientation" class="form-select form-select-sm">
                                    <option value="portrait" @selected(strtolower((string) ($template->PrintOrientation ?? 'portrait')) === 'portrait')>Portrait</option>
                                    <option value="landscape" @selected(strtolower((string) ($template->PrintOrientation ?? 'portrait')) === 'landscape')>Landscape</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Paper Size</label>
                                <select id="cfg_PaperSize" class="form-select form-select-sm">
                                    <option value="A4" @selected($paperSize === 'A4')>A4 (Standard)</option>
                                    <option value="Letter" @selected($paperSize === 'Letter')>Letter</option>
                                    <option value="A5" @selected($paperSize === 'A5')>A5 (Half Page)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Visual Theme Style</label>
                                <select id="cfg_ThemeStyle" class="form-select form-select-sm">
                                    <option value="classic" @selected($themeStyle === 'classic')>Classic FBR Bordered</option>
                                    <option value="modern" @selected($themeStyle === 'modern')>Modern Branded Header</option>
                                    <option value="compact" @selected($themeStyle === 'compact')>Compact (High-Density)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Brand Accent Color</label>
                                <input type="color" id="cfg_AccentColor" class="form-control form-control-sm form-control-color w-100" value="{{ $accentColor }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Base Font Size (px)</label>
                                <input type="number" id="cfg_BaseFontSize" class="form-control form-control-sm" min="9" max="16" value="{{ $baseFontSize }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Top Margin / Letterhead Offset (px)</label>
                                <input type="number" id="cfg_TopMarginPx" class="form-control form-control-sm" min="10" max="250" value="{{ $topMarginPx }}">
                            </div>
                        </div>

                        <div class="row g-2 mb-3 pb-3 border-bottom">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Seller Box Heading</label>
                                <input type="text" id="cfg_SellerHeading" class="form-control form-control-sm" value="{{ $sellerHeading }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Buyer Box Heading</label>
                                <input type="text" id="cfg_BuyerHeading" class="form-control form-control-sm" value="{{ $buyerHeading }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Max Logo Height (px)</label>
                                <input type="number" id="cfg_LogoMaxHeight" class="form-control form-control-sm" min="40" max="200" value="{{ $logoMaxHeight }}">
                            </div>
                        </div>

                        <h6 class="fw-semibold fs-13 mb-2">Visibility &amp; Modern Print Features</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cfg_ShowLogo" @checked($showLogo)>
                                    <label class="form-check-label" for="cfg_ShowLogo">Show Business Logo</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cfg_ShowSellerHeader" @checked($showSellerHeader)>
                                    <label class="form-check-label" for="cfg_ShowSellerHeader">Show Seller Details Box (Uncheck for pre-printed letterhead)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cfg_ShowDraftWatermark" @checked($showDraftWatermark)>
                                    <label class="form-check-label" for="cfg_ShowDraftWatermark">Show "DRAFT" Watermark when unposted to FBR</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cfg_ShowAmountInWords" @checked($showAmountInWords)>
                                    <label class="form-check-label" for="cfg_ShowAmountInWords">Show Total Amount in Words (PKR)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cfg_ShowSignatureBox" @checked($showSignatureBox)>
                                    <label class="form-check-label" for="cfg_ShowSignatureBox">Show Prepared / Authorized Signature Lines</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cfg_ShowBankDetails" @checked($showBankDetails)>
                                    <label class="form-check-label" for="cfg_ShowBankDetails">Show Bank / Payment Instructions</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label mb-1">Left Signature Label</label>
                                <input type="text" id="cfg_SignatureLabelLeft" class="form-control form-control-sm" value="{{ $signatureLabelLeft }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-1">Right Signature Label</label>
                                <input type="text" id="cfg_SignatureLabelRight" class="form-control form-control-sm" value="{{ $signatureLabelRight }}">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1">Bank / Payment Instructions Text (IBAN, Bank Name, Account Title)</label>
                            <textarea id="cfg_BankDetailsText" class="form-control form-control-sm" rows="2"
                                      placeholder="e.g. Bank: Meezan Bank | Title: ABC Traders | IBAN: PK00MEZN000...">{{ $bankDetailsText }}</textarea>
                        </div>
                    </div>

                    {{-- TAB 2: HEADER META, SELLER, BUYER, SUMMARY & NOTES FIELDS --}}
                    <div class="tab-pane fade" id="tab_fields" role="tabpanel">
                        <div class="text-muted fs-12 mb-2">
                            Drag rows within each section to reorder, rename labels, or choose <strong>Always Show</strong>, <strong>Auto-Hide if Empty/0</strong>, or <strong>Always Hide</strong>.
                        </div>

                        <div class="border rounded p-2" style="max-height: calc(100vh - 260px); overflow-y: auto;">
                            @php
                                $sectionLabels = [
                                    'meta'    => 'Document Meta (Top Right)',
                                    'seller'  => 'Seller Information (Left Box)',
                                    'buyer'   => 'Buyer Information (Right Box)',
                                    'summary' => 'Summary Totals (Bottom Right)',
                                    'notes'   => 'Footer Notes',
                                ];
                            @endphp

                            @foreach($sectionLabels as $secKey => $secTitle)
                                @php $secRows = $fieldsByGroup->get($secKey, collect())->sortBy('CustomDisplayOrder')->values(); @endphp
                                <div class="badge bg-primary-transparent text-primary fw-semibold mb-2 mt-2">{{ $secTitle }}</div>
                                <div class="table-responsive border rounded mb-3">
                                    <table class="table text-nowrap listing-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 55px;">Order</th>
                                                <th style="width: 155px;">Visibility</th>
                                                <th style="width: 155px;">Original Field</th>
                                                <th>Custom Display Label</th>
                                                <th style="width: 80px;">Font (px)</th>
                                                <th style="width: 55px;" class="text-center">Bold</th>
                                            </tr>
                                        </thead>
                                        <tbody class="sortable-tbody" data-group="fields_{{ $secKey }}">
                                            @foreach($secRows as $idx => $fRow)
                                                <tr class="field-config-row" data-field-id="{{ $fRow->ID }}">
                                                    <td class="text-muted" style="cursor: grab;">
                                                        <i class="fas fa-grip-vertical me-1"></i>
                                                        <span class="row-order-num">{{ $idx + 1 }}</span>
                                                    </td>
                                                    <td>
                                                        <select class="form-select form-select-sm f-is-on">
                                                            <option value="1" @selected((int) $fRow->CustomIsOn === 1)>Always Show</option>
                                                            <option value="2" @selected((int) $fRow->CustomIsOn === 2)>Auto-Hide if Empty/0</option>
                                                            <option value="0" @selected((int) $fRow->CustomIsOn === 0)>Always Hide</option>
                                                        </select>
                                                    </td>
                                                    <td class="text-muted">{{ $fRow->OriginalLabel }}</td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm f-label" value="{{ $fRow->CustomLabel }}">
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm f-fontsize" min="8" max="24" value="{{ (int) ($fRow->CustomFontSize ?? 12) }}">
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="checkbox" class="form-check-input f-bold" @checked((int) ($fRow->IsBold ?? 0) === 1)>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- TAB 3: LINE-ITEM TABLE COLUMNS --}}
                    <div class="tab-pane fade" id="tab_columns" role="tabpanel">
                        <div class="text-muted fs-12 mb-2">
                            Drag rows to reorder line-item columns. When <strong>HS Code</strong> or <strong>Extra/Further/FED Tax</strong> are set to <em>Always Hide</em> as standalone columns, they automatically display inline inside <em>Description</em> and <em>Sales Tax Amount</em>.
                        </div>

                        <div class="table-responsive border rounded" style="max-height: calc(100vh - 260px); overflow-y: auto;">
                            <table class="table text-nowrap listing-table mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th style="width: 55px;">Order</th>
                                        <th style="width: 150px;">Visibility</th>
                                        <th style="width: 55px;" class="text-center">Total</th>
                                        <th style="width: 135px;">Original Column</th>
                                        <th>Custom Header Label</th>
                                        <th style="width: 80px;">Min (px)</th>
                                        <th style="width: 80px;">Max (px)</th>
                                        <th style="width: 95px;">Alignment</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable-tbody" data-group="columns">
                                    @foreach($orderedColumns as $idx => $cRow)
                                        @php
                                            $minNum = (int) preg_replace('/[^0-9]/', '', (string) ($cRow->CustomWidthMin ?? '60'));
                                            $maxNum = (int) preg_replace('/[^0-9]/', '', (string) ($cRow->CustomWidthMax ?? '120'));
                                            $canTotal = in_array((string) $cRow->FieldKey, [
                                                'FbrQty', 'FbrQtyWithUnit', 'Quantity', 'QtyWithUnit',
                                                'Discount', 'ValueExclTax', 'FixedNotifiedValue',
                                                'SalesTaxAmount', 'ExtraTax', 'FurtherTax', 'FedPayable', 'TotalInclTax'
                                            ], true);
                                        @endphp
                                        <tr class="col-config-row" data-col-id="{{ $cRow->ID }}">
                                            <td class="text-muted" style="cursor: grab;">
                                                <i class="fas fa-grip-vertical me-1"></i>
                                                <span class="row-order-num">{{ $idx + 1 }}</span>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm c-is-on">
                                                    <option value="1" @selected((int) $cRow->CustomIsOn === 1)>Always Show</option>
                                                    <option value="2" @selected((int) $cRow->CustomIsOn === 2)>Auto-Hide if 0</option>
                                                    <option value="0" @selected((int) $cRow->CustomIsOn === 0)>Always Hide</option>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                @if($canTotal)
                                                    <input type="checkbox" class="form-check-input c-is-total" @checked((int) ($cRow->IsTotal ?? 0) === 1) title="Show sum in table footer">
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $cRow->OriginalLabel }}</td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm c-label" value="{{ $cRow->CustomLabel }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm c-min-w" min="25" max="500" value="{{ $minNum }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm c-max-w" min="25" max="600" value="{{ $maxNum }}">
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm c-align">
                                                    <option value="left" @selected(strtolower((string) $cRow->CustomAlignment) === 'left')>Left</option>
                                                    <option value="center" @selected(strtolower((string) $cRow->CustomAlignment) === 'center')>Center</option>
                                                    <option value="right" @selected(strtolower((string) $cRow->CustomAlignment) === 'right')>Right</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Offcanvas Footer Buttons --}}
            <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-3">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-danger-light" onclick="resetCurrentPrintTemplate()">
                        <i class="fas fa-undo me-1"></i>Reset to Default
                    </button>
                    @if(! $isSystemDefault)
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCurrentPrintTemplate()">
                            <i class="fas fa-trash me-1"></i>Delete Template
                        </button>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light border" onclick="saveAsNewPrintTemplatePrompt()">
                        <i class="fas fa-plus me-1"></i>Save as New Template
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnSavePrintTpl" onclick="savePrintTemplateChanges(false)">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('build/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        const CURRENT_INVOICE_ID  = {{ (int) ($invoice->ID ?? 0) }};
        const CURRENT_TEMPLATE_ID = {{ $tplId }};
        const CSRF_TOKEN          = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function onChangePrintToolbar() {
            const tplId  = document.getElementById('toolbar_template_select').value;
            const orient = document.getElementById('toolbar_orientation_select').value;
            const url = new URL(window.location.href);
            url.searchParams.set('template_id', tplId);
            url.searchParams.set('orientation', orient);
            url.searchParams.set('_', Date.now());
            window.location.href = url.toString();
        }

        function showPsdAlert(type, msg) {
            const box = document.getElementById('psdAlertBox');
            box.className = 'alert py-2 mb-3 ' + (type === 'success' ? 'alert-success' : 'alert-danger');
            box.textContent = msg;
        }

        // Drag & drop row sorting for all .sortable-tbody tables
        document.querySelectorAll('.sortable-tbody').forEach(tbody => {
            let draggedRow = null;
            tbody.querySelectorAll('tr').forEach(row => {
                row.setAttribute('draggable', 'true');
                row.addEventListener('dragstart', function (e) {
                    draggedRow = this;
                    this.classList.add('table-active');
                    e.dataTransfer.effectAllowed = 'move';
                });
                row.addEventListener('dragend', function () {
                    this.classList.remove('table-active');
                    draggedRow = null;
                    renumberTbody(tbody);
                });
                row.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    if (!draggedRow || draggedRow === this || draggedRow.parentNode !== tbody) return;
                    const rect = this.getBoundingClientRect();
                    const after = (e.clientY - rect.top) > (rect.height / 2);
                    tbody.insertBefore(draggedRow, after ? this.nextSibling : this);
                });
            });
        });

        function renumberTbody(tbody) {
            tbody.querySelectorAll('tr').forEach((tr, idx) => {
                const numSpan = tr.querySelector('.row-order-num');
                if (numSpan) numSpan.textContent = (idx + 1);
            });
        }

        function collectPrintTemplatePayload(overrideTemplateName) {
            const fields = {};
            document.querySelectorAll('.field-config-row').forEach((tr) => {
                const id = tr.getAttribute('data-field-id');
                const orderNum = parseInt(tr.querySelector('.row-order-num').textContent, 10) || 1;
                fields[id] = {
                    order:     orderNum,
                    is_on:     parseInt(tr.querySelector('.f-is-on').value, 10),
                    label:     tr.querySelector('.f-label').value.trim(),
                    font_size: parseInt(tr.querySelector('.f-fontsize').value, 10) || 12,
                    is_bold:   tr.querySelector('.f-bold').checked ? 1 : 0,
                };
            });

            const columns = {};
            document.querySelectorAll('.col-config-row').forEach((tr, idx) => {
                const id = tr.getAttribute('data-col-id');
                const totCheckbox = tr.querySelector('.c-is-total');
                columns[id] = {
                    order:     idx + 1,
                    is_on:     parseInt(tr.querySelector('.c-is-on').value, 10),
                    is_total:  (totCheckbox && totCheckbox.checked) ? 1 : 0,
                    label:     tr.querySelector('.c-label').value.trim(),
                    width_min: tr.querySelector('.c-min-w').value,
                    width_max: tr.querySelector('.c-max-w').value,
                    alignment: tr.querySelector('.c-align').value,
                };
            });

            return {
                template_id:         CURRENT_TEMPLATE_ID,
                TemplateName:        overrideTemplateName || document.getElementById('cfg_TemplateName').value.trim(),
                CustomTitle:         document.getElementById('cfg_CustomTitle').value.trim(),
                IsDefault:           document.getElementById('cfg_IsDefault').checked ? 1 : 0,
                PrintOrientation:    document.getElementById('cfg_PrintOrientation').value,
                PaperSize:           document.getElementById('cfg_PaperSize').value,
                ThemeStyle:          document.getElementById('cfg_ThemeStyle').value,
                AccentColor:         document.getElementById('cfg_AccentColor').value,
                BaseFontSize:        parseInt(document.getElementById('cfg_BaseFontSize').value, 10) || 12,
                TopMarginPx:         parseInt(document.getElementById('cfg_TopMarginPx').value, 10) || 35,
                LogoMaxHeight:       parseInt(document.getElementById('cfg_LogoMaxHeight').value, 10) || 105,
                SellerHeading:       document.getElementById('cfg_SellerHeading').value.trim(),
                BuyerHeading:        document.getElementById('cfg_BuyerHeading').value.trim(),
                ShowLogo:            document.getElementById('cfg_ShowLogo').checked ? 1 : 0,
                ShowSellerHeader:    document.getElementById('cfg_ShowSellerHeader').checked ? 1 : 0,
                ShowDraftWatermark:  document.getElementById('cfg_ShowDraftWatermark').checked ? 1 : 0,
                ShowAmountInWords:   document.getElementById('cfg_ShowAmountInWords').checked ? 1 : 0,
                ShowSignatureBox:    document.getElementById('cfg_ShowSignatureBox').checked ? 1 : 0,
                SignatureLabelLeft:  document.getElementById('cfg_SignatureLabelLeft').value.trim(),
                SignatureLabelRight: document.getElementById('cfg_SignatureLabelRight').value.trim(),
                ShowBankDetails:     document.getElementById('cfg_ShowBankDetails').checked ? 1 : 0,
                BankDetailsText:     document.getElementById('cfg_BankDetailsText').value.trim(),
                fields:              fields,
                columns:             columns,
            };
        }

        function savePrintTemplateChanges(asNew, customNewName) {
            const endpoint = asNew
                ? '{{ route("invoices.print-template.save-as-new") }}'
                : '{{ route("invoices.print-template.save") }}';

            const payload = collectPrintTemplatePayload(customNewName);

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json())
            .then(res => {
                if (!res.status) {
                    showPsdAlert('error', res.message || 'Unable to save template settings.');
                    return;
                }
                showPsdAlert('success', res.message || 'Saved!');
                const targetTplId = res.template_id || CURRENT_TEMPLATE_ID;
                const url = new URL(window.location.href);
                url.searchParams.set('template_id', targetTplId);
                url.searchParams.set('orientation', payload.PrintOrientation);
                url.searchParams.set('_', Date.now());
                setTimeout(() => { window.location.href = url.toString(); }, 350);
            })
            .catch(() => showPsdAlert('error', 'Network error while saving print template.'));
        }

        function saveAsNewPrintTemplatePrompt() {
            const currentName = document.getElementById('cfg_TemplateName').value.trim();
            const newName = prompt('Enter a name for the new Print Template:', currentName + ' (Copy)');
            if (!newName || !newName.trim()) return;
            savePrintTemplateChanges(true, newName.trim());
        }

        function resetCurrentPrintTemplate() {
            if (!confirm('Reset this template back to the original system default settings?')) return;
            const btn = document.querySelector('button[onclick="resetCurrentPrintTemplate()"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Resetting...';
            }
            const resetUrl = @json(route('invoices.print-template.reset', ['templateId' => '__TPL_ID__'])).replace('__TPL_ID__', CURRENT_TEMPLATE_ID);
            fetch(resetUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
            })
            .then(async (r) => {
                const data = await r.json().catch(() => null);
                if (!r.ok || !data?.status) {
                    throw new Error(data?.message || ('Reset failed with server error HTTP ' + r.status));
                }
                return data;
            })
            .then(res => {
                showPsdAlert('success', res.message || 'Template restored to defaults! Reloading...');
                const url = new URL(window.location.href);
                url.searchParams.set('template_id', CURRENT_TEMPLATE_ID);
                url.searchParams.delete('orientation');
                url.searchParams.set('_', Date.now());
                setTimeout(() => {
                    window.location.href = url.toString();
                }, 350);
            })
            .catch(err => {
                console.error('Reset template error:', err);
                showPsdAlert('error', err.message || 'Network error while resetting template.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-undo me-1"></i>Reset to Default';
                }
            });
        }

        function deleteCurrentPrintTemplate() {
            if (!confirm('Are you sure you want to delete this custom template?')) return;
            const btn = document.querySelector('button[onclick="deleteCurrentPrintTemplate()"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Deleting...';
            }
            const deleteUrl = @json(route('invoices.print-template.delete', ['templateId' => '__TPL_ID__'])).replace('__TPL_ID__', CURRENT_TEMPLATE_ID);
            fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
            })
            .then(async (r) => {
                const data = await r.json().catch(() => null);
                if (!r.ok || !data?.status) {
                    throw new Error(data?.message || ('Delete failed with server error HTTP ' + r.status));
                }
                return data;
            })
            .then(res => {
                showPsdAlert('success', res.message || 'Template deleted! Reloading...');
                const url = new URL(window.location.href);
                url.searchParams.set('template_id', res.template_id || 1);
                url.searchParams.delete('orientation');
                url.searchParams.set('_', Date.now());
                setTimeout(() => {
                    window.location.href = url.toString();
                }, 350);
            })
            .catch(err => {
                console.error('Delete template error:', err);
                showPsdAlert('error', err.message || 'Network error while deleting template.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash me-1"></i>Delete Template';
                }
            });
        }

        function triggerInvoicePrint() {
            const d = new Date();
            const saveTitle = 'GD-DI-' + d.getFullYear()
                + String(d.getMonth() + 1).padStart(2, '0')
                + String(d.getDate()).padStart(2, '0')
                + '-' + String(CURRENT_INVOICE_ID);

            const origSelfTitle = document.title;
            let origParentTitle = null;
            document.title = saveTitle;
            try {
                if (window.parent && window.parent !== window && window.parent.document) {
                    origParentTitle = window.parent.document.title;
                    window.parent.document.title = saveTitle;
                }
            } catch (e) {}

            window.print();

            setTimeout(() => {
                document.title = origSelfTitle;
                try {
                    if (origParentTitle !== null && window.parent && window.parent.document) {
                        window.parent.document.title = origParentTitle;
                    }
                } catch (e) {}
            }, 1500);
        }
    </script>
</body>
</html>
